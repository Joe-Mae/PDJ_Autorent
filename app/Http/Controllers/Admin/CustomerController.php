<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
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
        return view('admin.customers.index', [
            'users' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'client'))
                ->when($request->string('q')->toString(), function ($q, $term) {
                    $q->where(fn ($sub) => $sub->where('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%"));
                })
                ->withCount(['bookings', 'documents'])
                ->orderByDesc('last_name')
                ->paginate(25)
                ->withQueryString(),
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
}
