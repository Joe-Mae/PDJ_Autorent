<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'event', 'auditable_type', 'auditable_id',
    'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function about(Builder $query, Model $model): Builder
    {
        return $query->where('auditable_type', $model::class)->where('auditable_id', $model->getKey());
    }

    public function changedFields(): array
    {
        if ($this->old_values === null || $this->new_values === null) {
            return [];
        }

        return array_keys(array_filter(
            $this->old_values,
            fn ($value, $key) => ($this->new_values[$key] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));
    }
}
