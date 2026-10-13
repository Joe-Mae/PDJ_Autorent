<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gestion de la flotte.
 *
 * Deux règles de sûreté : un véhicule qui a déjà servi n'est jamais supprimé,
 * il passe en hors service ; et changer un véhicule de catégorie ne touche pas
 * au prix des réservations déjà prises, parce que le prix est figé à la
 * création de la réservation.
 */
class VehicleController extends Controller
{
    /** Nombre de photos acceptées d'un coup. */
    private const MAX_PHOTOS = 10;

    /**
     * Taille maximale d'une photo, en kilo-octets.
     *
     * Volontairement inférieure à upload_max_filesize (2 M par défaut) : on
     * refuse le fichier avec un message lisible, plutôt que de le laisser
     * disparaître sans explication.
     */
    private const MAX_PHOTO_KILOS = 2048;

    public function index(Request $request): View
    {
        return view('admin.vehicles.index', [
            'vehicles' => Vehicle::query()
                ->with('category')
                ->when($request->string('statut')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->when($request->string('q')->toString(), function ($q, $term) {
                    $q->where(fn ($sub) => $sub->where('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%")
                        ->orWhere('plate', 'like', "%{$term}%"));
                })
                ->orderBy('brand')
                ->orderBy('model')
                ->paginate(20)
                ->withQueryString(),
            'statuses' => VehicleStatus::cases(),
            'status' => $request->string('statut')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => new Vehicle(['year' => now()->year, 'seats' => 5, 'doors' => 5, 'bags' => 3, 'air_conditioning' => true]),
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $vehicle = Vehicle::query()->create([
            ...$data,
            'features' => array_values(array_filter($request->input('features', []))),
            'status' => VehicleStatus::Disponible,
        ]);

        $this->attachPhotos($request, $vehicle);

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('status', __('admin.vehicle_created'));
    }

    public function show(Vehicle $vehicle): View
    {
        return view('admin.vehicles.show', [
            'vehicle' => $vehicle->load(['category', 'media']),
            'bookings' => $vehicle->bookings()->with('user')->orderByDesc('starts_at')->limit(20)->get(),
            'blocks' => $vehicle->blocks()->orderByDesc('starts_at')->limit(10)->get(),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => $vehicle,
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validated($request, $vehicle);

        $vehicle->update([
            ...$data,
            'features' => array_values(array_filter($request->input('features', []))),
        ]);

        $this->attachPhotos($request, $vehicle);

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('status', __('admin.vehicle_updated'));
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->bookings()->exists()) {
            // Un véhicule avec un historique n'est pas supprimé : il sort du
            // planning, et les réservations passées gardent leur référence.
            $vehicle->update(['status' => VehicleStatus::HorsService, 'is_active' => false]);

            return redirect()
                ->route('admin.vehicles.index')
                ->with('status', __('admin.vehicle_archived'));
        }

        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', __('admin.vehicle_deleted'));
    }

    /** Passage rapide de statut depuis la liste (disponible / maintenance / hors service). */
    public function setStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(VehicleStatus::cases(), 'value'))],
        ]);

        $vehicle->update(['status' => VehicleStatus::from($data['status'])]);

        return back()->with('status', __('admin.vehicle_status_updated'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:vehicle_categories,id'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($vehicle?->id)],
            'year' => ['required', 'integer', 'between:2000,'.(now()->year + 1)],
            'transmission' => ['required', 'in:manuelle,automatique'],
            'fuel' => ['required', 'in:essence,diesel,hybride,electrique'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'doors' => ['required', 'integer', 'between:2,6'],
            'bags' => ['required', 'integer', 'between:0,9'],
            'air_conditioning' => ['nullable', 'boolean'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'next_technical_control_at' => ['nullable', 'date'],
            'description_fr' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],

            // Le format et la taille sont vérifiés ici, et non laissés au
            // serveur. Sans ces règles, une photo de téléphone trop lourde est
            // refusée par PHP sans qu'aucun message n'apparaisse : l'agent
            // voit simplement « rien ne s'est enregistré ».
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_PHOTO_KILOS,
            ],
        ], [
            'plate.unique' => __('admin.plate_taken'),
            'photos.max' => __('admin.photos_too_many', ['max' => self::MAX_PHOTOS]),
            'photos.*.image' => __('admin.photo_not_image'),
            'photos.*.mimes' => __('admin.photo_bad_format'),
            'photos.*.max' => __('admin.photo_too_heavy', ['max' => self::MAX_PHOTO_KILOS]),
        ]);

        return array_merge($data, [
            // La colonne est NOT NULL : un formulaire laissé vide arrivait avec
            // `mileage = null` et MySQL refusait l'insertion en 500. Un
            // véhicule neuf est à zéro kilomètre, pas à « inconnu ».
            'mileage' => $data['mileage'] ?? 0,

            // Une case à décocher n'envoie rien du tout. Lire la clé avec
            // `?? true` rendait « visible sur le site » incontrôlable : on
            // décochait, on enregistrait, et le véhicule restait visible.
            'air_conditioning' => $request->boolean('air_conditioning'),
            'is_active' => $request->boolean('is_active'),
        ]);
    }

    private function attachPhotos(Request $request, Vehicle $vehicle): void
    {
        foreach ($request->file('photos', []) as $index => $photo) {
            $vehicle->addMedia($photo)
                ->preservingOriginal()
                ->usingFileName(Str::slug($vehicle->brand.'-'.$vehicle->model).'-'.$index.'.'.$photo->getClientOriginalExtension())
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        }
    }
}
