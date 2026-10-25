<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La caution devient une décision de l'agence, véhicule par véhicule.
 *
 * Le montant reste porté par la catégorie ; ce drapeau dit seulement si on
 * l'exige pour cette voiture. Les réservations déjà prises gardent le montant
 * figé à leur création, quel que soit le changement ultérieur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('deposit_required')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('deposit_required');
        });
    }
};
