<?php

namespace App\Models;

use App\Enums\DriverOnlyBookingStatus;
use Database\Factories\DriverOnlyBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference', 'customer_name', 'phone', 'starts_at', 'ends_at', 'days',
    'daily_price', 'price_total', 'status', 'status_reason', 'driver_id',
])]
class DriverOnlyBooking extends Model
{
    /** @use HasFactory<DriverOnlyBookingFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'days' => 'integer',
            'daily_price' => 'integer',
            'price_total' => 'integer',
            'status' => DriverOnlyBookingStatus::class,
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** Pièces déposées par le client : identité, et rien d'autre en ligne. */
    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DriverOnlyBookingStatus::EnAttente->value,
            DriverOnlyBookingStatus::Confirmee->value,
            DriverOnlyBookingStatus::EnCours->value,
        ]);
    }

    #[Scope]
    protected function forPeriod(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }
}
