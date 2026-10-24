<?php

namespace App\Http\Requests\Site;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de demande de réservation invitée.
 */
class StoreGuestBookingRequest extends FormRequest
{
    /**
     * Aucune authentification requise ici : les garde-fous (véhicule
     * réservable, jeton) sont portés par authorize().
     */
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        abort_unless($vehicle->isBookable(), 404);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        $settings = app(SettingsService::class);

        return [
            'du' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer', 'between:'.$settings->openHour().','.$settings->closeHour()],
            'heure_retour' => ['required', 'integer', 'between:'.$settings->openHour().','.$settings->closeHour()],
            'avec_chauffeur' => ['nullable', 'boolean'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
