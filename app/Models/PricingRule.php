<?php

namespace App\Models;

use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category_id', 'label', 'kind', 'daily_price', 'deposit_amount',
    'min_days', 'max_days', 'priority', 'is_active',
])]
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }

    public function appliesTo(int $days): bool
    {
        if ($this->min_days !== null && $days < $this->min_days) {
            return false;
        }

        return ! ($this->max_days !== null && $days > $this->max_days);
    }
}
