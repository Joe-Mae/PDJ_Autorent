<?php

namespace App\Models;

use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'key', 'value', 'type', 'group', 'label_fr', 'label_en', 'help_fr', 'updated_by',
])]
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            try {
                app(SettingsService::class)->flush();
            } catch (\Throwable $e) {
                Cache::forget(SettingsService::CACHE_KEY);
            }
        });

        static::deleted(function (Setting $setting) {
            try {
                app(SettingsService::class)->flush();
            } catch (\Throwable $e) {
                Cache::forget(SettingsService::CACHE_KEY);
            }
        });
    }

    #[Scope]
    protected function inGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group)->orderBy('key');
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true) ?: [],
            default => $this->value,
        };
    }
}
