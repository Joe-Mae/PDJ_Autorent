<?php

namespace App\Http\Controllers\Account;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\PricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly PricingService $pricing,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('statut')->toString() ?: null;

        return view('account.bookings', [
            'bookings' => $request->user()->bookings()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->with(['vehicle.category', 'payments'])
                ->paginate(10)
                ->withQueryString(),
            'status' => $status,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function show(Request $request, string $locale, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return view('account.booking-show', [
            'booking' => $booking->load(['vehicle.category', 'documents', 'payments', 'review']),
            'quote' => $this->pricing->quoteForBooking($booking),
        ]);
    }

    /** Annulation en un clic, accessible directement depuis la liste. */
    public function cancel(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        try {
            $this->bookings->cancel($booking, $request->user(), 'client');
            $this->notifications->sendBookingCancelled($booking);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('booking.cancelled_ok'));
    }
}
