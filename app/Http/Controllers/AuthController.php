<?php

namespace App\Http\Controllers;

use App\Http\Requests\Site\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Connexion et déconnexion du personnel.
 *
 * On accepte le téléphone comme identifiant : au Togo, le numéro est ce que
 * l'employé a réellement sous la main. L'e-mail reste accepté, pour ceux qui
 * préfèrent le tapoter au clavier.
 *
 * Il n'y a pas d'espace client : le public réserve en mode invité, sans
 * compte à créer ni à retenir. La connexion n'ouvre que l'administration, et
 * un compte sans rôle du personnel est refusé comme un mot de passe faux —
 * on ne révèle pas qu'une fiche existe.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $this->findByIdentifier($data['login']);

        if ($user === null || ! $user->isStaff() || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home', ['locale' => app()->getLocale()]);
    }

    /**
     * Numéro au format E.164 sans « + » : 22890123499. C'est ce format qui est
     * stocké, donc c'est celui qu'on cherche.
     */
    private function findByIdentifier(string $identifier): ?User
    {
        $digits = preg_replace('/\D/', '', $identifier) ?? '';
        $normalized = $digits !== '' && ! str_starts_with($digits, '228')
            ? '228'.$digits
            : $digits;

        return User::query()
            ->where('email', $identifier)
            ->when($normalized !== '', fn ($q) => $q->orWhere('phone', $normalized))
            ->first();
    }
}
