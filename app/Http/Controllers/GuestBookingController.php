<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\DocumentType;
use App\Exceptions\BookingException;
use App\Http\Requests\Site\CreateGuestBookingRequest;
use App\Http\Requests\Site\LookupBookingRequest;
use App\Http\Requests\Site\QuoteGuestBookingRequest;
use App\Http\Requests\Site\StoreGuestBookingRequest;
use App\Models\Booking;
use App\Models\DriverOnlyBooking;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
    ) {}

    public function create(CreateGuestBookingRequest $request, string $locale, Vehicle $vehicle): View
    {
        $dates = $request->validated();

        if (blank($dates['du'] ?? null) || blank($dates['au'] ?? null)) {
            $start = today()->addDay();
            $period = BookingPeriod::make(
                $start->toDateString(),
                $start->copy()->addDays(3)->toDateString(),
                $this->settings->openHour(),
                $this->settings->closeHour(),
                $this->settings->openHour(),
                $this->settings->closeHour(),
            );
        } else {
            $period = $this->periodFrom($dates);
            $this->validatePeriod($period);
        }

        $withDriver = (bool) ($dates['avec_chauffeur'] ?? false);

        return view('booking.guest', [
            'vehicle' => $vehicle->load('category'),
            'period' => $period,
            'quote' => $this->pricing->quote($vehicle->category, $period, $withDriver, $vehicle->deposit_required),
            'withDriver' => $withDriver,
        ]);
    }

    public function quote(QuoteGuestBookingRequest $request, string $locale, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validated();

        $period = $this->periodFrom($data);
        $this->validatePeriod($period);
        $quote = $this->pricing->quote($vehicle->category, $period, (bool) ($data['avec_chauffeur'] ?? false), $vehicle->deposit_required);

        return response()->json([
            'days' => $quote->days,
            'daily_price' => $quote->dailyPrice,
            'price_rental' => $quote->priceRental,
            'price_driver' => $quote->priceDriver,
            'price_total' => $quote->priceTotal,
        ]);
    }

    public function store(StoreGuestBookingRequest $request, string $locale, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validated();

        $period = $this->periodFrom($data);
        $this->validatePeriod($period);
        $token = Str::random(64);

        try {
            $booking = $this->bookings->create([
                'vehicle' => $vehicle,
                'period' => $period,
                'user' => null,
                'with_driver' => (bool) ($data['avec_chauffeur'] ?? false),
                'phone_verified_at' => null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'customer_comment' => $data['customer_comment'] ?? null,
                'guest_access_token_hash' => hash('sha256', $token),
            ]);
        } catch (BookingException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        // Accusé de réception au client (s'il a laissé une adresse) et alerte
        // à l'agence : la demande ne doit pas attendre qu'un agent ouvre
        // l'administration pour être vue.
        $this->notifications->sendBookingReceived($booking, $booking->customer_email);
        $this->notifications->sendNewBookingRequest($booking);

        return redirect()->route('booking.guest.documents', [
            'locale' => $locale,
            'booking' => $booking,
            'token' => $token,
        ]);
    }

    /**
     * Étape documents de la demande invitée : juste après l'envoi du formulaire,
     * le client dépose sa pièce d'identité et son permis. Aucun compte requis :
     * le jeton du lien fait office de preuve de possession.
     */
    public function documents(string $locale, Booking $booking, string $token): View|RedirectResponse
    {
        $this->guardToken($booking, $token);

        if (! $booking->status->canStillUploadDocuments()) {
            return redirect()->route('booking.guest.confirmation', [
                'locale' => $locale,
                'booking' => $booking,
                'token' => $token,
            ]);
        }

        return view('booking.documents', [
            'booking' => $booking->load(['vehicle.category', 'documents']),
            'quote' => $this->pricing->quoteForBooking($booking),
            'required' => DocumentType::requiredForSubmission(),
            'reusable' => [],
            'isGuest' => true,
            'guestToken' => $token,
        ]);
    }

    public function confirmation(string $locale, Booking $booking, string $token): View|RedirectResponse
    {
        $this->guardToken($booking, $token);

        // La pièce d'identité est demandée en ligne ; le permis, lui, est
        // contrôlé par le personnel au retrait.
        if ($booking->status === BookingStatus::EnAttente && ! $this->hasRequiredIdentityCard($booking)) {
            return redirect()->route('booking.guest.documents', [
                'locale' => $locale,
                'booking' => $booking,
                'token' => $token,
            ]);
        }

        return view('booking.guest-confirmation', [
            'booking' => $booking->load(['vehicle.category']),
            'token' => $token,
        ]);
    }

    private function hasRequiredIdentityCard(Booking $booking): bool
    {
        return $booking->documents()
            ->where('type', DocumentType::IdentityCard->value)
            ->exists();
    }

    /**
     * Vérifie que le jeton du lien correspond bien à cette demande invitée.
     * On refuse aussi les réservations rattachées à un compte : elles passent
     * par le tunnel authentifié, pas par ce lien public.
     */
    public function lookup(Request $request, string $locale): View
    {
        return view('booking.lookup', [
            'locale' => $locale,
            'booking' => null,
            'driverOnlyBooking' => null,
            'matches' => collect(),
            // Le lien d'un e-mail arrive avec la référence : la pré-remplir
            // évite au client de la recopier depuis sa boîte.
            'reference' => Str::upper(trim($request->string('reference')->toString())),
            'customerName' => '',
            'error' => null,
        ]);
    }

    public function lookupStore(LookupBookingRequest $request, string $locale): View
    {
        $data = $request->validated();
        $reference = Str::upper(trim($data['reference'] ?? ''));
        $customerName = trim($data['customer_name'] ?? '');

        if ($reference !== '') {
            $bookings = Booking::query()
                ->whereRaw('UPPER(reference) = ?', [$reference])
                ->with(['vehicle.category', 'user'])
                ->get();

            // Une demande de chauffeur seul a sa propre table et sa propre référence.
            $driverOnlyBookings = DriverOnlyBooking::query()
                ->whereRaw('UPPER(reference) = ?', [$reference])
                ->get();
        } else {
            // Le client n'a pas toujours sa référence sous la main : chaque mot
            // de son nom doit se retrouver dans la réservation ou dans son
            // compte, dans n'importe quel ordre (« Afi Mensah » ou « Mensah Afi »).
            [$bookings, $driverOnlyBookings] = $this->searchByName($customerName);
        }

        $matches = $bookings
            ->map(fn (Booking $booking): array => [
                'reference' => $booking->reference,
                'customer' => $booking->user?->displayName() ?? $booking->customer_name ?? '',
                'service' => $booking->vehicle?->fullName() ?? '',
                'period' => $booking->starts_at->translatedFormat('d/m/Y H:i')
                    .' → '.$booking->ends_at->translatedFormat('d/m/Y H:i'),
                'status' => $booking->status->label(),
                'color' => $booking->status->color(),
            ])
            ->concat($driverOnlyBookings->map(fn (DriverOnlyBooking $booking): array => [
                'reference' => $booking->reference,
                'customer' => $booking->customer_name,
                'service' => __('driver.solo_title'),
                'period' => $booking->starts_at->translatedFormat('d/m/Y H:i')
                    .' → '.$booking->ends_at->translatedFormat('d/m/Y H:i'),
                'status' => $booking->status->label(),
                'color' => $booking->status->color(),
            ]));

        return view('booking.lookup', [
            'locale' => $locale,
            // Une seule correspondance : fiche complète. Plusieurs : liste des
            // dossiers, pour que le client choisisse le sien sans étaler le
            // détail de tous ses homonymes.
            'booking' => $matches->count() === 1 ? $bookings->first() : null,
            'driverOnlyBooking' => $matches->count() === 1 ? $driverOnlyBookings->first() : null,
            'matches' => $matches,
            'reference' => $reference,
            'customerName' => $customerName,
            'error' => $matches->isEmpty() ? __('booking.lookup.not_found') : null,
        ]);
    }

    /**
     * Recherche par nom. Chaque mot doit correspondre au nom porté par la
     * réservation invitée ou par le compte du client.
     *
     * @return array{0: Collection<int, Booking>, 1: Collection<int, DriverOnlyBooking>}
     */
    private function searchByName(string $query): array
    {
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return [new Collection, new Collection];
        }

        $bookings = Booking::query()
            ->where(function ($builder) use ($words): void {
                foreach ($words as $word) {
                    $term = '%'.$word.'%';

                    $builder->where(function ($sub) use ($term): void {
                        $sub->where('customer_name', 'like', $term)
                            ->orWhereHas('user', fn ($user) => $user
                                ->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('name', 'like', $term));
                    });
                }
            })
            ->with(['vehicle.category', 'user'])
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

        $driverOnlyBookings = DriverOnlyBooking::query()
            ->where(function ($builder) use ($words): void {
                foreach ($words as $word) {
                    $builder->where('customer_name', 'like', '%'.$word.'%');
                }
            })
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

        return [$bookings, $driverOnlyBookings];
    }

    private function guardToken(Booking $booking, string $token): void
    {
        abort_unless(
            $booking->user_id === null
                && $booking->guest_access_token_hash !== null
                && hash_equals($booking->guest_access_token_hash, hash('sha256', $token)),
            404,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function periodFrom(array $data): BookingPeriod
    {
        return BookingPeriod::make(
            $data['du'],
            $data['au'],
            (int) ($data['heure_depart'] ?? $this->settings->openHour()),
            (int) ($data['heure_retour'] ?? $this->settings->closeHour()),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }

    private function validatePeriod(BookingPeriod $period): void
    {
        if ($period->startsAt->isPast()) {
            throw ValidationException::withMessages([
                'du' => __('booking.guest.start_must_be_future'),
            ]);
        }

        if ($period->endsAt->lessThanOrEqualTo($period->startsAt)) {
            throw ValidationException::withMessages([
                'heure_retour' => __('booking.guest.return_after_pickup'),
            ]);
        }

        if ($period->isOutsideOpeningHours()) {
            throw ValidationException::withMessages([
                'heure_depart' => __('booking.error.outside_opening_hours', [
                    'open' => $this->settings->string('agency.open_time'),
                    'close' => $this->settings->string('agency.close_time'),
                ]),
            ]);
        }
    }
}
