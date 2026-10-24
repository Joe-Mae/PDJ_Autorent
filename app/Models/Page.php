<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug', 'title_fr', 'title_en', 'content_fr', 'content_en',
    'meta_description_fr', 'meta_description_en', 'is_active', 'sort_order',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function title(string $locale = 'fr'): string
    {
        return $locale === 'en' && $this->title_en ? $this->title_en : $this->title_fr;
    }

    public function content(string $locale = 'fr'): string
    {
        return $locale === 'en' && $this->content_en ? $this->content_en : $this->content_fr;
    }

    public function metaDescription(string $locale = 'fr'): ?string
    {
        return $locale === 'en' && $this->meta_description_en
            ? $this->meta_description_en
            : $this->meta_description_fr;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title_fr');
    }
}
