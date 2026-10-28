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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'category_id', 'brand', 'model', 'plate', 'year',
    'transmission', 'fuel', 'seats', 'doors', 'bags', 'air_conditioning',
    'features', 'description_fr', 'description_en',
    'mileage', 'technical_control_at', 'next_technical_control_at', 'fuel_level',
    'status', 'is_active', 'deposit_required',
])]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const MEDIA_GALLERY = 'gallery';

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
            'deposit_required' => 'boolean',
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

    /** Véhicules réservables maintenant : présents à l'agence et libres. */
    #[Scope]
    protected function bookable(Builder $query): Builder
    {
        return $query->where('vehicles.is_active', true)
            ->whereIn('vehicles.status', VehicleStatus::bookableValues());
    }

    /**
     * Véhicules visibles dans la flotte publique, y compris ceux partis en
     * location : ils restent au catalogue, avec un badge « indisponible ».
     */
    #[Scope]
    protected function listed(Builder $query): Builder
    {
        return $query->where('vehicles.is_active', true)
            ->whereIn('vehicles.status', VehicleStatus::listedValues());
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

    /**
     * Description saisie dans l'administration, dans la langue du visiteur.
     * Si la traduction manque, on sert l'autre langue plutôt qu'un blanc :
     * mieux vaut un texte en français qu'une fiche muette.
     */
    public function description(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $localized = $locale === 'en' ? $this->description_en : $this->description_fr;

        if (filled($localized)) {
            return $localized;
        }

        return filled($this->description_fr) ? $this->description_fr : $this->description_en;
    }

    public function slug(): string
    {
        return Str::slug($this->brand.'-'.$this->model.'-'.$this->year);
    }

    /**
     * Photos de la galerie, dans l'ordre choisi par l'agence : la principale
     * d'abord, puis les suivantes. C'est cette liste que la fiche et le
     * diaporama parcourent, pour ne jamais se contenter d'une seule image.
     *
     * @return Collection<int, Media>
     */
    public function galleryPhotos(): Collection
    {
        return $this->getMedia(self::MEDIA_GALLERY)
            ->sortBy([['order_column', 'desc'], ['id', 'desc']])
            ->values();
    }

    public function mainPhotoUrl(): ?string
    {
        return $this->galleryPhotos()->first()?->getUrl();
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
