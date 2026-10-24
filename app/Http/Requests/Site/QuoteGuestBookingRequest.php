<?php

namespace App\Http\Requests\Site;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de calcul d'un devis réservation invitée : endpoint JSON, le
 * tunnel ne recharge pas la page.
 */
class QuoteGuestBookingRequest extends FormRequest
{
    /**
     * Aucune authentification requise. Le véhicule doit être réservable
     * pour en connaître le prix.
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
        ];
    }
}
