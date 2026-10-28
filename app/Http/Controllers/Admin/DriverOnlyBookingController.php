<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverOnlyBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDriverRequest;
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

    public function index(Request $request): View
    {
        return view('admin.driver-only-bookings.index', [
            'bookings' => DriverOnlyBooking::query()
                ->with('driver')
                ->when($request->string('statut')->toString(), fn ($q, $status) => $q->where('status', $status))
                ->when($request->integer('chauffeur'), fn ($q, $driverId) => $q->where('driver_id', $driverId))
                ->when($request->string('q')->toString(), function ($q, $term) {
                    $q->where(fn ($sub) => $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
                })
                ->orderByRaw("CASE status WHEN 'en_attente' THEN 0 WHEN 'confirmee' THEN 1 WHEN 'refusee' THEN 2 ELSE 3 END")
                ->orderBy('starts_at')
                ->paginate(20)
                ->withQueryString(),
            'drivers' => Driver::query()->available()->orderBy('first_name')->orderBy('last_name')->get(),
            'allDrivers' => Driver::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'statuses' => DriverOnlyBookingStatus::cases(),
            'status' => $request->string('statut')->toString(),
            'counts' => [
                'pending' => DriverOnlyBooking::query()
                    ->where('status', DriverOnlyBookingStatus::EnAttente->value)
                    ->count(),
                'confirmed' => DriverOnlyBooking::query()
                    ->where('status', DriverOnlyBookingStatus::Confirmee->value)
                    ->count(),
                'today' => DriverOnlyBooking::query()
                    ->whereDate('starts_at', today())
                    ->whereIn('status', [
                        DriverOnlyBookingStatus::EnAttente->value,
                        DriverOnlyBookingStatus::Confirmee->value,
                    ])
                    ->count(),
            ],
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
            abort_unless($booking->status->canBeRefused(), 422);

            $booking->update([
                'status' => DriverOnlyBookingStatus::Refusee,
                'driver_id' => null,
            ]);
        });

        return back()->with('status', __('driver.solo_refused'));
    }

    /** Le chauffeur est parti : la prestation est en cours, il reste bloqué. */
    public function start(DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        return $this->transition(
            $driverOnlyBooking,
            DriverOnlyBookingStatus::EnCours,
            'driver.solo_started',
        );
    }

    /** Prestation terminée : le chauffeur redevient disponible. */
    public function finish(DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        return $this->transition(
            $driverOnlyBooking,
            DriverOnlyBookingStatus::Terminee,
            'driver.solo_finished',
        );
    }

    /** Annulation avec motif : la fiche garde toujours l'explication. */
    public function cancel(Request $request, DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'max:255'],
        ]);

        $cancelled = DB::transaction(function () use ($driverOnlyBooking, $data): bool {
            $booking = DriverOnlyBooking::query()->whereKey($driverOnlyBooking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->status->canBeCancelled()) {
                return false;
            }

            $booking->update([
                'status' => DriverOnlyBookingStatus::Annulee,
                'status_reason' => $data['motif'],
            ]);

            return true;
        });

        return $cancelled
            ? back()->with('status', __('driver.solo_cancelled'))
            : back()->with('error', __('driver.solo_invalid_transition'));
    }

    /**
     * Change le statut d'une demande dont la transition est autorisée.
     *
     * Le verrou de ligne évite que deux agents cliquent « démarrer » et
     * « annuler » en même temps sur la même demande.
     */
    private function transition(
        DriverOnlyBooking $driverOnlyBooking,
        DriverOnlyBookingStatus $target,
        string $message,
    ): RedirectResponse {
        $changed = DB::transaction(function () use ($driverOnlyBooking, $target): bool {
            $booking = DriverOnlyBooking::query()->whereKey($driverOnlyBooking->id)->lockForUpdate()->firstOrFail();

            $allowed = match ($target) {
                DriverOnlyBookingStatus::EnCours => $booking->status->canStart(),
                DriverOnlyBookingStatus::Terminee => $booking->status->canFinish(),
                default => false,
            };

            if (! $allowed) {
                return false;
            }

            $booking->update(['status' => $target]);

            return true;
        });

        return $changed
            ? back()->with('status', __($message))
            : back()->with('error', __('driver.solo_invalid_transition'));
    }

    public function assign(AssignDriverRequest $request, DriverOnlyBooking $driverOnlyBooking): RedirectResponse
    {
        $data = $request->validated();

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
