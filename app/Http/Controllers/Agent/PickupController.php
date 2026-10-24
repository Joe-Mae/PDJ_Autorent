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
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
    public function pick(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:12'],
        ]);

        $booking = Booking::query()
            ->where('reference', strtoupper($data['reference']))
            ->where('status', BookingStatus::Confirmee->value)
            ->firstOrFail();

        return redirect()->route('agent.pickup.documents', $booking);
    }

    /** Étape 1 — contrôle des originaux. */
    public function documents(Booking $booking): View
    {
        abort_unless($booking->status === BookingStatus::Confirmee, 422);

        return view('agent.pickup.documents', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
            'minimumLicenseYears' => app(SettingsService::class)->int('booking.min_license_years'),
            'minimumAge' => app(SettingsService::class)->int('booking.min_age'),
        ]);
    }

    public function storeDocuments(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === BookingStatus::Confirmee, 422);

        $data = $request->validate([
            'identity_original_verified' => ['nullable', 'boolean'],
            'identity_name_matches' => ['nullable', 'boolean'],
            'customer_age_eligible' => ['nullable', 'boolean'],
            'driver_license_original_verified' => ['nullable', 'boolean'],
            'driver_license_category_authorized' => ['nullable', 'boolean'],
            'driver_license_category' => ['nullable', 'string', 'max:30'],
            'driver_license_issued_at' => ['nullable', 'date_format:Y-m-d'],
            'driver_license_expires_at' => ['nullable', 'date_format:Y-m-d'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
            'license_copy_consent' => ['sometimes', 'accepted'],
            'license_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('license_copy') && ! $request->boolean('license_copy_consent')) {
            throw ValidationException::withMessages([
                'license_copy_consent' => __('agent.license_copy_consent_required'),
            ]);
        }

        if ($booking->with_driver && $request->hasFile('license_copy')) {
            throw ValidationException::withMessages([
                'license_copy' => __('agent.license_copy_not_applicable'),
            ]);
        }

        $identityOriginalVerified = $request->boolean('identity_original_verified');
        $identityNameMatches = $request->boolean('identity_name_matches');
        $customerAgeEligible = $request->boolean('customer_age_eligible');
        $licenseOriginalVerified = ! $booking->with_driver
            && $request->boolean('driver_license_original_verified');
        $licenseCategory = $booking->with_driver
            ? ''
            : trim($data['driver_license_category'] ?? '');
        $licenseCategoryAuthorized = ! $booking->with_driver
            && $request->boolean('driver_license_category_authorized');
        $licenseIssuedAt = ! $booking->with_driver && filled($data['driver_license_issued_at'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['driver_license_issued_at'])
            : null;
        $licenseExpiresAt = ! $booking->with_driver && filled($data['driver_license_expires_at'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['driver_license_expires_at'])
            : null;
        $identityFailures = [];
        $licenseFailures = [];

        if (! $identityOriginalVerified) {
            $identityFailures[] = __('agent.verification_failures.identity_not_seen');
        }

        if (! $identityNameMatches) {
            $identityFailures[] = __('agent.verification_failures.identity_name_mismatch');
        }

        if (! $customerAgeEligible) {
            $identityFailures[] = __('agent.verification_failures.age_requirement');
        }

        if (! $booking->with_driver) {
            if (! $licenseOriginalVerified) {
                $licenseFailures[] = __('agent.verification_failures.license_not_seen');
            }

            if ($licenseIssuedAt === null || $licenseIssuedAt->copy()
                ->addYears(app(SettingsService::class)->int('booking.min_license_years'))
                ->isAfter(today())) {
                $licenseFailures[] = __('agent.verification_failures.license_tenure');
            }

            if ($licenseExpiresAt === null || $licenseExpiresAt->isBefore($booking->ends_at->toDateString())) {
                $licenseFailures[] = __('agent.verification_failures.license_expired');
            }

            if ($licenseCategory === '' || ! $licenseCategoryAuthorized) {
                $licenseFailures[] = __('agent.verification_failures.license_category');
            }
        }

        $documentStatus = $booking->with_driver
            ? ($identityFailures === []
                ? DocumentVerificationStatus::NonRequis
                : DocumentVerificationStatus::NonConforme)
            : ($identityFailures === [] && $licenseFailures === []
                ? DocumentVerificationStatus::Conforme
                : DocumentVerificationStatus::NonConforme);

        if ($request->hasFile('license_copy') && ! $licenseOriginalVerified) {
            throw ValidationException::withMessages([
                'license_copy' => __('agent.license_copy_requires_original'),
            ]);
        }

        $verificationNote = implode("\n", array_filter([
            ...$identityFailures,
            ...$licenseFailures,
            $data['verification_note'] ?? null,
        ]));

        $booking->update([
            'document_verification_status' => $documentStatus,
            'identity_original_verified' => $identityOriginalVerified,
            'identity_name_matches' => $identityNameMatches,
            'customer_age_eligible' => $customerAgeEligible,
            'driver_license_original_verified' => $licenseOriginalVerified,
            'driver_license_category_authorized' => $licenseCategoryAuthorized,
            'driver_license_category' => $licenseCategory !== '' ? $licenseCategory : null,
            'driver_license_issued_at' => $licenseIssuedAt?->toDateString(),
            'driver_license_expires_at' => $licenseExpiresAt?->toDateString(),
            'document_verification_note' => $verificationNote !== '' ? $verificationNote : null,
            'documents_verified_at' => now(),
            'documents_verified_by' => $request->user()->id,
            'driver_license_copy_consent_at' => $request->hasFile('license_copy')
                ? now()
                : $booking->driver_license_copy_consent_at,
        ]);

        $booking->load('documents');

        foreach (DocumentType::requiredAtPickup($booking->with_driver) as $type) {
            $document = $booking->documents->firstWhere('type', $type);

            if ($document !== null) {
                $document->update([
                    'status' => match ($type) {
                        DocumentType::IdentityCard => $identityOriginalVerified
                            ? DocumentStatus::Presente
                            : DocumentStatus::ARelever,
                        DocumentType::DriverLicense => $licenseOriginalVerified && $documentStatus === DocumentVerificationStatus::Conforme
                            ? DocumentStatus::Conforme
                            : DocumentStatus::ARelever,
                        default => $document->status,
                    },
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            }
        }

        if ($request->hasFile('license_copy')) {
            $file = $request->file('license_copy');
            $path = $file->storeAs(
                'originaux/'.$booking->reference,
                Str::random(40).'.'.$file->getClientOriginalExtension(),
                'documents',
            );

            $booking->documents()
                ->where('type', DocumentType::DriverLicense->value)
                ->delete();

            CustomerDocument::query()->create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => DocumentType::DriverLicense,
                'status' => $licenseFailures === [] ? DocumentStatus::Conforme : DocumentStatus::NonConforme,
                'disk' => 'documents',
                'path' => $path,
                'original_filename' => Str::limit($file->getClientOriginalName(), 120, ''),
                'mime_type' => $file->getMimeType(),
                'size_kb' => (int) ceil($file->getSize() / 1024),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        if (! $booking->refresh()->isReadyForHandover()) {
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

    public function storeConditionReport(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['required', Rule::in(['quarter', 'half', 'three_quarters', 'full'])],
            'damage_reported' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required', 'array', 'min:4', 'max:10'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],
        ], [
            'photos.min' => __('agent.at_least_photos', ['count' => 4]),
        ]);

        foreach ($data['photos'] as $index => $photo) {
            $booking->addMedia($photo)
                ->preservingOriginal()
                ->usingFileName($booking->reference.'-retrait-'.$index.'.jpg')
                ->toMediaCollection(Vehicle::MEDIA_STATE_OF_VEHICLE, 'documents');
        }

        $booking->update([
            'mileage_at_pickup' => $data['mileage'],
            'fuel_level_pickup' => $data['fuel_level'],
            'pickup_notes' => $data['damage_reported'] ?? null,
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

    public function storePayment(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $expected = (int) $booking->price_total;
        $received = (int) $data['amount'];

        if ($received < $expected) {
            return back()->withErrors(['amount' => __('agent.amount_too_low', [
                'expected' => number_format($expected, 0, ' ', ' ').' F CFA',
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

    public function storeDeposit(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(array_column(PaymentMethod::depositMethods(), 'value'))],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

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

    public function signContract(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:255'],
            'accepted_conditions' => ['required', 'accepted'],
        ]);

        $booking->addMediaFromString($data['signature'])
            ->usingFileName($booking->reference.'-signature.svg')
            ->toMediaCollection('signature', 'documents');

        return redirect()->route('agent.pickup.keys', $booking);
    }

    /** Étape 6 — remise des clés : c'est ici que la location commence. */
    public function keys(Booking $booking): View
    {
        return view('agent.pickup.keys', [
            'booking' => $booking->load(['vehicle.category', 'user']),
            'handoverReady' => $booking->isReadyForHandover(),
        ]);
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
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
}
