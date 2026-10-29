<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\Site\StoreDriverOnlyBookingRequest;
use App\Services\DriverOnlyBookingService;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class DriverOnlyBookingController extends Controller
{
    public function __construct(
        private readonly DriverOnlyBookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
    ) {}

    public function store(StoreDriverOnlyBookingRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();

        /** @var UploadedFile $identityDocument */
        $identityDocument = $request->file('identity_document');

        try {
            $booking = $this->bookings->create($data, $identityDocument);
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->notifications->sendNewDriverOnlyRequest($booking);

        return redirect()
            ->route('page.driver', ['locale' => $locale])
            ->with('driver_only_booking_reference', $booking->reference)
            ->with('driver_only_booking_total', $booking->price_total)
            ->with('driver_only_booking_daily_price', $booking->daily_price)
            ->with('driver_only_booking_days', $booking->days);
    }
}
