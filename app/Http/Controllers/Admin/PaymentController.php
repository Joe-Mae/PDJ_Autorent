<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'kind' => ['required', Rule::in(array_column(PaymentKind::cases(), 'value'))],
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

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
    public function refundDeposit(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->kind === PaymentKind::Deposit, 422);

        $data = $request->validate([
            'deducted_amount' => ['nullable', 'integer', 'min:0', 'max:'.$payment->amount],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

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
            'amount' => number_format($refundable, 0, ' ', ' ').' F CFA',
            'deducted' => number_format($deducted, 0, ' ', ' ').' F CFA',
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
