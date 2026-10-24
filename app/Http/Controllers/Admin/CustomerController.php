<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fiche client.
 *
 * L'écran le plus consulté quand un client pose un problème : combien de
 * réservations, quels documents, combien d'absences, et le vehicle en cause.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->toString();

        return view('admin.customers.index', [
            'users' => User::query()
                ->whereDoesntHave('roles')
                ->when($term, function ($q) use ($term) {
                    $q->where(fn ($sub) => $sub->where('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%"));
                })
                ->withCount(['bookings', 'documents'])
                ->withMax('bookings as last_booking_at', 'starts_at')
                ->orderByDesc('last_name')
                ->paginate(25)
                ->withQueryString(),
            'guestCustomers' => $this->guestCustomersQuery($term)
                ->orderByDesc('last_booking_at')
                ->paginate(15, ['*'], 'invitesPage')
                ->withQueryString(),
        ]);
    }

    public function showGuest(int $bookingId): View
    {
        $booking = Booking::query()->findOrFail($bookingId);
        abort_if($booking->user_id !== null, 404);

        $identity = $this->guestIdentity($booking);
        abort_if($identity === null, 404);

        return view('admin.customers.guest', [
            'guest' => $booking,
            'bookings' => Booking::query()
                ->whereNull('user_id')
                ->whereRaw($this->guestIdentityExpression().' = ?', [$identity])
                ->with('vehicle.category')
                ->orderByDesc('starts_at')
                ->paginate(15),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.customers.show', [
            'user' => $user->load(['documents', 'reviews']),
            'bookings' => $user->bookings()
                ->with('vehicle.category')
                ->orderByDesc('starts_at')
                ->paginate(15),
        ]);
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        $user->update(['blocked_at' => null, 'block_reason' => null]);

        return back()->with('status', __('admin.customer_unblocked', ['name' => $user->displayName()]));
    }

    /**
     * Exiger une caution anticipée : c'est la sortie de secours pour un client
     * à deux absences — on ne le bloque pas, on demande la garantie d'abord.
     */
    public function requirePrepayment(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'required' => ['required', 'boolean'],
        ]);

        $user->update(['prepayment_required' => (bool) $data['required']]);

        return back()->with('status', $data['required']
            ? __('admin.prepayment_on')
            : __('admin.prepayment_off'));
    }

    private function guestCustomersQuery(string $term): Builder
    {
        $identity = $this->guestIdentityExpression();

        return Booking::query()
            ->whereNull('user_id')
            ->whereNotNull('customer_name')
            ->whereRaw("({$identity}) IS NOT NULL")
            ->when($term, fn ($query) => $query->where(function ($sub) use ($term) {
                $sub->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            }))
            ->selectRaw("MIN(id) AS id, {$identity} AS contact_key, MIN(customer_name) AS customer_name, MIN(customer_phone) AS customer_phone, MIN(customer_email) AS customer_email, COUNT(*) AS bookings_count, MAX(created_at) AS last_booking_at")
            ->groupByRaw($identity);
    }

    private function guestIdentity(Booking $booking): ?string
    {
        $phone = preg_replace('/[\s\-+()]/', '', (string) $booking->customer_phone);

        return $phone !== ''
            ? $phone
            : (filled($booking->customer_email) ? mb_strtolower(trim($booking->customer_email)) : null);
    }

    private function guestIdentityExpression(): string
    {
        return "COALESCE(NULLIF(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(customer_phone), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), ''), NULLIF(LOWER(TRIM(customer_email)), ''))";
    }
}
