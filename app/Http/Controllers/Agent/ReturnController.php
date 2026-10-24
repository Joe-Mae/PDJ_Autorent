<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RefundDepositRequest;
use App\Http\Requests\Agent\StoreReturnRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Retour du véhicule.
 *
 * Trois points de friction, et le parcours est construit autour d'eux :
 * le kilométrage, le carburant, la caution. Le carburant est obligatoire — sans
 * lui, le retour se termine en litige au lieu d'un reçu.
 */
class ReturnController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        return view('agent.return.index', [
            'onRoad' => Booking::query()
                ->where('status', BookingStatus::EnCours->value)
                ->with(['vehicle.category', 'user'])
                ->orderBy('ends_at')
                ->get(),
            'late' => Booking::query()
                ->where('status', BookingStatus::EnCours->value)
                ->where('ends_at', '<', now())
                ->with(['vehicle', 'user'])
                ->get(),
        ]);
    }

    public function control(Booking $booking): View
    {
        return view('agent.return.control', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
        ]);
    }

    public function store(StoreReturnRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data['photos'] as $index => $photo) {
            $booking->addMedia($photo)
                ->preservingOriginal()
                ->usingFileName($booking->reference.'-retour-'.$index.'.jpg')
                ->toMediaCollection(Vehicle::MEDIA_STATE_OF_VEHICLE, 'documents');
        }

        $booking->update([
            'mileage_at_return' => $data['mileage'],
            'fuel_level_return' => $data['fuel_level'],
            'return_notes' => $data['damage_note'] ?? null,
        ]);

        $booking->vehicle?->update(['mileage' => $data['mileage']]);

        if (filled($data['late_fee'] ?? null)) {
            Payment::query()->create([
                'booking_id' => $booking->id,
                'kind' => PaymentKind::LateFee,
                'method' => PaymentMethod::Especes,
                'amount' => (int) $data['late_fee'],
                'recorded_by' => $request->user()->id,
                'recorded_at' => now(),
                'note' => __('agent.late_fee'),
            ]);
        }

        return redirect()->route('agent.return.deposit', $booking);
    }

    /** Restitution de la caution, avec les prélèvements détaillés. */
    public function deposit(Booking $booking): View
    {
        return view('agent.return.deposit', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
        ]);
    }

    public function refundDeposit(RefundDepositRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $paid = $booking->depositPaid();
        $deducted = (int) $data['deducted_amount'];

        if ($deducted > $paid) {
            return back()->withErrors(['deducted_amount' => __('agent.deduction_above_deposit', [
                'amount' => $this->settings->money($paid),
            ])]);
        }

        if ($deducted > 0) {
            Payment::query()->create([
                'booking_id' => $booking->id,
                'kind' => PaymentKind::Damage,
                'method' => PaymentMethod::Especes,
                'amount' => $deducted,
                'recorded_by' => $request->user()->id,
                'recorded_at' => now(),
                'note' => $data['note'] ?? null,
            ]);
        }

        Payment::query()->create([
            'booking_id' => $booking->id,
            'kind' => PaymentKind::DepositRefund,
            'method' => PaymentMethod::Especes,
            'amount' => $paid - $deducted,
            'deducted_amount' => $deducted,
            'reference' => $data['note'] ?? null,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        $this->bookings->finish($booking, $request->user(), $booking->mileage_at_return, $booking->fuel_level_return);

        return redirect()
            ->route('agent.return.index')
            ->with('status', __('agent.return_done', [
                'ref' => $booking->reference,
                'amount' => $this->settings->money($paid - $deducted),
            ]));
    }
}
