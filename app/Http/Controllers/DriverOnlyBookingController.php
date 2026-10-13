<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Services\DriverOnlyBookingService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriverOnlyBookingController extends Controller
{
    public function __construct(
        private readonly DriverOnlyBookingService $bookings,
        private readonly SettingsService $settings,
    ) {}

    public function store(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,30}$/'],
            'du' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
        ]);

        try {
            $booking = $this->bookings->create($data);
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('page.driver', ['locale' => $locale])
            ->with('driver_only_booking_reference', $booking->reference)
            ->with('driver_only_booking_total', $booking->price_total)
            ->with('driver_only_booking_daily_price', $booking->daily_price)
            ->with('driver_only_booking_days', $booking->days);
    }
}
