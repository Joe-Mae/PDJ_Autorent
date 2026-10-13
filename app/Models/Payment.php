<?php

namespace App\Models;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'booking_id', 'kind', 'method', 'amount', 'currency', 'reference',
    'recorded_by', 'recorded_at', 'note', 'refunds_payment_id', 'deducted_amount',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'method' => PaymentMethod::class,
            'recorded_at' => 'datetime',
            'amount' => 'integer',
            'deducted_amount' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function refunds(): HasOne
    {
        return $this->hasOne(self::class, 'refunds_payment_id');
    }

    #[Scope]
    protected function ofKind(Builder $query, PaymentKind ...$kinds): Builder
    {
        return $query->whereIn('kind', array_map(fn (PaymentKind $k) => $k->value, $kinds));
    }

    #[Scope]
    protected function today(Builder $query): Builder
    {
        return $query->whereDate('recorded_at', today());
    }

    /** Montant net réellement remis au client sur une restitution de caution. */
    public function netRefunded(): int
    {
        return (int) $this->amount;
    }

    public function isReversal(): bool
    {
        return $this->kind->isCashOut();
    }
}
