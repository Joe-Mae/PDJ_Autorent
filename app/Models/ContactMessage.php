<?php

namespace App\Models;

use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'phone', 'email', 'message',
    'status', 'admin_notes', 'handled_by', 'handled_at',
])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    #[Scope]
    protected function byStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', 'nouveau');
    }

    public function isNew(): bool
    {
        return $this->status === 'nouveau';
    }

    public function markAsRead(?User $user = null): self
    {
        $this->update([
            'status' => 'lu',
            'handled_by' => $user?->id,
            'handled_at' => now(),
        ]);

        return $this;
    }

    public function markAsProcessed(?User $user = null): self
    {
        $this->update([
            'status' => 'traite',
            'handled_by' => $user?->id,
            'handled_at' => now(),
        ]);

        return $this;
    }
}
