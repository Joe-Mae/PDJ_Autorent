<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jours fériés togolais etfermeture de l'agence.
 * N'affectent pas la disponibilité (l'agence peut louer un dimanche) : ils servent
 * au tarif majoré et à l'interdiction d'encaisser ce jour-là.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            $table->string('label_fr', 120);
            $table->string('label_en', 120)->nullable();
            $table->boolean('is_agency_closed')->default(false);
            $table->boolean('is_price_surcharged')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
