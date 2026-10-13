<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key' => 'site.hero_uploaded_images',
            'value' => '[]',
            'type' => 'json',
            'group' => 'website',
            'label_fr' => 'Photos importées du diaporama accueil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The setting may contain uploaded images chosen by the agency.
    }
};
