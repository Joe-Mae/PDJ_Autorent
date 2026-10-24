<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\AvailabilityService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Chauffeurs.
 *
 * Un chauffeur indisponible ne disparaît pas de la liste : il passe en
 * « indisponible » et le site cesse de proposer l'option. Le tableau montre en
 * plus le nombre de places restantes sur les 14 prochains jours, parce que
 * c'est la question que l'agent se pose quand un client téléphone.
 */
class DriverController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        $period = BookingPeriod::make(
            today()->toDateString(),
            today()->addDays(14)->toDateString(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );

        return view('admin.drivers.index', [
            'drivers' => Driver::query()
                ->withCount([
                    'bookings as active_bookings_count' => fn ($query) => $query->active(),
                    'bookings as current_bookings_count' => fn ($query) => $query
                        ->active()
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>', now()),
                ])
                ->when($request->string('q')->toString(), function ($q, $term) {
                    $q->where(fn ($sub) => $sub->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
                })
                ->when($request->string('statut')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->orderBy('status')
                ->orderBy('first_name')
                ->get(),
            'statuses' => DriverStatus::cases(),
            'status' => $request->string('statut')->toString(),
            'freeSlots' => $this->availability->freeDriverSlots($period),
            'vehicleDailyPrice' => $this->settings->int('driver.block_price'),
        ]);
    }

    public function create(): View
    {
        return view('admin.drivers.form', ['driver' => new Driver(['status' => DriverStatus::Actif])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Driver::query()->create($this->validated($request));

        return redirect()->route('admin.drivers.index')->with('status', __('admin.driver_created'));
    }

    public function edit(Driver $driver): View
    {
        return view('admin.drivers.form', ['driver' => $driver]);
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $driver->update($this->validated($request, $driver));

        return redirect()->route('admin.drivers.index')->with('status', __('admin.driver_updated'));
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        // Un chauffeur qui a déjà conduit n'est pas effacé : on le met hors
        // service, sinon l'historique des réservations devient incompréhensible.
        $driver->update(['status' => DriverStatus::Indisponible]);

        return back()->with('status', __('admin.driver_retired'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Driver $driver = null): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('drivers', 'phone')->ignore($driver?->id)],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(array_column(DriverStatus::cases(), 'value'))],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        return array_merge($data, ['whatsapp' => $data['whatsapp'] ?? $data['phone']]);
    }
}
