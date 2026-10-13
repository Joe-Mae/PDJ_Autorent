<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverOnlyBookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverOnlyBooking;
use App\Services\AvailabilityService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverOnlyBookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        return view('admin.driver-only-bookings.index', [
            'bookings' => DriverOnlyBooking::query()
                ->with('driver')
                ->orderByRaw("CASE status WHEN 'en_attente' THEN 0 WHEN 'confirmee' THEN 1 WHEN 'refusee' THEN 2 ELSE 3 END")
                ->orderBy('starts_at')
                ->paginate(20),
            'drivers' => Driver::query()->available()->orderBy('first_name')->orderBy('last_name')->get(),
        ]);
    }

    public function confirm(DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        DB::transaction(function () use ($driverOnlyBooking): void {
            $booking = DriverOnlyBooking::query()->whereKey($driverOnlyBooking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === DriverOnlyBookingStatus::EnAttente, 422);

            $booking->update(['status' => DriverOnlyBookingStatus::Confirmee]);
        });

        return back()->with('status', __('driver.solo_confirmed'));
    }

    public function refuse(DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        DB::transaction(function () use ($driverOnlyBooking): void {
            $booking = DriverOnlyBooking::query()->whereKey($driverOnlyBooking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status->isActive(), 422);

            $booking->update([
                'status' => DriverOnlyBookingStatus::Refusee,
                'driver_id' => null,
            ]);
        });

        return back()->with('status', __('driver.solo_refused'));
    }

    public function assign(Request $request, DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        $data = $request->validate(['driver' => ['required', 'integer', 'exists:drivers,id']]);

        $assigned = DB::transaction(function () use ($driverOnlyBooking, $data): bool {
            $this->availability->lockAvailableDrivers();
            $booking = DriverOnlyBooking::query()->whereKey($driverOnlyBooking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === DriverOnlyBookingStatus::Confirmee, 422);

            $period = $this->period($booking);
            $availableDrivers = $this->availability->freeDriverRecords($period, $booking->id);
            $driver = Driver::query()->available()->whereKey($data['driver'])->first();

            if (
                $driver === null
                || $this->availability->freeDriverSlots($period, $booking->id) === 0
                || ! $availableDrivers->contains('id', $driver->id)
            ) {
                return false;
            }

            $booking->update(['driver_id' => $driver->id]);

            return true;
        });

        if (! $assigned) {
            return back()->with('error', __('driver.none_available'));
        }

        return back()->with('status', __('driver.solo_assigned'));
    }

    private function period(DriverOnlyBooking $booking): BookingPeriod
    {
        return BookingPeriod::fromDates(
            $booking->starts_at->toImmutable(),
            $booking->ends_at->toImmutable(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }
}
