<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Exceptions\BookingException;
use App\Http\Requests\Concerns\GuardsBookingOwnership;
use App\Http\Requests\Site\CreateBookingRequest;
use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Site\ToggleDriverRequest;
use App\Http\Requests\Site\VerifyBookingPhoneCodeRequest;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\PhoneCode;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Tunnel de réservation, quatre étapes.
 *
 *   1. récapitulatif + option chauffeur  →  show()
 *   2. coordonnées du conducteur        →  contact() / storeContact()
 *   3. vérification du téléphone         →  verifyPhone() / verifyPhoneStore()
 *   4. dépôt des documents + confirmation → documents() / store()
 *
 * Chaque étape écrit en base immédiatement : le client peut fermer l'onglet et
 * revenir le lendemain, il reprend où il en était. Le prix, lui, est figé dès
 * l'étape 1 et ne bouge plus.
 */
class BookingController extends Controller
{
    use GuardsBookingOwnership;

    public function __construct(
        private readonly BookingService $bookings,
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
    ) {}

    /** Étape 1 — récapitulatif, option chauffeur, décompte vivant. */
    public function show(Request $request, string $locale, Booking $booking): View|RedirectResponse
    {
        $this->guardOwnership($request, $booking);

        if ($booking->status->isFinal()) {
            return redirect()->route('booking.confirmation', ['locale' => $locale, 'booking' => $booking]);
        }

        $period = $this->periodOf($booking);

        return view('booking.summary', [
            'booking' => $booking->load(['vehicle.category', 'user']),
            'period' => $period,
            'quote' => $this->pricing->quoteForBooking($booking),
            'driverAvailable' => $this->availability->canOfferDriver($period),
            'driverAutoChecked' => $this->settings->int('driver.auto_check_days'),
        ]);
    }

