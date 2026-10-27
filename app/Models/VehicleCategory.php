<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Database\Factories\VehicleCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'name_fr', 'name_en', 'slug', 'description_fr', 'description_en',
    'daily_price', 'deposit_amount', 'daily_price_holiday',
    'seats', 'doors', 'bags', 'sort_order', 'is_active', 'is_featured',
])]
class VehicleCategory extends Model
{
    /** @use HasFactory<VehicleCategoryFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'category_id');
    }

    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Vehicle::class, 'category_id', 'category_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('vehicle_categories.is_active', true);
    }

    /** Véhicules actuellement réservables par le public. */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('vehicles.is_active', true)
            ->whereIn('vehicles.status', VehicleStatus::bookableValues());
    }

    public function name(string $locale = 'fr'): string
    {
        return $locale === 'en' && $this->name_en ? $this->name_en : $this->name_fr;
    }

    public function description(string $locale = 'fr'): ?string
    {
        return $locale === 'en' && $this->description_en ? $this->description_en : $this->description_fr;
    }

    /** Tarif applicable, saisi ou vide si la catégorie est vide. */
    public function deposit(): int
    {
        return (int) $this->deposit_amount;
    }
}
