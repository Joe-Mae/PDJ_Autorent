<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord de l'agence.
 *
 * On commence par ce qui demande une action aujourd'hui, pas par un graphique :
 * les documents à vérifier avant ce soir, les véhicules qui partent, les
 * retards. Les chiffres du mois arrivent après, pour l'agent qui fait le point
 * le vendredi.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'vehicules' => Vehicle::query()->bookable()->count(),
                'en_location' => Vehicle::query()->where('status', VehicleStatus::EnLocation->value)->count(),
                'maintenance' => Vehicle::query()->where('status', VehicleStatus::EnMaintenance->value)->count(),
                'chauffeurs' => Driver::query()->available()->count(),
            ],

            'today' => [
                'departures' => Booking::query()
                    ->whereIn('status', [BookingStatus::Confirmee->value, BookingStatus::EnCours->value])
                    ->whereDate('starts_at', today())
                    ->with(['vehicle', 'user'])
                    ->orderBy('pickup_hour')
                    ->get(),

                'returns' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->whereDate('ends_at', today())
                    ->with(['vehicle', 'user'])
                    ->get(),

                'overdue' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->where('ends_at', '<', now())
                    ->with(['vehicle', 'user'])
                    ->get(),
            ],

            'pending' => [
                'validation' => Booking::query()
                    ->where('status', BookingStatus::EnAttente->value)
                    ->with(['vehicle.category', 'user', 'documents'])
                    ->orderBy('starts_at')
                    ->get(),

                // La file de documents est triée par urgence : ce qui part demain
                // passe avant ce qui part dans trois semaines.
                'documents' => CustomerDocument::query()
                    ->pending()
                    ->with(['user', 'booking.vehicle'])
                    ->orderByRaw('COALESCE((SELECT starts_at FROM bookings WHERE bookings.id = documents.booking_id), created_at) ASC')
                    ->limit(20)
                    ->get(),
            ],

            'cash' => [
                'today_in' => Payment::query()
                    ->whereNotIn('kind', ['caution_restitution'])
                    ->whereDate('recorded_at', today())
                    ->sum('amount'),

                'today_out' => Payment::query()
                    ->where('kind', 'caution_restitution')
                    ->whereDate('recorded_at', today())
                    ->sum('amount'),

                'deposits_held' => DB::table('payments')
                    ->where('kind', 'caution')
                    ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                    ->whereIn('bookings.status', [
                        BookingStatus::EnCours->value,
                        BookingStatus::Confirmee->value,
                        BookingStatus::EnAttente->value,
                    ])
                    ->sum('amount'),
            ],

            'monthRevenue' => Payment::query()
                ->where('kind', 'location')
                ->whereBetween('recorded_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ]);
    }
}
