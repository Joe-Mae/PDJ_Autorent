<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveVehicleRequest;
use App\Http\Requests\Admin\SetVehicleStatusRequest;
use App\Http\Requests\Admin\VehicleSelectionRequest;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gestion de la flotte.
 *
 * Trois règles de sûreté : un véhicule qui a déjà servi n'est jamais supprimé,
 * il est archivé puis remis en service d'un clic ; changer un véhicule de
 * catégorie ne touche pas au prix des réservations déjà prises, parce que le
 * prix est figé à la création de la réservation ; et un véhicule n'est
 * réservable que s'il est à la fois visible et en service.
 */
class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->string('vue')->toString() === 'archives' ? 'archives' : 'active';
        $status = $request->string('statut')->toString();

        // « Hors service » n'existe que dans les archives : y basculer
        // directement évite une flotte active vide et un filtre inutilisable.
        if ($status === VehicleStatus::HorsService->value) {
            $view = 'archives';
        }

        // Un véhicule est archivé dès qu'il est masqué, hors service ou
        // supprimé. Les supprimés (sans historique) restent restaurables :
        // leur fiche et leurs photos dorment en base, inutile de les recréer.
        $archived = fn (Builder $query): Builder => $query
            ->where(function (Builder $sub): void {
                $sub->where('is_active', false)
                    ->orWhere('status', VehicleStatus::HorsService->value)
                    ->orWhereNotNull('deleted_at');
            });

        $query = $view === 'archives'
            ? Vehicle::query()->withTrashed()->where($archived)
            : Vehicle::query()
                ->where('is_active', true)
                ->where('status', '!=', VehicleStatus::HorsService->value);

        return view('admin.vehicles.index', [
            'vehicles' => $query
                ->with(['category', 'media'])
                ->withCount([
                    'bookings as current_bookings_count' => fn ($query) => $query
                        ->active()
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>', now()),
                ])
                ->when($status, fn ($q, $s) => $q->where('status', $s))
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
            'status' => $status,
            'view' => $view,
            'counts' => [
                'active' => Vehicle::query()
                    ->where('is_active', true)
                    ->where('status', '!=', VehicleStatus::HorsService->value)
                    ->count(),
                'archived' => Vehicle::query()->withTrashed()->where($archived)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => new Vehicle(['year' => now()->year, 'seats' => 5, 'doors' => 5, 'bags' => 3, 'air_conditioning' => true]),
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(SaveVehicleRequest $request): RedirectResponse
    {
        $data = $this->validated($request);

        $vehicle = Vehicle::query()->create([
            ...$data,
            'features' => array_values(array_filter($request->input('features', []))),
            'status' => VehicleStatus::Disponible,
        ]);

        $newMedia = $this->attachPhotos($request, $vehicle);

        $this->setMainPhoto($vehicle, $request->input('main_photo_id'), $newMedia, $request->input('main_photo_index'));

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('status', __('admin.vehicle_created'));
    }

    public function show(Vehicle $vehicle): View
    {
        return view('admin.vehicles.show', [
            'vehicle' => $vehicle->load(['category', 'media']),
            'bookings' => $vehicle->bookings()->with('user')->orderByDesc('starts_at')->limit(20)->get(),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => $vehicle,
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(SaveVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validated($request);
        $wasHidden = ! $vehicle->is_active;

        $vehicle->update([
            ...$data,
            'features' => array_values(array_filter($request->input('features', []))),
        ]);

        // Archiver un véhicule le rend invisible ET hors service. Recocher
        // « Visible sur le site » doit donc aussi le remettre en service,
        // sinon la fiche réapparaît mais reste non réservable. Un véhicule
        // déjà visible qui est hors service (panne, atelier) n'est pas touché.
        if ($wasHidden && $data['is_active'] && $vehicle->status === VehicleStatus::HorsService) {
            $vehicle->update(['status' => VehicleStatus::Disponible]);
        }

        $newMedia = $this->attachPhotos($request, $vehicle);

        $this->setMainPhoto($vehicle, $request->input('main_photo_id'), $newMedia, $request->input('main_photo_index'));

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

    /** Suppression (ou archivage) en masse des véhicules sélectionnés. */
    public function destroyMany(VehicleSelectionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $archived = 0;
        $deleted = 0;

        foreach (Vehicle::query()->whereIn('id', $data['ids'])->get() as $vehicle) {
            if ($vehicle->bookings()->exists()) {
                $vehicle->update(['status' => VehicleStatus::HorsService, 'is_active' => false]);
                $archived++;
            } else {
                $vehicle->delete();
                $deleted++;
            }
        }

        $message = [];
        if ($deleted > 0) {
            $message[] = __('admin.vehicle_deleted').' ('.$deleted.')';
        }
        if ($archived > 0) {
            $message[] = __('admin.vehicle_archived').' ('.$archived.')';
        }

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', implode(' ', $message));
    }

    /**
     * Remise en service d'un véhicule archivé ou supprimé.
     *
     * La fiche, les photos et l'historique n'ont jamais quitté la base :
     * l'entreprise rachète rarement des voitures, elle recycle sa flotte.
     * Un clic suffit donc, sans ressaisir le nom ni retéléverser les images.
     */
    public function restore(Vehicle $vehicle): RedirectResponse
    {
        $this->putBackInService($vehicle);

        return redirect()
            ->route('admin.vehicles.index', ['vue' => 'archives'])
            ->with('status', __('admin.vehicle_restored', ['vehicle' => $vehicle->fullName()]));
    }

    /** Remise en service en masse depuis la vue « Archivés ». */
    public function restoreMany(VehicleSelectionRequest $request): RedirectResponse
    {
        $count = 0;

        foreach (Vehicle::query()->withTrashed()->whereIn('id', $request->validated()['ids'])->get() as $vehicle) {
            $this->putBackInService($vehicle);
            $count++;
        }

        return redirect()
            ->route('admin.vehicles.index', ['vue' => 'archives'])
            ->with('status', __('admin.vehicles_restored', ['count' => $count]));
    }

    private function putBackInService(Vehicle $vehicle): void
    {
        if ($vehicle->trashed()) {
            $vehicle->restore();
        }

        $vehicle->update([
            'is_active' => true,
            // Une archive est hors service : la remettre en ligne la rend
            // de nouveau louable. Une maintenance volontaire est conservée.
            'status' => $vehicle->status === VehicleStatus::HorsService
                ? VehicleStatus::Disponible
                : $vehicle->status,
        ]);
    }

    /** Passage rapide de statut depuis la liste (disponible / maintenance / hors service). */
    public function setStatus(SetVehicleStatusRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validated();

        $vehicle->update(['status' => VehicleStatus::from($data['status'])]);

        return back()->with('status', __('admin.vehicle_status_updated'));
    }

    /** @return array<string, mixed> */
    private function validated(SaveVehicleRequest $request): array
    {
        $data = $request->validated();

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

    /**
     * Désigne la photo principale (« grand plan ») du véhicule.
     *
     * La photo principale est celle dont l'order_column est le plus élevé :
     * mainPhotoUrl() trie par order_column décroissant. On la place donc au
     * au-dessus des autres. Si aucune photo n'est choisie mais que des photos
     * viennent d'être ajoutées, la première nouvelle devient principale.
     */
    private function setMainPhoto(Vehicle $vehicle, mixed $mainPhotoId, array $newMedia, ?int $mainPhotoIndex = null): void
    {
        $media = $vehicle->getMedia(Vehicle::MEDIA_GALLERY);

        $chosen = null;

        if ($mainPhotoId !== null && $mainPhotoId !== '') {
            $chosen = $media->firstWhere('id', (int) $mainPhotoId);
        } elseif ($mainPhotoIndex !== null && isset($newMedia[$mainPhotoIndex])) {
            $chosen = $newMedia[$mainPhotoIndex];
        }

        if ($chosen !== null) {
            $maxOrder = (int) $vehicle->getMedia(Vehicle::MEDIA_GALLERY)->max('order_column');
            $chosen->order_column = $maxOrder + 1;
            $chosen->save();

            return;
        }

        // Aucun choix explicite : la première photo nouvellement ajoutée devient
        // la principale, pour que le véhicule ne reste pas sans image à l'affiche.
        if ($newMedia !== []) {
            $chosen = $newMedia[0];
            $maxOrder = (int) $vehicle->getMedia(Vehicle::MEDIA_GALLERY)->max('order_column');
            $chosen->order_column = $maxOrder + 1;
            $chosen->save();
        }
    }

    private function attachPhotos(Request $request, Vehicle $vehicle): array
    {
        $newMedia = [];

        foreach ($request->file('photos', []) as $index => $photo) {
            $media = $vehicle->addMedia($photo)
                ->preservingOriginal()
                ->usingFileName(Str::slug($vehicle->brand.'-'.$vehicle->model).'-'.$index.'.'.$photo->getClientOriginalExtension())
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');

            $newMedia[] = $media;
        }

        return $newMedia;
    }
}
