<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'birth_date' => ['required', 'date', 'before:today'],
            'preferred_locale' => ['required', 'in:fr,en'],
        ]);

        $data['name'] = trim("{$data['first_name']} {$data['last_name']}");

        $user->update($data);

        return back()->with('status', __('profile.updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('profile.wrong_password'),
            ]);
        }

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', __('profile.password_updated'));
    }
}
