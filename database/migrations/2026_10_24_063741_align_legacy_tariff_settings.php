<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les premières installations ont semé un barème chauffeur plus bas que celui
 * du cahier des charges (5 000 / 8 000 / 6 500). On remet ces trois lignes au
 * tarif officiel, mais uniquement si l'agence ne les a pas déjà modifiées :
 * une valeur personnalisée ne doit jamais être écrasée par une migration.
 */
return new class extends Migration
{
    /** @var array<string, array{legacy: string, target: string}> */
    private const TARIFFS = [
        'driver.block_price' => ['legacy' => '5000', 'target' => '7000'],
        'driver.solo_daily_price' => ['legacy' => '8000', 'target' => '15000'],
        'driver.solo_discount_daily_price' => ['legacy' => '6500', 'target' => '13000'],
    ];

    public function up(): void
    {
        foreach (self::TARIFFS as $key => $values) {
            DB::table('settings')
                ->where('key', $key)
                ->where('value', $values['legacy'])
                ->update(['value' => $values['target'], 'updated_at' => now()]);
        }

        DB::table('settings')
            ->where('key', 'site.brand_label')
            ->where('value', 'Autorent')
            ->update(['value' => 'AUTORENT', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Un barème est un choix d'agence : on ne le rétablit pas à l'envers.
    }
};
