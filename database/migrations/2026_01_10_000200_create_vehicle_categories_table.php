<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La catégorie porte le tarif et la caution. Le modèle exact (Toyota Corolla, Dacia
 * Logan) n'a aucun impact sur le prix : deux Corolla coûtent le même.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name_fr');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            // Tarifs en FCFA, entiers, jamais de centimes.
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('deposit_amount');
            $table->unsignedInteger('daily_price_holiday')->nullable();

            $table->unsignedSmallInteger('seats')->default(5);
            $table->unsignedSmallInteger('doors')->default(5);
            $table->unsignedSmallInteger('bags')->default(2);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_categories');
    }
};
