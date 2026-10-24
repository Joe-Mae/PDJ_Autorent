<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recherche et fiche véhicule.
 *
 * Point important : on ne montre JAMAIS un véhicule indisponible. Le site
 * affiche le prix de ce qui est réellement réservable sur la période, et quand
 * rien ne sort, on propose des alternatives concrètes plutôt qu'une page d'erreur.
 */
class VehicleController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request, string $locale, ?VehicleCategory $category = null): View
    {
        $validated = $this->validateSearch($request);

        // Sans dates, on montre la flotte telle qu'elle est, calculée sur le
        // créneau par défaut. Sans cela, le lien « La flotte » du menu — le
        // plus cliqué du site — affichait « 0 véhicule » : le visiteur
        // arrivait par la porte sans dates et repartait sans rien.
        $period = $validated !== null ? $this->periodFrom($validated) : $this->defaultPeriod();

        $vehicles = $this->availability->searchVehicles($validated ?? [], $period);
        $driverAvailable = $this->availability->canOfferDriver($period);

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'period' => $period,
            'filters' => $validated ?? [],
            'category' => $category,
            'categories' => VehicleCategory::query()->active()->withCount(['vehicles' => fn ($q) => $q->bookable()])->orderBy('sort_order')->get(),
            'priceBounds' => $this->availability->priceBounds(),
            'driverAvailable' => $driverAvailable,
            'alternatives' => $vehicles->isEmpty() && $period ? $this->alternatives($period) : collect(),
            'whatsapp' => $this->settings->whatsappNumber(),
        ]);
    }

    public function show(Request $request, string $locale, Vehicle $vehicle): View
    {
        abort_unless($vehicle->isBookable(), 404);

        $validated = $this->validateSearch($request);
        $hasDates = $validated !== null && ! empty($validated['du']) && ! empty($validated['au']);
        $period = $hasDates ? $this->periodFrom($validated) : null;
        $isFree = $period ? $this->availability->isVehicleFree($vehicle, $period) : null;
        $quote = $period ? $this->pricing->quote($vehicle->category, $period, (bool) ($validated['with_driver'] ?? false)) : null;

        return view('vehicles.show', [
            'vehicle' => $vehicle->load(['category', 'media']),
            'period' => $period,
            'quote' => $quote,
            'isFree' => $isFree,
            'driverAvailable' => $period ? $this->availability->canOfferDriver($period) : false,
            'filters' => $validated ?? [],
            'reviews' => Review::query()
                ->published()
                ->where('vehicle_id', $vehicle->id)
                ->bestFirst()
                ->limit(6)
                ->get(),
            'similar' => Vehicle::query()
                ->bookable()
                ->where('category_id', $vehicle->category_id)
                ->whereKeyNot($vehicle->id)
                ->with(['category', 'media'])
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * Endpoint AJAX des filtres : renvoie juste le nombre de résultats, le
     * fragment HTML des lignes, et l'URL partageable. Pas de rechargement.
     */
    public function filter(Request $request, string $locale): JsonResponse
    {
        $validated = $this->validateSearch($request) ?? $request->only([
            'category', 'transmission', 'fuel', 'seats_min', 'price_min', 'price_max', 'with_driver', 'sort',
        ]);

        // Même règle que index() : sans dates, on filtre sur le créneau par
        // défaut. Passer un tableau sans `du` ici faisait planter
        // periodFrom() en 500.
        $period = $this->periodFrom($validated);
        $vehicles = $this->availability->searchVehicles($validated, $period);

        return response()->json([
            'count' => $vehicles->count(),
            'url' => request()->fullUrlWithQuery([]),
            'html' => view('vehicles.partials.rows', [
                'vehicles' => $vehicles,
                'period' => $period,
                'pricing' => $this->pricing,
            ])->render(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validateSearch(Request $request): ?array
    {
        $data = $request->validate([
            'du' => ['nullable', 'date_format:Y-m-d'],
            'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['nullable', 'integer', 'between:6,22'],
            'heure_retour' => ['nullable', 'integer', 'between:6,22'],
            'avec_chauffeur' => ['nullable', 'boolean'],
            'categorie' => ['nullable', 'string', 'exists:vehicle_categories,slug'],
            'transmission' => ['nullable', 'in:manuelle,automatique'],
            'fuel' => ['nullable', 'array'],
            'fuel.*' => ['in:essence,diesel,hybride,electrique'],
            'places_min' => ['nullable', 'integer', 'between:2,9'],
            'prix_min' => ['nullable', 'integer', 'min:0'],
            'prix_max' => ['nullable', 'integer', 'min:0'],
            'tri' => ['nullable', 'in:prix-asc,prix-desc'],
        ], [], [], null, false);

        if (blank($data['du'] ?? null) || blank($data['au'] ?? null)) {
            return null;
        }

        return array_merge($data, [
            'seats_min' => $data['places_min'] ?? null,
            'price_min' => $data['prix_min'] ?? null,
            'price_max' => $data['prix_max'] ?? null,
            'sort' => $data['tri'] ?? 'prix-asc',
            'with_driver' => (bool) ($data['avec_chauffeur'] ?? false),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function periodFrom(array $data): BookingPeriod
    {
        // Un tableau de filtres peut arriver sans les dates — c'est le cas
        // quand le visiteur arrive sur /vehicules sans avoir rien choisi. On
        // retombe alors sur le créneau par défaut plutôt que de lire une clé
        // absente.
        if (blank($data['du'] ?? null) || blank($data['au'] ?? null)) {
            return $this->defaultPeriod();
        }

        return BookingPeriod::make(
            $data['du'],
            $data['au'],
            (int) ($data['heure_depart'] ?? $this->settings->openHour()),
            (int) ($data['heure_retour'] ?? $this->settings->closeHour()),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }

    private function defaultPeriod(): BookingPeriod
    {
        $start = today()->addDay();

        return BookingPeriod::make(
            $start->toDateString(),
            $start->copy()->addDays(3)->toDateString(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
    }

    /**
     * Quand rien ne sort : les 3 premières disponibilités proches de la flotte,
     * pour donner au client une action immédiate plutôt qu'un cul-de-sac.
     */
    private function alternatives(BookingPeriod $period)
    {
        return Vehicle::query()
            ->bookable()
            ->with(['category', 'media'])
            ->get()
            ->map(function (Vehicle $vehicle) use ($period) {
                $next = $this->availability->nextFreePeriod($vehicle, $period, 21);

                return $next ? compact('vehicle', 'next') : null;
            })
            ->filter()
            ->sortBy(fn ($row) => $row['next']->startsAt)
            ->take(3)
            ->values();
    }
}
