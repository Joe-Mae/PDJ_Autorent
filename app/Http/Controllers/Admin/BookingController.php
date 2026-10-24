<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverOnlyBookingStatus;
use App\Enums\PaymentKind;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverOnlyBooking;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Suivi des réservations côté agence.
 *
 * La confirmation est volontairement manuelle : c'est le seul garde-fou fiable
 * contre les réservations fantômes. Chaque action trace qui l'a faite et
 * prévient le client sur WhatsApp dans la foulée.
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.bookings.index', [
            'bookings' => Booking::query()
                ->when($request->string('statut')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->when($request->string('prestation')->toString(), function ($q, $type) {
                    match ($type) {
                        'sans_chauffeur' => $q->where('with_driver', false),
                        'avec_chauffeur' => $q->where('with_driver', true),
                        default => $q,
                    };
                })
                ->when($request->date('du'), fn ($q, $d) => $q->whereDate('starts_at', '>=', $d))
                ->when($request->date('au'), fn ($q, $d) => $q->whereDate('starts_at', '<=', $d))
                ->when($request->string('q')->toString(), function ($q, $term) {
                    $q->where(fn ($sub) => $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('customer_phone', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhere('customer_email', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")));
                })
                ->when($request->integer('vehicule'), fn ($q, $vehicleId) => $q->where('vehicle_id', $vehicleId))
                ->when($request->integer('chauffeur'), fn ($q, $driverId) => $q->where('driver_id', $driverId))
                ->orderByDesc('starts_at')
                ->with(['vehicle.category', 'user', 'driver'])
                // Le statut d'une réservation et celui de son paiement sont deux
                // choses : une réservation confirmée peut n'avoir versé qu'un
                // acompte. On charge donc le total encaissé en une requête, pas
                // une par ligne du tableau.
                ->withSum(
                    ['payments as rental_paid' => fn ($query) => $query->where('kind', PaymentKind::Rental->value)],
                    'amount'
                )
                ->paginate(20)
                ->withQueryString(),
            'statuses' => BookingStatus::cases(),
            'vehicles' => Vehicle::query()->orderBy('brand')->orderBy('model')->get(),
            'filters' => $request->only(['statut', 'prestation', 'du', 'au', 'q', 'vehicule']),
            'driverOnlyPending' => DriverOnlyBooking::query()
                ->where('status', DriverOnlyBookingStatus::EnAttente->value)
                ->count(),
            'activeDriver' => $request->integer('chauffeur')
                ? Driver::query()->find($request->integer('chauffeur'))
                : null,
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking->load(['vehicle.category', 'user', 'documents', 'payments', 'driver', 'review', 'documentsVerifiedBy']),
            'drivers' => Driver::query()->available()->get(),
        ]);
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        try {
            $this->bookings->confirm($booking, $request->user());
            $this->notifications->sendBookingConfirmed($booking, $booking->customer_email ?? $booking->user?->email);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('admin.booking_confirmed', ['ref' => $booking->reference]));
    }

    public function refuse(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'max:255'],
        ], [
            'motif.required' => __('admin.reason_required'),
        ]);

        try {
            $this->bookings->refuse($booking, $request->user(), $data['motif']);
            $this->notifications->sendBookingRefused($booking, $data['motif'], $booking->customer_email ?? $booking->user?->email);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('admin.booking_refused', ['ref' => $booking->reference]));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        try {
            $this->bookings->cancel($booking, $request->user(), 'agence');
            $this->notifications->sendBookingCancelled($booking, $booking->customer_email ?? $booking->user?->email);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('admin.booking_cancelled', ['ref' => $booking->reference]));
    }

    /** Re-dater une réservation : le prix est recalculé et réécrit. */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'du' => ['required', 'date'],
            'au' => ['required', 'date', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer', 'between:6,22'],
            'heure_retour' => ['required', 'integer', 'between:6,22'],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ]);

        $period = BookingPeriod::make(
            $data['du'],
            $data['au'],
            (int) $data['heure_depart'],
            (int) $data['heure_retour'],
        );

        try {
            $this->bookings->reprice($booking, $period, (bool) ($data['avec_chauffeur'] ?? false));
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('admin.booking_updated'));
    }

    /** Le chauffeur est affecté au retrait, pas à la réservation. */
    public function assignDriver(Request $request, Booking $booking, Driver $driver): RedirectResponse
    {
        abort_unless($booking->with_driver, 422);

        $booking->update(['driver_id' => $driver->id]);

        return back()->with('status', __('admin.driver_assigned', [
            'name' => $driver->displayName(),
        ]));
    }
}
