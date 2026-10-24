<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Concerns\GuardsBookingOwnership;
use App\Models\Booking;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Identité du conducteur, étape 2 du tunnel de réservation.
 */
class StoreContactRequest extends FormRequest
{
    use GuardsBookingOwnership;

    /** Garde de propriété et statut final : contrôlés avant la validation. */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        abort_unless($booking instanceof Booking, 404);

        $this->guardOwnership($this, $booking);
        abort_if($booking->status->isFinal(), 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birth_date' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'birth_date.before' => __('validation.booking.age', [
                'age' => app(SettingsService::class)->int('booking.min_age'),
            ]),
        ];
    }
}
