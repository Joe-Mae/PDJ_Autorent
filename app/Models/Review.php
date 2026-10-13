<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id', 'user_id', 'vehicle_id',
    'rating', 'cleanliness_rating', 'condition_rating', 'service_rating',
    'author_name', 'author_city', 'locale', 'comment', 'agency_answer',
    'status', 'published_by',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'rating' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Publiee->value);
    }

    #[Scope]
    protected function bestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('rating')->orderByDesc('created_at');
    }

    public function hasAnswer(): bool
    {
        return filled($this->agency_answer);
    }
}
