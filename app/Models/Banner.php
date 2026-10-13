<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slot', 'title_fr', 'title_en', 'subtitle_fr', 'subtitle_en',
    'cta_label_fr', 'cta_url', 'image_path', 'background_color',
    'starts_at', 'ends_at', 'sort_order', 'is_active',
])]
class Banner extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function live(Builder $query, ?string $slot = null): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($slot, fn (Builder $q) => $q->where('slot', $slot))
            ->orderBy('sort_order');
    }

    public function title(string $locale = 'fr'): string
    {
        return $locale === 'en' && $this->title_en ? $this->title_en : $this->title_fr;
    }

    public function subtitle(string $locale = 'fr'): ?string
    {
        return $locale === 'en' && $this->subtitle_en ? $this->subtitle_en : $this->subtitle_fr;
    }
}
