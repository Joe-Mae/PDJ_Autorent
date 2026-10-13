<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le profil client est plus large que le users par défaut de Laravel :
 * on a besoin du téléphone (clé de contact), de la date de naissance (contrôle d'âge)
 * et d'un compteur d'absences (anti-réservation fantôme).
 *
 * Pas de colonne `role_id` ici : le rôle est porté par spatie/laravel-permission
 * dans la table `model_has_roles`, qui n'existe qu'à la migration du 29 septembre.
 * Une clé étrangère vers `roles` depuis cette migration échouait sur MySQL
 * (table pas encore créée) et passait inaperçue sur SQLite. Une colonne que
 * rien ne lit n'a pas sa place dans le schéma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone', 20)->unique()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('preferred_locale', 5)->default('fr')->after('birth_date');

            // Anti-réservation fantôme
            $table->unsignedTinyInteger('no_show_count')->default(0)->after('preferred_locale');
            $table->timestamp('blocked_at')->nullable()->after('no_show_count');
            $table->string('block_reason', 255)->nullable()->after('blocked_at');
            $table->boolean('prepayment_required')->default(false)->after('block_reason');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_name']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'birth_date', 'preferred_locale',
                'no_show_count', 'blocked_at', 'block_reason', 'prepayment_required',
            ]);
        });
    }
};
