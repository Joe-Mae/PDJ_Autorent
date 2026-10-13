<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlockReason;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingBlock;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

/**
 * Planning de disponibilité.
 *
 * Vue planning, une ligne par véhicule : c'est la seule représentation qui
 * montre d'un coup d'œil où sont les trous. Les réservations sont des barres
 * bleues, les blocages d'atelier des barres grises, et le mois courant est
 * surligné.
 */
class CalendrierController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->monthFrom($request->string('mois')->toString());
        $vehicles = Vehicle::query()
            ->bookable()
            ->with('category')
            ->orderBy('category_id')
            ->get();

        return view('admin.calendar', [
            'vehicles' => $vehicles,
            'month' => $month,
            'bookings' => $this->bookingsForMonth($vehicles->pluck('id'), $month),
            'blocks' => $this->blocksForMonth($vehicles->pluck('id'), $month),
            'days' => $month->daysInMonth,
        ]);
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        $month = $this->monthFrom($request->string('mois')->toString());

        return view('admin.calendar', [
            'vehicles' => collect([$vehicle]),
            'month' => $month,
            'bookings' => $this->bookingsForMonth(collect([$vehicle->id]), $month),
            'blocks' => $this->blocksForMonth(collect([$vehicle->id]), $month),
            'days' => $month->daysInMonth,
            'reasons' => BlockReason::cases(),
        ]);
    }

    /** Un blocage d'atelier, posé au glisser-déposer depuis le planning. */
    public function storeBlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['required', 'in:'.implode(',', array_column(BlockReason::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        BookingBlock::query()->create([
            ...$data,
            'starts_at' => $data['starts_at'].' 08:00:00',
            'ends_at' => $data['ends_at'].' 19:00:00',
            'created_by' => $request->user()->id,
        ]);

        $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);

        if ($vehicle->status === VehicleStatus::Disponible) {
            $vehicle->update(['status' => VehicleStatus::EnMaintenance]);
        }

        return back()->with('status', __('admin.block_created'));
    }

    public function destroyBlock(Request $request, BookingBlock $block): RedirectResponse
    {
        $block->delete();

        return back()->with('status', __('admin.block_removed'));
    }

    private function bookingsForMonth($vehicleIds, $month)
    {
        return Booking::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('starts_at', '<', $month->copy()->endOfMonth()->addDay())
            ->where('ends_at', '>', $month->copy()->startOfMonth()->subDay())
            ->orderBy('starts_at')
            ->get();
    }

    private function blocksForMonth($vehicleIds, $month)
    {
        return BookingBlock::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('starts_at', '<', $month->copy()->endOfMonth()->addDay())
            ->where('ends_at', '>', $month->copy()->startOfMonth()->subDay())
            ->get();
    }

    private function monthFrom(?string $value): CarbonImmutable
    {
        return filled($value)
            ? Date::parse($value)->startOfMonth()->toImmutable()
            : now()->toImmutable()->startOfMonth();
    }
}
