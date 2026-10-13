<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            ->whereIn('status', [BookingStatus::EnAttente->value, BookingStatus::Confirmee->value])
            ->firstOrFail();

        return redirect()->route('agent.pickup.documents', $booking);
    }

    /** Étape 1 — contrôle des originaux. */
    public function documents(Booking $booking): View
    {
        return view('agent.pickup.documents', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents']),
        ]);
    }

    public function storeDocuments(Request $request, Booking $booking): RedirectResponse
    {
        $booking->load('documents');

        $required = $request->boolean('with_driver')
            ? [DocumentType::IdentityCard]
            : [DocumentType::IdentityCard, DocumentType::DriverLicense];

        $seen = (array) $request->input('originaux', []);

        foreach ($required as $type) {
            $document = $booking->documents->firstWhere('type', $type);

            if ($document === null) {
                continue;
            }

            $document->update([
                'status' => in_array($type->value, $seen, true)
                    ? DocumentStatus::Presente
                    : DocumentStatus::ARelever,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        // Photo de l'original, pour le dossier en cas de litige ultérieur.
        if ($request->hasFile('photo_origine')) {
            $path = $request->file('photo_origine')->storeAs(
                'originaux/'.$booking->reference,
                Str::random(30).'.jpg',
                disk: 'documents',
            );

            CustomerDocument::query()->firstOrCreate(
                ['booking_id' => $booking->id, 'type' => DocumentType::IdentityCard],
                ['user_id' => $booking->user_id ?? 0, 'status' => DocumentStatus::Presente, 'disk' => 'documents', 'path' => $path, 'mime_type' => 'image/jpeg', 'size_kb' => 1],
            );
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
        ]);
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookings->start(
            $booking,
            $request->user(),
            $booking->mileage_at_pickup,
            $booking->fuel_level_pickup,
        );

        return redirect()
            ->route('agent.pickup.index')
            ->with('status', __('agent.pickup_done', ['ref' => $booking->reference]));
    }
}
