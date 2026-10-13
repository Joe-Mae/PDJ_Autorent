<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table clé/valeur des réglages métier, pilotés depuis /admin/parametres.
 * Aucune valeur de tarification n'est écrite en dur dans le code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string')
                ->comment('string | int | bool | json | date');
            $table->string('group', 40)->default('general');
            $table->string('label_fr', 150);
            $table->string('label_en', 150)->nullable();
            $table->text('help_fr')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
