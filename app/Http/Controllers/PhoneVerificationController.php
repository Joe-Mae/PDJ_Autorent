<?php

namespace App\Http\Controllers;

use App\Http\Requests\Site\VerifyCodeRequest;
use App\Models\PhoneCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Saisie du code de vérification.
 *
 * Une page volontairement minimale, sans navigation : le client est au milieu
 * son tunnel, il ne doit pas pouvoir se perdre ni trouver le reste du site.
 */
class PhoneVerificationController extends Controller
{
    public function show(Request $request, string $locale, string $code): View
    {
        return view('phone.verify', [
            'phone' => $this->maskPhone($code),
        ]);
    }

    /** Vérification faite en AJAX : le tunnel ne recharge pas la page. */
    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $phoneCode = PhoneCode::query()
            ->where('phone', $data['phone'] ?? '')
            ->latest('id')
            ->first();

        if ($phoneCode === null || ! $phoneCode->isUsable()) {
            return back()->withErrors(['code' => __('booking.error.code_expired')]);
        }

        if (! Hash::check($data['code'], $phoneCode->code_hash)) {
            $phoneCode->increment('attempts');

            return back()->withErrors(['code' => __('booking.error.code_invalid', [
                'left' => $phoneCode->attemptsLeft(),
            ])]);
        }

        $phoneCode->update(['verified_at' => now()]);

        return back()->with('status', __('booking.phone_verified'));
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) < 6 ? $phone : substr($digits, 0, 4).' •• •• '.substr($digits, -2);
    }
}
