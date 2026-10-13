<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saisonnalité et remises. La règle la plus simple l'emporte : celle dont la
 * fenêtre [starts_on, ends_on] contient la date de début, la plus restrictive d'abord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained('vehicle_categories')->cascadeOnDelete();
            $table->string('label');

            $table->enum('kind', ['season', 'weekend', 'long_stay', 'promo'])
                ->default('season');

            // null = tarif non modifié sur la fenêtre
            $table->unsignedInteger('daily_price')->nullable();
            $table->unsignedInteger('deposit_amount')->nullable();
            $table->unsignedSmallInteger('min_days')->nullable();
            $table->unsignedSmallInteger('max_days')->nullable();

            $table->unsignedTinyInteger('priority')->default(0)
                ->comment('plus élevé = appliqué en premier');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['category_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
