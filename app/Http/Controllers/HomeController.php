<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\AvailabilityService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Accueil. Un seul but : faire saisir des dates et voir des voitures.
 * Tout le reste de la page est là pour rassurer, pas pour distraire.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AvailabilityService $availability,
    ) {}

    public function __invoke(Request $request): View
    {
        $locale = app()->getLocale();
        $uploadedHeroImages = collect($this->settings->get('site.hero_uploaded_images', 'array'))
            ->filter(fn ($path) => is_string($path)
                && str_starts_with($path, 'hero-slides/')
                && ! str_contains($path, '..')
                && Storage::disk('public')->exists($path))
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->values();
        $selectedHeroVehicleIds = $this->settings->get('site.hero_vehicle_ids', 'array');
        $heroVehicles = collect();

        if ($selectedHeroVehicleIds !== []) {
            $vehiclesById = Vehicle::query()
                ->bookable()
                ->whereKey($selectedHeroVehicleIds)
                ->whereHas('media', fn (Builder $query) => $query->where('collection_name', Vehicle::MEDIA_GALLERY))
                ->with(['media' => fn (MorphMany $query) => $query->where('collection_name', Vehicle::MEDIA_GALLERY)])
                ->get()
                ->keyBy('id');

            $heroVehicles = collect($selectedHeroVehicleIds)
                ->map(fn ($id) => $vehiclesById->get($id))
                ->filter();
        }

        return view('home', [
            'banners' => Banner::query()->live('home_hero')->get(),
            'featured' => Vehicle::query()
                ->bookable()
                ->whereHas('category', fn ($q) => $q->where('is_featured', true))
                ->with(['category', 'media'])
                ->limit(6)
                ->get(),
            'heroImages' => $uploadedHeroImages
                ->concat($heroVehicles
                    ->map(fn (Vehicle $vehicle): ?string => $vehicle->mainPhotoUrl())
                    ->filter())
                ->filter()
                ->unique()
                ->take(4)
                ->values(),
            'categories' => VehicleCategory::query()
                ->active()
                ->withCount(['vehicles' => fn ($q) => $q->bookable()])
                ->orderBy('sort_order')
                ->get(),
            'priceBounds' => $this->availability->priceBounds(),
            'reviews' => Review::query()->published()->bestFirst()->with('vehicle.category')->limit(3)->get(),
            'averageRating' => round((float) Review::query()->published()->avg('rating'), 1),
            'reviewCount' => Review::query()->published()->count(),
            'faqs' => Faq::query()->where('is_published', true)->orderBy('sort_order')->limit(4)->get(),
            'whatsapp' => $this->settings->whatsappNumber(),
            'phone' => $this->settings->formattedPhone(),
            'locale' => $locale,
            'heroEyebrow' => $this->settings->string('site.hero_eyebrow.'.$locale),
            'navLabels' => [
                'fleet' => $this->settings->string('nav.fleet.'.$locale),
                'driver' => $this->settings->string('nav.driver.'.$locale),
            ],
        ]);
    }
}
