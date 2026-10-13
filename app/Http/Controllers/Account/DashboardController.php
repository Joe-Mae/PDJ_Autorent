<?php

namespace App\Http\Controllers\Account;

use App\Enums\BookingStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Espace client.
 *
 * Le tableau de bord ne montre qu'une chose en haut : la prochaine réservation,
 * avec le compte à rebours et les documents à emporter. Le reste est de
 * l'historique. Un client qui vient vérifier « c'est bien enregistré, je
 * présente quoi le matin ? » doit le trouver en trois secondes.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $upcoming = $user->bookings()
            ->whereIn('status', [BookingStatus::EnAttente->value, BookingStatus::Confirmee->value])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->with(['vehicle.category', 'documents'])
            ->first();

        $ongoing = $user->bookings()
            ->where('status', BookingStatus::EnCours->value)
            ->with(['vehicle.category', 'payments'])
            ->first();

        return view('account.dashboard', [
            'user' => $user,
            'upcoming' => $upcoming,
            'ongoing' => $ongoing,
            'recent' => $user->bookings()
                ->whereNotIn('status', [BookingStatus::EnAttente->value, BookingStatus::Confirmee->value, BookingStatus::EnCours->value])
                ->orderByDesc('created_at')
                ->with(['vehicle.category'])
                ->limit(5)
                ->get(),
            'missingDocuments' => $this->missingDocuments($upcoming),
        ]);
    }

    /** @return list<string> */
    private function missingDocuments(?Booking $booking): array
    {
        if ($booking === null) {
            return [];
        }

        $required = DocumentType::requiredForClient($booking->with_driver);
        $uploaded = $booking->documents->pluck('type')->all();

        return array_values(array_map(
            static fn (DocumentType $type) => $type->value,
            array_filter($required, static fn (DocumentType $type) => ! in_array($type, $uploaded, true)),
        ));
    }
}
