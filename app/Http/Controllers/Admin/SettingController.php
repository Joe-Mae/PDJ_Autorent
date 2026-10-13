<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Réglages de l'agence.
 *
 * Une seule page pour tous les réglages qui touches au métier : horaires,
 * battement, délai de grâce, tarif du bloc chauffeur, âge minimum, cautions.
 * C'est le levier qui permet à l'agence de changer sa politique sans toucher
 * au code — et le risque est inverse : il faut donc journaliser qui a modifié
 * quoi.
 */
class SettingController extends Controller
{
    /** Les groupes, dans l'ordre où l'agent les lit. */
    private const GROUPS = [
        'general' => 'Agence',
        'reservation' => 'Réservation',
        'driver' => 'Chauffeur',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public function index(): View
    {
        $this->settings->flush();

        return view('admin.settings.index', [
            'groups' => collect(self::GROUPS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'settings' => Setting::query()->inGroup($key)->get(),
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            $setting = Setting::query()->find($key);

            if ($setting === null) {
                continue;
            }

            if ($setting->type === 'int' && filled($value)) {
                $value = (int) $value;
            }

            $setting->update(['value' => (string) $value, 'updated_by' => $request->user()->id]);
        }

        $this->settings->flush();

        return back()->with('status', __('admin.settings_saved'));
    }
}
