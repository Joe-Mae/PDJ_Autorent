<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
    ) {}

    public function create(Request $request, string $locale, Vehicle $vehicle): View
    {
        abort_unless($vehicle->isBookable(), 404);

        $dates = $request->validate([
            'du' => ['nullable', 'date_format:Y-m-d', 'required_with:au', 'after_or_equal:today'],
            'au' => ['nullable', 'date_format:Y-m-d', 'required_with:du', 'after_or_equal:du'],
            'heure_depart' => ['nullable', 'integer'],
            'heure_retour' => ['nullable', 'integer'],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ]);

        if (blank($dates['du'] ?? null) || blank($dates['au'] ?? null)) {
            $start = today()->addDay();
            $period = BookingPeriod::make(
                $start->toDateString(),
                $start->copy()->addDays(3)->toDateString(),
                $this->settings->openHour(),
                $this->settings->closeHour(),
                $this->settings->openHour(),
                $this->settings->closeHour(),
            );
        } else {
            $period = $this->periodFrom($dates);
            $this->validatePeriod($period);
        }

        $withDriver = (bool) ($dates['avec_chauffeur'] ?? false);

        return view('booking.guest', [
            'vehicle' => $vehicle->load('category'),
            'period' => $period,
            'quote' => $this->pricing->quote($vehicle->category, $period, $withDriver),
            'withDriver' => $withDriver,
        ]);
    }

    public function quote(Request $request, string $locale, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->isBookable(), 404);

        $data = $request->validate([
            'du' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer', 'between:'.$this->settings->openHour().','.$this->settings->closeHour()],
            'heure_retour' => ['required', 'integer', 'between:'.$this->settings->openHour().','.$this->settings->closeHour()],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ]);

        $period = $this->periodFrom($data);
        $this->validatePeriod($period);
        $quote = $this->pricing->quote($vehicle->category, $period, (bool) ($data['avec_chauffeur'] ?? false));

        return response()->json([
            'days' => $quote->days,
            'daily_price' => $quote->dailyPrice,
            'price_total' => $quote->priceTotal,
        ]);
    }

    public function store(Request $request, string $locale, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->isBookable(), 404);

        $data = $request->validate([
            'du' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer', 'between:'.$this->settings->openHour().','.$this->settings->closeHour()],
            'heure_retour' => ['required', 'integer', 'between:'.$this->settings->openHour().','.$this->settings->closeHour()],
            'avec_chauffeur' => ['nullable', 'boolean'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $period = $this->periodFrom($data);
        $this->validatePeriod($period);
        $token = Str::random(64);

        try {
            $booking = $this->bookings->create([
                'vehicle' => $vehicle,
                'period' => $period,
                'user' => null,
                'with_driver' => (bool) ($data['avec_chauffeur'] ?? false),
                'phone_verified_at' => null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'customer_comment' => $data['customer_comment'] ?? null,
                'guest_access_token_hash' => hash('sha256', $token),
            ]);
        } catch (BookingException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('booking.guest.confirmation', [
            'locale' => $locale,
            'booking' => $booking,
            'token' => $token,
        ]);
    }

    public function confirmation(string $locale, Booking $booking, string $token): View
    {
        abort_unless(
            $booking->user_id === null
                && $booking->guest_access_token_hash !== null
                && hash_equals($booking->guest_access_token_hash, hash('sha256', $token)),
            404,
        );

        return view('booking.guest-confirmation', [
            'booking' => $booking->load(['vehicle.category']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function periodFrom(array $data): BookingPeriod
    {
        return BookingPeriod::make(
            $data['du'],
            $data['au'],
            (int) ($data['heure_depart'] ?? $this->settings->openHour()),
            (int) ($data['heure_retour'] ?? $this->settings->closeHour()),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }

    private function validatePeriod(BookingPeriod $period): void
    {
        if ($period->startsAt->isPast()) {
            throw ValidationException::withMessages([
                'du' => __('booking.guest.start_must_be_future'),
            ]);
        }

        if ($period->endsAt->lessThanOrEqualTo($period->startsAt)) {
            throw ValidationException::withMessages([
                'heure_retour' => __('booking.guest.return_after_pickup'),
            ]);
        }

        if ($period->isOutsideOpeningHours()) {
            throw ValidationException::withMessages([
                'heure_depart' => __('booking.error.outside_opening_hours', [
                    'open' => $this->settings->string('agency.open_time'),
                    'close' => $this->settings->string('agency.close_time'),
                ]),
            ]);
        }
    }
}