    /**
     * Le « Réserver » de la fiche véhicule arrive ici : on crée le panier de
     * réservation tout de suite, avec le prix figé, avant tout formulaire.
     * Un client qui ferme l'onglet retrouve sa réservation à la même adresse.
     *
     * L'écriture passe obligatoirement par BookingService : c'est lui qui
     * verrouille la ligne du véhicule, contrôle la disponibilité, l'âge, le
     * nombre de réservations et le planning des chauffeurs. Écrire la
     * réservation directement ici laisserait passer toutes ces règles, et
     * notamment la double réservation sur le même véhicule.
     */
    public function create(CreateBookingRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();

        $vehicle = Vehicle::query()->with('category')->findOrFail($data['vehicle_id']);
        $period = BookingPeriod::make(
            $data['du'],
            $data['au'],
            (int) $data['heure_depart'],
            (int) $data['heure_retour'],
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );

        if ($period->isOutsideOpeningHours()) {
            return back()->withErrors([
                'heure' => __('booking.error.outside_opening_hours', [
                    'open' => $this->settings->string('agency.open_time'),
                    'close' => $this->settings->string('agency.close_time'),
                ]),
            ]);
        }

        $withDriver = (bool) ($data['avec_chauffeur'] ?? false);

        // Un compte connecté qui a déjà une réservation exactement identique
        // continue simplement celle-ci plutôt que d'en créer une seconde.
        $existing = $request->user()?->bookings()
            ->where('vehicle_id', $vehicle->id)
            ->where('starts_at', $period->startsAt)
            ->where('ends_at', $period->endsAt)
            ->where('with_driver', $withDriver)
            ->first();

        if ($existing !== null) {
            return redirect()->route('booking.show', ['locale' => $locale, 'booking' => $existing]);
        }

        // Une BookingException est rendue en « retour avec message » par le
        // gestionnaire d'exceptions : le client lit la raison sur la fiche du
        // véhicule, au lieu d'atterrir sur une page d'erreur.
        $booking = $this->bookings->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => $request->user(),
            'with_driver' => $withDriver,
            'phone_verified_at' => null,
        ]);

        // On note la référence dans la session : c'est ce qui autorise plus
        // tard cette réservation orpheline à être rattachée au compte quand le
        // visiteur se connecte. Sans cette trace, n'importe quel compte
        // connecté qui connaît la référence pourrait s'en attribuer la propriété.
        $request->session()->push('pdj.reservations', $booking->reference);

        return redirect()->route('booking.show', ['locale' => $locale, 'booking' => $booking]);
    }

    /** Bascule chauffeur : le décompte se recalcule, le prix est réécrit. */
    public function toggleDriver(ToggleDriverRequest $request, string $locale, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $period = $this->periodOf($booking);

        try {
            $this->bookings->reprice($booking, $period, (bool) $data['avec_chauffeur']);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    /** Étape 2 — identité du conducteur. */
    public function contact(Request $request, string $locale, Booking $booking): View
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        return view('booking.contact', [
            'booking' => $booking->load(['vehicle.category', 'user']),
            'period' => $this->periodOf($booking),
            'documentsRequired' => DocumentType::requiredForSubmission(),
        ]);
    }

    /** Enregistre l'identité, crée le compte, puis déclenche le code de vérification. */
    public function storeContact(StoreContactRequest $request, string $locale, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $user = $this->resolveUser($request, $data);
        $minAge = $this->settings->int('booking.min_age');

        if ($user->age() !== null && $user->age() < $minAge) {
            return back()->withErrors(['birth_date' => __('booking.error.too_young', ['age' => $minAge])])->withInput();
        }

        $booking->update([
            'user_id' => $user->id,
            'phone_verified_at' => null,
        ]);

        $this->issuePhoneCode($user->phone, $request->string('canal', 'whatsapp')->toString());

        return redirect()->route('booking.verify-phone', ['locale' => $locale, 'booking' => $booking]);
    }

    /** Étape 3 — saisie du code. */
    public function verifyPhone(Request $request, string $locale, Booking $booking): View|RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        if ($booking->hasPhoneVerified()) {
            return redirect()->route('booking.documents', ['locale' => $locale, 'booking' => $booking]);
        }

        return view('booking.verify-phone', [
            'booking' => $booking->load(['vehicle.category', 'user']),
            'phone' => $booking->user?->phone,
        ]);
    }

    public function verifyPhoneStore(VerifyBookingPhoneCodeRequest $request, string $locale, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $user = $booking->user;

        if ($user === null) {
            return back()->withErrors(['code' => __('booking.error.no_account')]);
        }

        $phoneCode = PhoneCode::query()
            ->where('phone', $user->phone)
            ->latest('id')
            ->first();

        if ($phoneCode === null || ! $phoneCode->isUsable()) {
            return back()->withErrors(['code' => __('booking.error.code_expired')]);
        }

        if (! Hash::check($data['code'], $phoneCode->code_hash)) {
            $phoneCode->increment('attempts');

            return back()->withErrors(['code' => __('booking.error.code_invalid', [
                'left' => $phoneCode->attemptsLeft(),
            ])])->withInput();
        }

        $phoneCode->update(['verified_at' => now()]);
        $this->bookings->markPhoneVerified($booking);

        return redirect()->route('booking.documents', ['locale' => $locale, 'booking' => $booking]);
    }

    public function resendCode(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $this->guardOwnership($request, $booking);

        $phone = $booking->user?->phone;

        if ($phone === null) {
            return back();
        }

        $this->issuePhoneCode($phone, $request->string('canal', 'whatsapp')->toString());

        return back()->with('status', __('booking.code_sent', ['phone' => $phone]));
    }

    /** Étape 4 — documents, puis récapitulatif final. */
    public function documents(Request $request, string $locale, Booking $booking): View|RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        $this->guardBookingEligibility($request, $booking);

        if (! $booking->hasPhoneVerified()) {
            return redirect()->route('booking.verify-phone', ['locale' => $locale, 'booking' => $booking]);
        }

        return view('booking.documents', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
            'period' => $this->periodOf($booking),
            'quote' => $this->pricing->quoteForBooking($booking),
            'required' => DocumentType::requiredForSubmission(),
            'reusable' => $this->reusableDocuments($booking),
        ]);
    }

    /** Dernier écran : on relit tout avant d'envoyer. */
    public function review(Request $request, string $locale, Booking $booking): View|RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        $this->guardBookingEligibility($request, $booking);

        $required = DocumentType::requiredForSubmission();
        $uploaded = $booking->documents()->ofType(...$required)->pluck('type')->all();

        $missing = array_values(array_diff(
            array_map(fn (DocumentType $t) => $t->value, $required),
            $uploaded,
        ));

        return view('booking.review', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
            'period' => $this->periodOf($booking),
            'quote' => $this->pricing->quoteForBooking($booking),
            'missing' => $missing,
        ]);
    }

    /** Envoi final. La réservation part « en attente », un agent la confirme. */
    public function store(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if(! $booking->isNew(), 422);
        abort_if($booking->status->isFinal(), 422);

        $this->guardBookingEligibility($request, $booking);

        $required = DocumentType::requiredForSubmission();
        $uploaded = $booking->documents()->ofType(...$required)->pluck('type')->all();
        $missing = array_diff(array_map(fn (DocumentType $t) => $t->value, $required), $uploaded);

        if ($missing !== []) {
            return back()->with('error', __('booking.error.documents_missing'));
        }

        try {
            $confirmed = DB::transaction(fn () => $this->bookings->create([
                'vehicle' => $booking->vehicle,
                'period' => $this->periodOf($booking),
                'user' => $booking->user,
                'with_driver' => $booking->with_driver,
                'phone_verified_at' => $booking->phone_verified_at,
                'public_token' => $booking->public_token,
            ]));
        } catch (BookingException $e) {
            return redirect()->route('booking.show', ['locale' => $locale, 'booking' => $booking])->with('error', $e->getMessage());
        }

        $this->notifications->sendBookingReceived($confirmed, $confirmed->customer_email ?? $confirmed->user?->email);
        $this->notifications->sendNewBookingRequest($confirmed);

        return redirect()->route('booking.confirmation', ['locale' => $locale, 'booking' => $confirmed]);
    }

    public function confirmation(Request $request, string $locale, Booking $booking): View
    {
        $this->guardOwnership($request, $booking);

        return view('booking.confirmation', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
            'period' => $this->periodOf($booking),
            'quote' => $this->pricing->quoteForBooking($booking),
            'phone' => $this->settings->formattedPhone(),
            'whatsapp' => $this->settings->whatsappNumber(),
            'address' => $this->settings->string('agency.address'),
        ]);
    }

    /**
     * Cible du bouton « Je confirme ma venue » du rappel WhatsApp.
     *
     * Le lien est envoyé tel quel dans le message : il doit fonctionner sans
     * session, sans compte et dans la langue du message, d'où le jeton opaque
     * plutôt que la référence. La langue du client est déduite du numéro qui
     * a reçu le message plutôt que du visiteur, qui peut être l'agent.
     */
    public function confirmPresence(Request $request, string $locale, string $token): RedirectResponse
    {
        $booking = Booking::query()->where('public_token', $token)->firstOrFail();

        $this->bookings->markPresenceConfirmed($booking);

        $langue = $booking->user?->preferred_locale === 'en' ? 'en' : $locale;

        if ($booking->guest_access_token_hash !== null) {
            return redirect()
                ->route('home', ['locale' => $langue])
                ->with('status', __('booking.presence_confirmed'));
        }

        return redirect()
            ->route('booking.show', ['locale' => $langue, 'booking' => $booking])
            ->with('status', __('booking.presence_confirmed'));
    }

    // ── Aides ─────────────────────────────────────────────────────────────────

    private function periodOf(Booking $booking): BookingPeriod
    {
        return BookingPeriod::fromDates(
            $booking->starts_at->toImmutable(),
            $booking->ends_at->toImmutable(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }

    /**
     * Contrôles qui n'appartiennent pas au formulaire : l'âge, le permis,
     * le nombre de réservations actives. On les rejoue à l'envoi.
     */
    private function guardBookingEligibility(Request $request, Booking $booking): void
    {
        $user = $booking->user;
        $minAge = $this->settings->int('booking.min_age');

        if ($user?->age() !== null && $user->age() < $minAge) {
            throw ValidationException::withMessages([
                'birth_date' => __('booking.error.too_young', ['age' => $minAge]),
            ]);
        }

        $max = $this->settings->int('booking.max_active_bookings');

        if ($user !== null && $user->activeBookingsCount() > $max) {
            throw ValidationException::withMessages([
                'booking' => __('booking.error.too_many_bookings', ['max' => $max]),
            ]);
        }
    }

    /**
     * Fiche client créée à la volée si elle n'existe pas, sinon mise à jour.
     * Le tunnel n'impose jamais de créer un compte avant d'avoir vu le prix :
     * la fiche n'est qu'un moyen de retrouver le client (âge, historique,
     * absence), pas un espace personnel — il n'y a pas d'espace client.
     *
     * Le téléphone est stocké normalisé (chiffres seuls, préfixe 228) : « +228
     * 90 12 34 99 » et « 90123499 » sont le même numéro, et le chercher deux
     * fois donnerait deux fiches.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveUser(Request $request, array $data): User
    {
        $phone = $this->normalizePhone($data['phone']);

        $user = $request->user() ?? User::query()->where('phone', $phone)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $phone,
                'birth_date' => $data['birth_date'],
                'password' => Str::random(32),
                'preferred_locale' => app()->getLocale(),
            ]);
        } else {
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'],
            ]);
        }

        return $user;
    }

    /** Numéro au format E.164 sans « + » : 22890123499. */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return str_starts_with($digits, '228') ? $digits : '228'.$digits;
    }

    private function issuePhoneCode(string $phone, string $channel): void
    {
        $code = (string) random_int(100000, 999999);

        PhoneCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'channel' => $channel,
            'expires_at' => now()->addMinutes(PhoneCode::LIFETIME_MINUTES),
            'ip' => request()->ip(),
        ]);

        $this->notifications->sendPhoneCode($phone, $code, $channel);
    }

    /** Documents déjà vérifiés du client, réutilisables sans nouveau dépôt. */
    private function reusableDocuments(Booking $booking)
    {
        $user = $booking->user;

        if ($user === null) {
            return collect();
        }

        return $user->documents()
            ->whereIn('status', ['conforme'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', today()))
            ->get()
            ->keyBy(fn (CustomerDocument $d) => $d->type->value);
    }
}
