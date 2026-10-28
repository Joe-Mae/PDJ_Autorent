<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le suivi d'une réservation de chauffeur seul va désormais jusqu'à la fin de
 * la prestation : « en cours » et « terminé » s'ajoutent aux statuts existants.
 *
 * La colonne passe d'ENUM à VARCHAR : ajouter un statut ne doit plus demander
 * un ALTER de type en production, ni une reconstruction de table sous SQLite.
 * L'enum PHP reste la source de vérité et caste la valeur à la lecture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_only_bookings', function (Blueprint $table) {
            // Motif d'annulation ou de refus, pour que la fiche explique
            // toujours pourquoi la prestation ne s'est pas faite.
            $table->string('status_reason', 255)->nullable()->after('status');
        });

        Schema::table('driver_only_bookings', function (Blueprint $table) {
            $table->string('status', 20)->default('en_attente')->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_only_bookings', function (Blueprint $table) {
            $table->dropColumn('status_reason');
        });

        // Les statuts « en cours » et « terminé » seraient perdus en revenant à
        // l'ancien ENUM : on laisse la colonne texte, qui accepte tout.
    }
};
