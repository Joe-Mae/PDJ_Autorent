<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'category_id', 'brand', 'model', 'plate', 'year',
    'transmission', 'fuel', 'seats', 'doors', 'bags', 'air_conditioning',
    'features', 'description_fr', 'description_en',
    'mileage', 'technical_control_at', 'next_technical_control_at', 'fuel_level',
    'status', 'is_active',
])]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const MEDIA_GALLERY = 'gallery';

    public const MEDIA_STATE_OF_VEHICLE = 'etat_des_lieux';

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'air_conditioning' => 'boolean',
            'is_active' => 'boolean',
            'year' => 'integer',
            'seats' => 'integer',
            'technical_control_at' => 'date',
            'next_technical_control_at' => 'date',
            'status' => VehicleStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    #[Scope]
    protected function bookable(Builder $query): Builder
    {
        return $query->where('vehicles.is_active', true)
            ->whereIn('vehicles.status', VehicleStatus::bookableValues());
    }

    #[Scope]
    protected function available(Builder $query, Booking $booking): Builder
    {
        return $query->whereDoesntHave('bookings', fn (Builder $q) => $q
            ->active()
            ->whereColumn('bookings.starts_at', '<', $booking->ends_at)
            ->whereColumn('bookings.ends_at', '>', $booking->starts_at));
    }

    public function displayName(): string
    {
        return trim("{$this->brand} {$this->model}");
    }

    public function fullName(): string
    {
        return "{$this->displayName()} ({$this->year})";
    }

    public function slug(): string
    {
        return Str::slug($this->brand.'-'.$this->model.'-'.$this->year);
    }

    public function mainPhotoUrl(): ?string
    {
        $media = $this->getMedia(self::MEDIA_GALLERY)
            ->sortBy([['order_column', 'desc'], ['id', 'desc']])
            ->first();

        return $media?->getUrl();
    }

    public function isBookable(): bool
    {
        return $this->is_active && $this->status->isBookable();
    }

    public function transmissionLabel(): string
    {
        return $this->transmission === 'automatique'
            ? __('vehicle.transmission.automatique')
            : __('vehicle.transmission.manuelle');
    }

    public function fuelLabel(): string
    {
        return __('vehicle.fuel.'.$this->fuel);
    }
}
