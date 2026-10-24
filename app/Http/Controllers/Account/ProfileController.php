<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validated();

        $data['name'] = trim("{$data['first_name']} {$data['last_name']}");

        $user->update($data);

        return back()->with('status', __('profile.updated'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('profile.wrong_password'),
            ]);
        }

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', __('profile.password_updated'));
    }
}
