<?php

namespace App\Http\Controllers\Agent;

use App\Enums\PaymentKind;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Rapport de caisse du jour.
 *
 * Trois totaux, jamais mélangés : ce qui est entré (location,cautions), ce qui
 * est ressorti (cautions rendues), et ce qui reste en caution chez nous. Le
 * dernier chiffre est le plus important — c'est l'argent des clients.
 */
class CashReportController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->date('jour') ?? today();
        $payments = $this->paymentsFor($date);

        return view('agent.cash.index', [
            'date' => $date,
            'rental' => (int) $payments->where('kind', PaymentKind::Rental)->sum('amount'),
            'deposits' => (int) $payments->where('kind', PaymentKind::Deposit)->sum('amount'),
            'extras' => (int) $payments->whereIn('kind', [PaymentKind::LateFee, PaymentKind::Fuel, PaymentKind::Damage])->sum('amount'),
            'refunds' => (int) $payments->where('kind', PaymentKind::DepositRefund)->sum('amount'),
            'deducted' => (int) $payments->where('kind', PaymentKind::DepositRefund)->sum('deducted_amount'),
            'byMethod' => $payments->whereIn('kind', [PaymentKind::Rental, PaymentKind::Deposit])->groupBy('method')->map->sum('amount'),
            'payments' => $payments->sortByDesc('recorded_at'),
        ]);
    }

    /** Clôture : on fige le total et on trace qui a fermé. */
    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'counted' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $date = today();
        $expected = (int) Payment::query()
            ->whereDate('recorded_at', $date)
            ->whereIn('kind', [PaymentKind::Rental, PaymentKind::Deposit])
            ->sum('amount');

        $gap = $data['counted'] - $expected;

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'cash.closed',
            'auditable_type' => Payment::class,
            'description' => json_encode([
                'date' => $date->toDateString(),
                'attendu' => $expected,
                'compte' => $data['counted'],
                'ecart' => $gap,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return back()->with('status', $gap === 0
            ? __('agent.cash_closed_ok')
            : __('agent.cash_closed_gap', ['gap' => app(SettingsService::class)->money(abs($gap))]));
    }

    private function paymentsFor($date)
    {
        return Payment::query()
            ->whereDate('recorded_at', $date)
            ->with('booking.vehicle')
            ->get();
    }
}
