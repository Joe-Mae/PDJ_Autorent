<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverOnlyBookingStatus;
use App\Enums\PaymentKind;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverOnlyBooking;
use App\Models\Payment;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'periode' => ['nullable', 'in:semaine,mois,annee'],
        ]);
        $period = $filters['periode'] ?? 'mois';
        $range = $this->range($period);

        $statusCounts = Booking::query()
            ->whereBetween('starts_at', [$range['from'], $range['to']])
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rentedStatuses = [BookingStatus::EnCours->value, BookingStatus::Terminee->value];
        $topVehicles = Vehicle::query()
            ->withCount(['bookings as rentals_count' => fn (Builder $query) => $query
                ->whereIn('status', $rentedStatuses)
                ->whereBetween('starts_at', [$range['from'], $range['to']])])
            ->whereHas('bookings', fn (Builder $query) => $query
                ->whereIn('status', $rentedStatuses)
                ->whereBetween('starts_at', [$range['from'], $range['to']]))
            ->orderByDesc('rentals_count')
            ->limit(5)
            ->get();

        $actualRentalSeconds = Booking::query()
            ->whereIn('status', $rentedStatuses)
            ->whereRaw('COALESCE(started_at, starts_at) < ?', [$range['to']])
            ->whereRaw('COALESCE(ended_at, ends_at) > ?', [$range['from']])
            ->get(['started_at', 'ended_at', 'starts_at', 'ends_at', 'status'])
            ->sum(function (Booking $booking) use ($range): int {
                $start = max(
                    ($booking->started_at ?? $booking->starts_at)->getTimestamp(),
                    $range['from']->getTimestamp(),
                );
                $endAt = $booking->status === BookingStatus::EnCours
                    ? min(now()->getTimestamp(), $range['to']->getTimestamp())
                    : min(($booking->ended_at ?? $booking->ends_at)->getTimestamp(), $range['to']->getTimestamp());

                return max(0, $endAt - $start);
            });

        $elapsedSeconds = max(0, min(now()->getTimestamp(), $range['to']->getTimestamp()) - $range['from']->getTimestamp());
        $capacitySeconds = Vehicle::query()->where('is_active', true)->count() * $elapsedSeconds;
        $utilization = $capacitySeconds > 0
            ? round(($actualRentalSeconds / $capacitySeconds) * 100, 1)
            : 0;

        $outstanding = Booking::query()
            ->whereIn('status', [
                BookingStatus::EnAttente->value,
                BookingStatus::Confirmee->value,
                BookingStatus::EnCours->value,
            ])
            ->withSum(
                ['payments as rental_paid' => fn (Builder $query) => $query->where('kind', PaymentKind::Rental->value)],
                'amount'
            )
            ->get(['id', 'price_total'])
            ->sum(fn (Booking $booking): int => max(0, (int) $booking->price_total - (int) $booking->rental_paid));

        // Activité des chauffeurs : les locations avec chauffeur et les
        // demandes de chauffeur seul sont deux tables, mais un seul métier.
        $carTrips = Booking::query()
            ->whereNotNull('driver_id')
            ->whereBetween('starts_at', [$range['from'], $range['to']])
            ->selectRaw('driver_id, COUNT(*) AS total')
            ->groupBy('driver_id')
            ->pluck('total', 'driver_id');

        $soloTrips = DriverOnlyBooking::query()
            ->whereNotNull('driver_id')
            ->whereIn('status', [
                DriverOnlyBookingStatus::EnAttente->value,
                DriverOnlyBookingStatus::Confirmee->value,
            ])
            ->whereBetween('starts_at', [$range['from'], $range['to']])
            ->selectRaw('driver_id, COUNT(*) AS total')
            ->groupBy('driver_id')
            ->pluck('total', 'driver_id');

        $driverActivity = Driver::query()
            ->orderBy('first_name')
            ->get()
            ->map(fn (Driver $driver): array => [
                'driver' => $driver,
                'trips' => (int) ($carTrips[$driver->id] ?? 0) + (int) ($soloTrips[$driver->id] ?? 0),
            ])
            ->sortByDesc('trips')
            ->take(5)
            ->values();

        return view('admin.statistics.index', [
            'period' => $period,
            'range' => $range,
            'statusCounts' => $statusCounts,
            'overdueCount' => Booking::query()
                ->where('status', BookingStatus::EnCours->value)
                ->where('ends_at', '<', now())
                ->count(),
            'completedCount' => (int) ($statusCounts[BookingStatus::Terminee->value] ?? 0),
            'topVehicles' => $topVehicles,
            'utilization' => $utilization,
            'actualRentalRevenue' => Payment::query()
                ->where('kind', PaymentKind::Rental->value)
                ->whereBetween('recorded_at', [$range['from'], $range['to']])
                ->sum('amount'),
            'outstanding' => $outstanding,
            'serviceBreakdown' => [
                'car_only' => Booking::query()
                    ->where('with_driver', false)
                    ->whereBetween('starts_at', [$range['from'], $range['to']])
                    ->count(),
                'with_driver' => Booking::query()
                    ->where('with_driver', true)
                    ->whereBetween('starts_at', [$range['from'], $range['to']])
                    ->count(),
                'driver_only' => DriverOnlyBooking::query()
                    ->whereBetween('starts_at', [$range['from'], $range['to']])
                    ->count(),
            ],
            'driverActivity' => $driverActivity,
        ]);
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    private function range(string $period): array
    {
        $today = now()->toImmutable();

        return match ($period) {
            'semaine' => [
                'from' => $today->startOfWeek(),
                'to' => $today->endOfWeek(),
            ],
            'annee' => [
                'from' => $today->startOfYear(),
                'to' => $today->endOfYear(),
            ],
            default => [
                'from' => $today->startOfMonth(),
                'to' => $today->endOfMonth(),
            ],
        };
    }
}
