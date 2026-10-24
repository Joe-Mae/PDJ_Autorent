<?php

use App\Models\CustomerDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les réservations se font désormais sans compte : une pièce déposée par un
 * visiteur n'est rattachée qu'à sa réservation, pas à un utilisateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Le retour arrière n'a de sens que s'il ne reste aucune pièce invitée.
        CustomerDocument::query()->whereNull('user_id')->delete();

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
