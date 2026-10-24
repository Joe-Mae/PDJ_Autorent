<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundDepositRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Caisse de l'agence.
 *
 * Rien n'est encaissé en ligne : ces enregistrements sont saisis au guichet,
 * en espèces, mobile money ou Visa. La caution est un enregistrement séparé
 * avec son propre reçu, parce que ce n'est pas un revenu et qu'il ne doit
 * jamais être confondu avec la location dans le rapport du jour.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $day = $request->date('jour') ?? today();

        // Le reste à encaisser ne concerne que les réservations qui immobilisent
        // encore un véhicule : une annulation ne doit pas gonfler l'impayé.
        $unpaid = Booking::query()
            ->whereIn('status', [
                BookingStatus::EnAttente->value,
                BookingStatus::Confirmee->value,
                BookingStatus::EnCours->value,
            ])
            ->withSum(
                ['payments as rental_paid' => fn ($query) => $query->where('kind', PaymentKind::Rental->value)],
                'amount'
            )
            ->get(['id', 'reference', 'price_total'])
            ->map(fn (Booking $booking): array => [
                'booking' => $booking,
                'remaining' => max(0, (int) $booking->price_total - (int) $booking->rental_paid),
            ])
            ->filter(fn (array $line): bool => $line['remaining'] > 0)
            ->sortByDesc('remaining')
            ->values();

        return view('admin.payments.index', [
            'payments' => Payment::query()
                ->with(['booking.vehicle', 'recorder'])
                ->when($request->date('jour'), fn ($q, $d) => $q->whereDate('recorded_at', $d), fn ($q) => $q->whereDate('recorded_at', today()))
                ->when($request->string('kind')->toString(), fn ($q, $k) => $q->where('kind', $k))
                ->orderByDesc('recorded_at')
                ->paginate(30)
                ->withQueryString(),
            'kinds' => PaymentKind::cases(),
            'methods' => PaymentMethod::cases(),
            'day' => $day,
            'dayTotals' => [
                'rental' => Payment::query()
                    ->whereDate('recorded_at', $day)
                    ->where('kind', PaymentKind::Rental->value)
                    ->sum('amount'),
                'deposits' => Payment::query()
                    ->whereDate('recorded_at', $day)
                    ->where('kind', PaymentKind::Deposit->value)
                    ->sum('amount'),
                'refunds' => Payment::query()
                    ->whereDate('recorded_at', $day)
                    ->where('kind', PaymentKind::DepositRefund->value)
                    ->sum('amount'),
            ],
            'outstanding' => $unpaid->sum('remaining'),
            'unpaidBookings' => $unpaid->take(5),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $booking = Booking::query()->findOrFail($data['booking_id']);

        Payment::query()->create([
            ...$data,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return back()->with('status', __('admin.payment_recorded'));
    }

    /**
     * Restitution de la caution. Les prélèvements éventuels (carburant,
     * dégâts, retard) sont saisis à part : le client doit voir la ligne avant
     * de récupérer ce qui reste.
     */
    public function refundDeposit(RefundDepositRequest $request, Payment $payment): RedirectResponse
    {
        $data = $request->validated();

        $deducted = (int) ($data['deducted_amount'] ?? 0);
        $refundable = $payment->amount - $deducted;

        Payment::query()->create([
            'booking_id' => $payment->booking_id,
            'kind' => PaymentKind::DepositRefund,
            'method' => $payment->method,
            'amount' => $refundable,
            'deducted_amount' => $deducted,
            'reference' => $data['note'] ?? null,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
            'refunds_payment_id' => $payment->id,
        ]);

        return back()->with('status', __('admin.deposit_refunded', [
            'amount' => app(SettingsService::class)->money($refundable),
            'deducted' => app(SettingsService::class)->money($deducted),
        ]));
    }

    public function report(Request $request): View
    {
        $date = $request->date('jour') ?? today();

        $payments = Payment::query()
            ->whereDate('recorded_at', $date)
            ->with('booking')
            ->get();

        $byMethod = $payments
            ->whereNotNull('booking_id')
            ->filter(fn (Payment $p) => $p->kind->isCashIn())
            ->groupBy(fn (Payment $p) => $p->method->value)
            ->map(fn ($group) => $group->sum('amount'));

        return view('admin.payments.report', [
            'date' => $date,
            'rental' => (int) $payments->where('kind', PaymentKind::Rental)->sum('amount'),
            'deposits' => (int) $payments->where('kind', PaymentKind::Deposit)->sum('amount'),
            'extras' => (int) $payments->whereIn('kind', [PaymentKind::LateFee, PaymentKind::Fuel, PaymentKind::Damage])->sum('amount'),
            'refunds' => (int) $payments->where('kind', PaymentKind::DepositRefund)->sum('amount'),
            'deducted' => (int) $payments->where('kind', PaymentKind::DepositRefund)->sum('deducted_amount'),
            'byMethod' => $byMethod,
            'payments' => $payments->sortByDesc('recorded_at'),
        ]);
    }
}
