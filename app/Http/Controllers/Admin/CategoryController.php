<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function store(Request $request): RedirectResponse
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

    public function update(Request $request, VehicleCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

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
    private function validated(Request $request, ?VehicleCategory $category = null): array
    {
        $data = $request->validate([
            'name_fr' => ['required', 'string', 'max:80'],
            'name_en' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', Rule::unique('vehicle_categories', 'slug')->ignore($category?->id)],
            'description_fr' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'daily_price' => ['required', 'integer', 'min:1000'],
            'deposit_amount' => ['required', 'integer', 'min:0'],
            'daily_price_holiday' => ['nullable', 'integer', 'min:1000'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'doors' => ['required', 'integer', 'between:2,6'],
            'bags' => ['required', 'integer', 'between:0,9'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

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
