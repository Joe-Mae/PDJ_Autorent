<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Lecture typée des réglages métier.
 *
 * Un seul point d'accès : aucune valeur de tarif, de caution ou de délai n'est
 * écrite en dur ailleurs. La table est petite, tout tient en cache, et le cache
 * est vidé dès qu'un agent enregistre un changement.
 */
class SettingsService
{
    public const CACHE_KEY = 'pdj.settings.all';

    public const DEFAULTS = [
        'agency.name' => 'PDJ-AUTORENT',
        'agency.address' => 'Lomé, Togo',
        'agency.phone' => '+228 90 00 00 00',
        'agency.whatsapp' => '+22890000000',
        'agency.open_time' => '08:00',
        'agency.close_time' => '19:00',

        'general.currency' => 'FCFA',

        'booking.turnaround_hours' => '2',
        'booking.grace_period_hours' => '2',
        'booking.max_active_bookings' => '2',
        'booking.min_age' => '18',
        'booking.min_license_years' => '1',
        'booking.speed_limit_kmh' => '120',
        'booking.free_cancellation_before' => '18:00',
        'booking.late_fee_multiplier' => '1',
        'booking.min_days' => '1',
        'booking.insurance_deductible' => '150000',
        'booking.reference_prefix' => 'PDJ-',

        'driver.block_hours' => '4',
        'driver.block_price' => '7000',
        'driver.auto_check_days' => '5',
        'driver.solo_daily_price' => '15000',
        'driver.solo_discount_daily_price' => '13000',
        'driver.solo_discount_after_days' => '3',
        'driver.with_car_mode' => 'supplement',

        'site.hero_vehicle_ids' => '[]',
        'site.hero_uploaded_images' => '[]',
        'site.brand_color' => '#0A6E4C',
        'site.accent_color' => '#FFC400',
        'site.brand_mark' => 'PDJ',
        'site.brand_label' => 'AUTORENT',
        'site.hero_eyebrow.fr' => 'Location de voitures à Lomé',
        'site.hero_eyebrow.en' => 'Car rental in Lomé',
        'site.footer_tagline.fr' => 'Une agence, un point à Lomé, des prix qui ne changent pas à la réservation.',
        'site.footer_tagline.en' => 'One agency, one spot in Lomé, prices that do not change after booking.',
        'nav.home.fr' => 'Accueil',
        'nav.home.en' => 'Home',
        'nav.fleet.fr' => 'Garage',
        'nav.fleet.en' => 'The fleet',
        'nav.driver.fr' => 'Chauffeur',
        'nav.driver.en' => 'With chauffeur',
        'nav.faq.fr' => 'Questions fréquentes',
        'nav.faq.en' => 'Frequently asked questions',
        'nav.contact.fr' => 'Contact',
        'nav.contact.en' => 'Contact',
        'nav.lookup.fr' => 'Consulter ma réservation',
        'nav.lookup.en' => 'Check my booking',

        'no_show.max_absences' => '2',

        'document.retention_months' => '12',
        'document.identity_retention_months' => '60',
        'document.selfie_enabled' => '1',

        'contact.email' => 'contact@pdj-autorent.tg',
    ];

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    /**
     * @template T
     *
     * @param  'string'|'int'|'bool'|'array'  $type
     * @return ($type is 'int' ? int : ($type is 'bool' ? bool : ($type is 'array' ? array : string)))
     */
    public function get(string $key, ?string $type = null): mixed
    {
        $value = $this->all()[$key] ?? self::DEFAULTS[$key] ?? null;

        if ($value === null) {
            return null;
        }

        return match ($type ?? $this->declaredType($key)) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : (json_decode((string) $value, true) ?: []),
            default => (string) $value,
        };
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function string(string $key): string
    {
        return (string) $this->get($key);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $rows = Cache::remember(self::CACHE_KEY, 300, function () {
            return Setting::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typedValue()])
                ->all();
        });

        return $this->resolved = $rows;
    }

    /**
     * Écrit un réglage, en créant la ligne si elle n'existe pas encore.
     *
     * La colonne `label_fr` est NOT NULL : la créer à la volée à partir de la
     * clé évite l'erreur d'intégrité qui faisait échouer toute écriture sur
     * une clé absente. Le libellé reste modifiable ensuite depuis /admin.
     */
    public function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $this->declaredType($key),
                'group' => str($key)->before('.')->toString(),
                'label_fr' => $this->humanize($key),
            ],
        );

        $this->flush();
    }

    /** « booking.grace_period_hours » → « Grace period hours », lisible en attendant le vrai libellé. */
    private function humanize(string $key): string
    {
        return str($key)->after('.')->replace('_', ' ')->ucfirst()->toString();
    }

    /** @param  array<string, mixed>  $values */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    public function flush(): void
    {
        $this->resolved = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function openHour(): int
    {
        return (int) str($this->string('agency.open_time'))->before(':')->value();
    }

    public function closeHour(): int
    {
        return (int) str($this->string('agency.close_time'))->before(':')->value();
    }

    public function whatsappNumber(): string
    {
        return preg_replace('/\D/', '', $this->string('agency.whatsapp')) ?? '';
    }

    public function formattedPhone(): string
    {
        return $this->string('agency.phone');
    }

    /**
     * Devise affichée. Un blanc-marque hors du Franc CFA change ce réglage,
     * jamais le code.
     */
    public function currency(): string
    {
        return $this->string('general.currency');
    }

    /**
     * Montant formaté pour l'écran : « 150 000 FCFA ».
     *
     * Séparateur de milliers insécable, jamais de décimale : les tarifs sont
     * des entiers, du début à la fin.
     */
    public function money(int $amount): string
    {
        return number_format($amount, 0, '.', ' ').' '.$this->currency();
    }

    private function declaredType(string $key): string
    {
        return match (true) {
            in_array($key, ['site.hero_vehicle_ids', 'site.hero_uploaded_images'], true) => 'array',
            str($key)->startsWith('booking.') && str($key)->endsWith('_hours') => 'int',
            str($key)->startsWith('booking.') && str($key)->endsWith('_count') => 'int',
            $key === 'booking.min_age' => 'int',
            $key === 'booking.min_license_years' => 'int',
            $key === 'booking.min_days' => 'int',
            $key === 'booking.late_fee_multiplier' => 'int',
            $key === 'booking.insurance_deductible' => 'int',
            $key === 'driver.block_hours' => 'int',
            $key === 'driver.block_price' => 'int',
            $key === 'driver.auto_check_days' => 'int',
            str($key)->startsWith('driver.') && str($key)->endsWith('_price') => 'int',
            $key === 'driver.solo_discount_after_days' => 'int',
            $key === 'no_show.max_absences' => 'int',
            $key === 'document.retention_months' => 'int',
            $key === 'document.identity_retention_months' => 'int',
            $key === 'document.selfie_enabled' => 'bool',
            default => 'string',
        };
    }
}
