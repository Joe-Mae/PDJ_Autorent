<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le planning de disponibilité est retiré : les blocages d'atelier n'ont plus
 * de raison d'être saisis, et la table n'est plus lue par personne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('booking_blocks');
    }

    public function down(): void
    {
        Schema::create('booking_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 30)->default('maintenance');
            $table->string('note', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'starts_at', 'ends_at'], 'blocks_dispo_index');
        });
    }
};
