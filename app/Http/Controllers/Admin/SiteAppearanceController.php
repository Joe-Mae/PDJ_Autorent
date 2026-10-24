<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class SiteAppearanceController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function edit(): View
    {
        $vehicles = Vehicle::query()
            ->bookable()
            ->whereHas('media', fn ($query) => $query->where('collection_name', Vehicle::MEDIA_GALLERY))
            ->with(['media' => fn ($query) => $query->where('collection_name', Vehicle::MEDIA_GALLERY)])
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        return view('admin.appearance.edit', [
            'vehicles' => $vehicles,
            'heroVehicleIds' => $this->settings->get('site.hero_vehicle_ids', 'array'),
            'heroImages' => collect($this->settings->get('site.hero_uploaded_images', 'array'))
                ->filter(fn ($path) => is_string($path)
                    && str_starts_with($path, 'hero-slides/')
                    && ! str_contains($path, '..')
                    && Storage::disk('public')->exists($path))
                ->map(fn (string $path) => ['path' => $path, 'url' => Storage::disk('public')->url($path)])
                ->values(),
            'brandColor' => $this->safeColor('site.brand_color', '#0A6E4C'),
            'accentColor' => $this->safeColor('site.accent_color', '#FFC400'),
            'brandMark' => $this->settings->string('site.brand_mark'),
            'brandLabel' => $this->settings->string('site.brand_label'),
            'heroEyebrowFr' => $this->settings->string('site.hero_eyebrow.fr'),
            'heroEyebrowEn' => $this->settings->string('site.hero_eyebrow.en'),
            'footerTaglineFr' => $this->settings->string('site.footer_tagline.fr'),
            'footerTaglineEn' => $this->settings->string('site.footer_tagline.en'),
            'navigationLabels' => [
                'home' => [
                    'fr' => $this->settings->string('nav.home.fr'),
                    'en' => $this->settings->string('nav.home.en'),
                ],
                'fleet' => [
                    'fr' => $this->settings->string('nav.fleet.fr'),
                    'en' => $this->settings->string('nav.fleet.en'),
                ],
                'driver' => [
                    'fr' => $this->settings->string('nav.driver.fr'),
                    'en' => $this->settings->string('nav.driver.en'),
                ],
                'faq' => [
                    'fr' => $this->settings->string('nav.faq.fr'),
                    'en' => $this->settings->string('nav.faq.en'),
                ],
                'contact' => [
                    'fr' => $this->settings->string('nav.contact.fr'),
                    'en' => $this->settings->string('nav.contact.en'),
                ],
                'lookup' => [
                    'fr' => $this->settings->string('nav.lookup.fr'),
                    'en' => $this->settings->string('nav.lookup.en'),
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $heroVehicleIdsInput = $request->input('hero_vehicle_ids', []);
        if (is_array($heroVehicleIdsInput)) {
            $request->merge([
                'hero_vehicle_ids' => array_values(array_filter(
                    $heroVehicleIdsInput,
                    fn ($id) => $id !== null && $id !== '',
                )),
            ]);
        }

        $removeHeroImagesInput = $request->input('remove_hero_images', []);
        if (is_array($removeHeroImagesInput)) {
            $request->merge([
                'remove_hero_images' => array_values(array_filter(
                    $removeHeroImagesInput,
                    fn ($path) => $path !== null && $path !== '',
                )),
            ]);
        }

        $data = $request->validate([
            'hero_vehicle_ids' => ['nullable', 'array', 'max:4'],
            'hero_vehicle_ids.*' => ['integer', 'distinct', Rule::exists('vehicles', 'id')],
            'hero_images' => ['nullable', 'array', 'max:4'],
            'hero_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_hero_images' => ['nullable', 'array'],
            'remove_hero_images.*' => ['string', 'distinct'],
            'brand_color' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'accent_color' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'brand_mark' => ['nullable', 'string', 'max:8'],
            'brand_label' => ['required', 'string', 'max:40'],
            'hero_eyebrow_fr' => ['required', 'string', 'max:100'],
            'hero_eyebrow_en' => ['required', 'string', 'max:100'],
            'footer_tagline_fr' => ['required', 'string', 'max:180'],
            'footer_tagline_en' => ['required', 'string', 'max:180'],
            'nav_home_fr' => ['required', 'string', 'max:40'],
            'nav_home_en' => ['required', 'string', 'max:40'],
            'nav_fleet_fr' => ['required', 'string', 'max:40'],
            'nav_fleet_en' => ['required', 'string', 'max:40'],
            'nav_driver_fr' => ['required', 'string', 'max:40'],
            'nav_driver_en' => ['required', 'string', 'max:40'],
            'nav_faq_fr' => ['required', 'string', 'max:50'],
            'nav_faq_en' => ['required', 'string', 'max:50'],
            'nav_contact_fr' => ['required', 'string', 'max:40'],
            'nav_contact_en' => ['required', 'string', 'max:40'],
            'nav_lookup_fr' => ['required', 'string', 'max:40'],
            'nav_lookup_en' => ['required', 'string', 'max:40'],
        ], [
            'hero_images.max' => 'Vous pouvez importer quatre photos au maximum à la fois.',
            'hero_images.*.image' => 'Chaque fichier du diaporama doit être une image valide.',
            'hero_images.*.mimes' => 'Formats acceptés pour le diaporama : JPG, PNG ou WebP.',
            'hero_images.*.max' => 'Chaque photo du diaporama ne doit pas dépasser 2 Mo.',
        ]);

        $heroVehicleIds = $data['hero_vehicle_ids'] ?? [];
        if ($heroVehicleIds !== []) {
            $validHeroVehicleCount = Vehicle::query()
                ->bookable()
                ->whereKey($heroVehicleIds)
                ->whereHas('media', fn ($query) => $query->where('collection_name', Vehicle::MEDIA_GALLERY))
                ->count();

            if ($validHeroVehicleCount !== count($heroVehicleIds)) {
                return back()
                    ->withErrors(['hero_vehicle_ids' => 'Choisissez uniquement des véhicules actifs avec une photo.'])
                    ->withInput();
            }
        }

        $storedHeroImages = collect($this->settings->get('site.hero_uploaded_images', 'array'))
            ->filter(fn ($path) => is_string($path)
                && str_starts_with($path, 'hero-slides/')
                && ! str_contains($path, '..'))
            ->values();
        $removedHeroImages = $storedHeroImages
            ->intersect($data['remove_hero_images'] ?? [])
            ->values();
        $remainingHeroImages = $storedHeroImages
            ->diff($removedHeroImages)
            ->values();
        $newHeroImages = $data['hero_images'] ?? [];

        if ($remainingHeroImages->count() + count($newHeroImages) + count($heroVehicleIds) > 4) {
            return back()
                ->withErrors(['hero_images' => 'Le diaporama peut contenir quatre photos au maximum. Retirez une photo ou un véhicule avant d’en ajouter.'])
                ->withInput();
        }

        $uploadedHeroImages = [];
        foreach ($newHeroImages as $image) {
            $path = $image->storePublicly('hero-slides', 'public');
            if ($path === false) {
                throw new RuntimeException('Impossible d’enregistrer une photo du diaporama.');
            }

            $uploadedHeroImages[] = $path;
        }

        $values = [
            'site.hero_vehicle_ids' => $heroVehicleIds,
            'site.hero_uploaded_images' => [
                ...$remainingHeroImages->all(),
                ...$uploadedHeroImages,
            ],
            'site.brand_color' => $data['brand_color'],
            'site.accent_color' => $data['accent_color'],
            'site.brand_mark' => $data['brand_mark'] ?? $this->settings->string('site.brand_mark'),
            'site.brand_label' => $data['brand_label'],
            'site.hero_eyebrow.fr' => $data['hero_eyebrow_fr'],
            'site.hero_eyebrow.en' => $data['hero_eyebrow_en'],
            'site.footer_tagline.fr' => $data['footer_tagline_fr'],
            'site.footer_tagline.en' => $data['footer_tagline_en'],
            'nav.home.fr' => $data['nav_home_fr'],
            'nav.home.en' => $data['nav_home_en'],
            'nav.fleet.fr' => $data['nav_fleet_fr'],
            'nav.fleet.en' => $data['nav_fleet_en'],
            'nav.driver.fr' => $data['nav_driver_fr'],
            'nav.driver.en' => $data['nav_driver_en'],
            'nav.faq.fr' => $data['nav_faq_fr'],
            'nav.faq.en' => $data['nav_faq_en'],
            'nav.contact.fr' => $data['nav_contact_fr'],
            'nav.contact.en' => $data['nav_contact_en'],
            'nav.lookup.fr' => $data['nav_lookup_fr'],
            'nav.lookup.en' => $data['nav_lookup_en'],
        ];

        DB::transaction(function () use ($request, $values): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => is_array($value) ? json_encode($value) : $value,
                        'type' => in_array($key, ['site.hero_vehicle_ids', 'site.hero_uploaded_images'], true) ? 'json' : 'string',
                        'group' => 'website',
                        'label_fr' => str($key)->after('.')->replace('_', ' ')->ucfirst()->toString(),
                        'updated_by' => $request->user()->id,
                    ],
                );
            }
        });

        foreach ($removedHeroImages as $path) {
            if (Storage::disk('public')->exists($path) && ! Storage::disk('public')->delete($path)) {
                throw new RuntimeException('Impossible de supprimer une ancienne photo du diaporama.');
            }
        }

        $this->settings->flush();

        return to_route('admin.appearance.edit')->with('status', 'Apparence du site mise à jour.');
    }

    private function safeColor(string $key, string $fallback): string
    {
        $color = $this->settings->string($key);

        return preg_match('/\A#[0-9A-Fa-f]{6}\z/', $color) === 1 ? $color : $fallback;
    }
}
