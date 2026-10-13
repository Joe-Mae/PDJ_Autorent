<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Connexion et inscription.
 *
 * On accepte le téléphone comme identifiant : au Togo, le numéro est ce que le
 * client a réellement sous la main, et c'est par là que le rappel WhatsApp part.
 * L'e-mail reste facultatif, réservé à ceux qui veulent un justificatif écrit.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findByIdentifier($data['login']);

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('account.dashboard', ['locale' => app()->getLocale()]));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        if (User::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('auth.phone_taken'),
            ]);
        }

        $user = User::query()->create([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'birth_date' => $data['birth_date'],
            'password' => $data['password'],
            'preferred_locale' => app()->getLocale(),
        ]);

        $user->assignRole('client');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.dashboard', ['locale' => app()->getLocale()])
            ->with('status', __('auth.welcome'));
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
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return str_starts_with($digits, '228') ? $digits : '228'.$digits;
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
