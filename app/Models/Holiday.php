<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'label_fr', 'label_en', 'is_agency_closed', 'is_price_surcharged', 'sort_order'])]
class Holiday extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_agency_closed' => 'boolean',
            'is_price_surcharged' => 'boolean',
        ];
    }

    #[Scope]
    protected function between(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereBetween('date', [
            $from instanceof \DateTimeInterface ? $from->format('Y-m-d') : $from,
            $to instanceof \DateTimeInterface ? $to->format('Y-m-d') : $to,
        ]);
    }

    public function label(string $locale = 'fr'): string
    {
        return $locale === 'en' && $this->label_en ? $this->label_en : $this->label_fr;
    }
}
