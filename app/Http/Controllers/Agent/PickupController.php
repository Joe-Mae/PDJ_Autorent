<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\HandoverBookingRequest;
use App\Http\Requests\Agent\PickBookingRequest;
use App\Http\Requests\Agent\SignPickupContractRequest;
use App\Http\Requests\Agent\StorePickupConditionReportRequest;
use App\Http\Requests\Agent\StorePickupDepositRequest;
use App\Http\Requests\Agent\StorePickupDocumentsRequest;
use App\Http\Requests\Agent\StorePickupPaymentRequest;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\DocumentStorageService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

/**
 * Retrait du véhicule, en six écrans verrouillés dans l'ordre.
 *
 * LaTablet de l'agent, un client debout en face, et vingt minutes à faire.
 * L'ordre n'est pas négociable : on ne rend pas les clés avant d'avoir vu les
 * originaux, encaissé la location, pris la caution et signé le contrat. C'est
 * la seule façon que la fin de journée corresponde à ce qui s'est passé.
 */
class PickupController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    /** Accueil : qui doit partir aujourd'hui, et qui est en retard. */
    public function index(): View
    {
        return view('agent.pickup.index', [
            'expected' => Booking::query()
                ->whereIn('status', [BookingStatus::Confirmee->value])
                ->whereDate('starts_at', today())
                ->with(['vehicle.category', 'user', 'documents'])
                ->orderBy('pickup_hour')
                ->get(),
            'late' => Booking::query()
                ->whereIn('status', [BookingStatus::EnAttente->value, BookingStatus::Confirmee->value])
                ->where('starts_at', '<', now())
                ->with(['vehicle', 'user'])
                ->get(),
        ]);
    }

    /** Recherche par référence ou scan du QR code du bon. */
    public function pick(PickBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $booking = Booking::query()
            ->where('reference', strtoupper($data['reference']))
            ->where('status', BookingStatus::Confirmee->value)
            ->firstOrFail();

        return redirect()->route('agent.pickup.documents', $booking);
    }

    /** Étape 1 — contrôle de la pièce d'identité. */
    public function documents(Booking $booking): View
    {
        abort_unless($booking->status === BookingStatus::Confirmee, 422);

        return view('agent.pickup.documents', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
            'minimumAge' => app(SettingsService::class)->int('booking.min_age'),
        ]);
    }

    public function storeDocuments(StorePickupDocumentsRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $identityOriginalVerified = $request->boolean('identity_original_verified');
        $identityNameMatches = $request->boolean('identity_name_matches');
        $customerAgeEligible = $request->boolean('customer_age_eligible');

        $identityFailures = [];

        if (! $identityOriginalVerified) {
            $identityFailures[] = __('agent.verification_failures.identity_not_seen');
        }

        if (! $identityNameMatches) {
            $identityFailures[] = __('agent.verification_failures.identity_name_mismatch');
        }

        if (! $customerAgeEligible) {
            $identityFailures[] = __('agent.verification_failures.age_requirement');
        }

        $identityVerified = $identityFailures === [];

        // Sans permis à contrôler ici, l'étape 1 suffit : la réservation est
        // prête pour la remise. Sinon, elle reste en attente jusqu'au contrôle
        // du permis, juste avant la remise des clés.
        $documentStatus = $identityVerified
            ? ($booking->with_driver ? DocumentVerificationStatus::NonRequis : DocumentVerificationStatus::EnAttente)
            : DocumentVerificationStatus::NonConforme;

        $booking->update([
            'document_verification_status' => $documentStatus,
            'identity_original_verified' => $identityOriginalVerified,
            'identity_name_matches' => $identityNameMatches,
            'customer_age_eligible' => $customerAgeEligible,
            'document_verification_note' => filled($data['verification_note'] ?? null)
                ? $data['verification_note']
                : null,
            'documents_verified_at' => now(),
            'documents_verified_by' => $request->user()->id,
        ]);

        $identityDocument = $booking->documents()
            ->where('type', DocumentType::IdentityCard->value)
            ->first();

        $identityDocument?->update([
            'status' => $identityOriginalVerified ? DocumentStatus::Presente : DocumentStatus::ARelever,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if (! $identityVerified) {
            return back()->with('error', __('agent.document_check_failed'));
        }

        return redirect()->route('agent.pickup.condition', $booking);
    }

    /** Étape 2 — état des lieux : photos et kilométrage. */
    public function conditionReport(Booking $booking): View
    {
        return view('agent.pickup.condition', [
            'booking' => $booking->load(['vehicle.category', 'user']),
        ]);
    }

    public function storeConditionReport(StorePickupConditionReportRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        // Les photos ne sont demandées que si l'agent a constaté un dommage :
        // une voiture intacte n'a rien à prouver, une rayure si.
        foreach ($data['photos'] ?? [] as $index => $photo) {
            $booking->addMedia($photo)
                ->preservingOriginal()
                ->usingFileName($booking->reference.'-retrait-'.$index.'.jpg')
                ->toMediaCollection(Booking::MEDIA_CONDITION_REPORT);
        }

        $booking->update([
            'mileage_at_pickup' => $data['mileage'],
            'fuel_level_pickup' => $data['fuel_level'],
            'pickup_notes' => $request->boolean('damage_present')
                ? ($data['damage_reported'] ?? null)
                : null,
        ]);

        $booking->vehicle?->update(['mileage' => $data['mileage']]);

        return redirect()->route('agent.pickup.payment', $booking);
    }

    /** Étape 3 — encaissement de la location. */
    public function payment(Booking $booking): View
    {
        return view('agent.pickup.payment', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function storePayment(StorePickupPaymentRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $expected = (int) $booking->price_total;
        $received = (int) $data['amount'];

        if ($received < $expected) {
            return back()->withErrors(['amount' => __('agent.amount_too_low', [
                'expected' => app(SettingsService::class)->money($expected),
            ])])->withInput();
        }

        Payment::query()->create([
            'booking_id' => $booking->id,
            'kind' => PaymentKind::Rental,
            'method' => $data['method'],
            'amount' => $received,
            'reference' => $data['reference'] ?? null,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return redirect()->route('agent.pickup.deposit', $booking);
    }

    /** Étape 4 — caution, sur un reçu distinct. */
    public function deposit(Booking $booking): View
    {
        return view('agent.pickup.deposit', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
            'methods' => PaymentMethod::depositMethods(),
        ]);
    }

    public function storeDeposit(StorePickupDepositRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        Payment::query()->create([
            'booking_id' => $booking->id,
            'kind' => PaymentKind::Deposit,
            'method' => $data['method'],
            'amount' => (int) $data['amount'],
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return redirect()->route('agent.pickup.contract', $booking);
    }

    /** Étape 5 — contrat, affiché sur la tablette, signé au doigt. */
    public function contract(Booking $booking): View
    {
        $settings = app(SettingsService::class);

        return view('agent.pickup.contract', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
            'speedLimit' => $settings->int('booking.speed_limit_kmh'),
        ]);
    }

    public function signContract(SignPickupContractRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $booking->addMediaFromString($data['signature'])
            ->usingFileName($booking->reference.'-signature.svg')
            ->toMediaCollection(Booking::MEDIA_SIGNATURE);

        return redirect()->route('agent.pickup.keys', $booking);
    }

    /**
     * Étape 6 — contrôle du permis, photo et remise des clés.
     *
     * C'est le dernier geste : la caissière vérifie le permis original du
     * locataire, le photographie, puis remet les clés. L'enregistrement de la
     * photo fait passer la réservation en « en cours ».
     */
    public function keys(Booking $booking): View
    {
        $licenseDocument = $booking->documents()
            ->where('type', DocumentType::DriverLicense->value)
            ->latest('id')
            ->first();

        return view('agent.pickup.keys', [
            'booking' => $booking->load(['vehicle.category', 'user']),
            'identityReady' => $booking->hasVerifiedIdentity(),
            'needsLicense' => ! $booking->with_driver,
            'licenseDocument' => $licenseDocument,
            'hasLicenseCopy' => $booking->hasDriverLicenseCopy(),
            'minimumLicenseYears' => app(SettingsService::class)->int('booking.min_license_years'),
        ]);
    }

    public function complete(HandoverBookingRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        if (! $booking->hasVerifiedIdentity()) {
            return back()->with('error', __('agent.handover_blocked'));
        }

        if (! $booking->with_driver) {
            $licenseFailures = $this->licenseFailures($booking, $request, $data);

            if ($licenseFailures !== []) {
                $booking->update([
                    'document_verification_status' => DocumentVerificationStatus::NonConforme,
                    'driver_license_original_verified' => $request->boolean('driver_license_original_verified'),
                    'driver_license_category_authorized' => false,
                    'documents_verified_at' => now(),
                    'documents_verified_by' => $request->user()->id,
                ]);

                return back()
                    ->withInput()
                    ->with('error', __('agent.license_check_failed', [
                        'reasons' => implode(' ', $licenseFailures),
                    ]));
            }

            // Le permis peut déjà avoir été joint au dossier par un collègue :
            // la photo n'est alors exigée que si elle manque.
            if ($request->hasFile('license_copy')) {
                $this->storeLicenseCopy($booking, $request);
            }

            $booking->update([
                'driver_license_original_verified' => true,
                'driver_license_category_authorized' => true,
                'driver_license_category' => trim($data['driver_license_category']),
                'driver_license_issued_at' => $data['driver_license_issued_at'],
                'driver_license_expires_at' => $data['driver_license_expires_at'],
                'document_verification_status' => DocumentVerificationStatus::Conforme,
                'documents_verified_at' => now(),
                'documents_verified_by' => $request->user()->id,
            ]);
        }

        try {
            $this->bookings->start(
                $booking,
                $request->user(),
                $booking->mileage_at_pickup,
                $booking->fuel_level_pickup,
            );
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('agent.pickup.index')
            ->with('status', __('agent.pickup_done', ['ref' => $booking->reference]));
    }

    /**
     * Enregistre la photo du permis, en remplaçant la précédente.
     *
     * Le fichier est stocké sur le disque privé (nom aléatoire, EXIF
     * supprimés) et le consentement du client est horodaté au même moment.
     */
    private function storeLicenseCopy(Booking $booking, HandoverBookingRequest $request): void
    {
        $stored = app(DocumentStorageService::class)->store(
            $request->file('license_copy'),
            'originaux/'.$booking->reference,
            'license_copy',
        );

        // Une seule photo de permis par réservation : la plus récente.
        $booking->documents()
            ->where('type', DocumentType::DriverLicense->value)
            ->get()
            ->each(fn (CustomerDocument $document) => $document->delete());

        $booking->documents()->create([
            'user_id' => $booking->user_id,
            'type' => DocumentType::DriverLicense,
            'status' => DocumentStatus::Conforme,
            'disk' => $stored->disk,
            'path' => $stored->path,
            'original_filename' => $stored->originalFilename,
            'mime_type' => $stored->mimeType,
            'size_kb' => $stored->sizeKb,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $booking->update(['driver_license_copy_consent_at' => now()]);
    }

    /**
     * Motifs de non-conformité du permis, dans l'ordre où la caissière les
     * vérifie. Une liste vide vaut permis conforme.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function licenseFailures(Booking $booking, HandoverBookingRequest $request, array $data): array
    {
        $settings = app(SettingsService::class);
        $failures = [];

        if (! $request->boolean('driver_license_original_verified')) {
            $failures[] = __('agent.verification_failures.license_not_seen');
        }

        $issuedAt = filled($data['driver_license_issued_at'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['driver_license_issued_at'])
            : null;

        if ($issuedAt === null || $issuedAt->copy()
            ->addYears($settings->int('booking.min_license_years'))
            ->isAfter(today())) {
            $failures[] = __('agent.verification_failures.license_tenure');
        }

        $expiresAt = filled($data['driver_license_expires_at'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['driver_license_expires_at'])
            : null;

        if ($expiresAt === null || $expiresAt->isBefore($booking->ends_at->toDateString())) {
            $failures[] = __('agent.verification_failures.license_expired');
        }

        if (trim($data['driver_license_category'] ?? '') === ''
            || ! $request->boolean('driver_license_category_authorized')) {
            $failures[] = __('agent.verification_failures.license_category');
        }

        return $failures;
    }
}
