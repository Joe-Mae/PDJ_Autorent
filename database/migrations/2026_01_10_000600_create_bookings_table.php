<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le pivot du produit.
 *
 * Deux invariants sont portés par le schéma lui-même :
 *  1. les montants sont figés (prix_location, prix_chauffeur, prix_total, caution) ;
 *  2. l'index (vehicle_id, starts_at, ends_at, status) rend la recherche de
 *     chevauchement peu coûteuse, ce qui permet de vérifier la disponibilité
 *     sans materialized view ni cache invalide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 12)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            // Créneau demandé, toujours en Africa/Lome stocké en UTC
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('days')->comment('jours facturables, minimum 1');
            $table->unsignedSmallInteger('pickup_hour')->default(8);
            $table->unsignedSmallInteger('return_hour')->default(19);

            $table->boolean('with_driver')->default(false);
            $table->unsignedSmallInteger('driver_blocks')->default(0)
                ->comment('blocs de 4 h facturés, arrondis au bloc supérieur');
            $table->unsignedInteger('driver_hours')->default(0)
                ->comment('heures de conduite réelles, pour l\'affichage');

            // Montants figés, FCFA entiers
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('price_rental');
            $table->unsignedInteger('price_driver');
            $table->unsignedInteger('price_total');
            $table->unsignedInteger('deposit_amount');
            $table->string('currency', 3)->default('XOF');
            $table->unsignedSmallInteger('holiday_days')->default(0);

            $table->enum('status', BookingStatus::values())->default(BookingStatus::EnAttente->value);
            $table->string('status_reason', 255)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('cancelled_by')->nullable()->comment('client | agence');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Pièces à présenter / venir chercher le jour J
            $table->unsignedInteger('mileage_at_pickup')->nullable();
            $table->unsignedInteger('mileage_at_return')->nullable();
            $table->string('fuel_level_pickup', 20)->nullable();
            $table->string('fuel_level_return', 20)->nullable();
            $table->text('pickup_notes')->nullable();
            $table->text('return_notes')->nullable();

            // Garde-fous anti-fantôme
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('client_confirmed_presence_at')->nullable();
            $table->timestamp('grace_expires_at')->nullable();
            $table->string('public_token', 40)->unique();

            $table->timestamps();

            $table->index(['vehicle_id', 'starts_at', 'ends_at', 'status'], 'bookings_dispo_index');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'starts_at'], 'bookings_pipeline_index');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
