<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une demande de chauffeur seul est une réservation à part entière : elle doit
 * donc porter, elle aussi, la pièce d'identité du client. La colonne reste
 * nullable pour ne pas casser les pièces déjà rattachées à une réservation
 * de véhicule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('driver_only_booking_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('driver_only_bookings')
                ->nullOnDelete();

            $table->index(['driver_only_booking_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['driver_only_booking_id']);
            $table->dropIndex(['driver_only_booking_id', 'type']);
            $table->dropColumn('driver_only_booking_id');
        });
    }
};
