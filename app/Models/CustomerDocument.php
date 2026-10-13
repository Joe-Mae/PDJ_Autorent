<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Database\Factories\CustomerDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 'booking_id', 'type', 'status', 'disk', 'path',
    'original_filename', 'mime_type', 'size_kb',
    'review_note', 'reviewed_by', 'reviewed_at', 'expires_at', 'purge_after',
])]
class CustomerDocument extends Model
{
    /** @use HasFactory<CustomerDocumentFactory> */
    use HasFactory;

    /** Types acceptés à l'upload — le reste est rejeté côté serveur. */
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    public const MAX_SIZE_KB = 5120;

    /** La table s'appelle `documents` : le nom du modèle décrit l'entité, pas la table. */
    protected $table = 'documents';

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'reviewed_at' => 'datetime',
            'expires_at' => 'date',
            'purge_after' => 'datetime',
            'size_kb' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    #[Scope]
    protected function ofType(Builder $query, DocumentType ...$types): Builder
    {
        return $query->whereIn('type', array_map(fn (DocumentType $t) => $t->value, $types));
    }

    /** File de vérification, la plus urgente en tête. */
    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->whereIn('status', [DocumentStatus::EnAttente->value, DocumentStatus::ARelever->value]);
    }

    /** Document encore bon pour une nouvelle réservation. */
    public function isReusable(): bool
    {
        return $this->status->isReusable()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** URL signée : le fichier vit hors de public/, 15 minutes de validité. */
    public function temporaryUrl(): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(15));
    }

    public function humanSize(): string
    {
        return $this->size_kb >= 1024
            ? number_format($this->size_kb / 1024, 1, ',', ' ').' Mo'
            : $this->size_kb.' Ko';
    }

    protected static function booted(): void
    {
        // La date de purge est posée à la création : la rétention dépend du type.
        static::creating(function (self $document) {
            $document->purge_after ??= now()->addMonths($document->type->retentionMonths());
        });

        static::deleting(function (self $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }
}
