<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveVehicleCategoryRequest;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Catégories et tarifs.
 *
 * Le prix et la caution vivent ici, pas sur le véhicule : changer le tarif d'un
 * SUV change celui de tous les SUV, immédiatement, et les réservations déjà
 * prises gardent le leur parce que le montant est figé à la création.
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => VehicleCategory::query()
                ->withCount('vehicles')
                ->with('pricingRules')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new VehicleCategory(['seats' => 5, 'doors' => 5, 'bags' => 3])]);
    }

    public function store(SaveVehicleCategoryRequest $request): RedirectResponse
    {
        VehicleCategory::query()->create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('status', __('admin.category_created'));
    }

    public function edit(VehicleCategory $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'rules' => $category->pricingRules()->orderByDesc('priority')->get(),
        ]);
    }

    public function update(SaveVehicleCategoryRequest $request, VehicleCategory $category): RedirectResponse
    {
        $category->update($this->validated($request));

        return redirect()->route('admin.categories.index')->with('status', __('admin.category_updated'));
    }

    public function destroy(VehicleCategory $category): RedirectResponse
    {
        if ($category->vehicles()->exists()) {
            // Une catégorie utilisée n'est pas effacée : elle disparaît du site.
            $category->update(['is_active' => false]);

            return back()->with('status', __('admin.category_archived'));
        }

        $category->delete();

        return back()->with('status', __('admin.category_deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(SaveVehicleCategoryRequest $request): array
    {
        $data = $request->validated();

        return array_merge($data, [
            'slug' => $data['slug'] ?? Str::slug($data['name_fr']),

            // Même raison que pour le véhicule : une case décochée n'envoie
            // rien, et `?? true` rendait la catégorie impossible à retirer du
            // site.
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);
    }
}
