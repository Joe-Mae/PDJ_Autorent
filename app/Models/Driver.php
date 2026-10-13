<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'first_name', 'last_name', 'phone', 'whatsapp', 'photo_path', 'bio',
    'status', 'rating', 'completed_rentals',
])]
class Driver extends Model implements HasMedia
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory, InteractsWithMedia;

    public const MEDIA_PHOTO = 'photo';

    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'rating' => 'integer',
            'completed_rentals' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('status', DriverStatus::Actif->value);
    }

    public function displayName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }
}
