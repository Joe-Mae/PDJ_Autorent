<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Concerns\GuardsBookingOwnership;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie du code de vérification du téléphone, étape 3 du tunnel.
 */
class VerifyBookingPhoneCodeRequest extends FormRequest
{
    use GuardsBookingOwnership;

    /** La garde de propriété est contrôlée avant la validation. */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        abort_unless($booking instanceof Booking, 404);

        $this->guardOwnership($this, $booking);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }
}
