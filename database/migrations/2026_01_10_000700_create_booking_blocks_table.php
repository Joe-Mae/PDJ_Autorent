<?php

use App\Enums\BlockReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indisponibilités décidées par l'agence (garantie, contrôle technique, accident).
 * Assez proche d'une réservation pour sharing le même test de chevauchement, mais
 * séparée parce qu'elle n'a ni client, ni prix, ni confirmation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('reason', BlockReason::values())->default(BlockReason::Maintenance->value);
            $table->string('note', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'starts_at', 'ends_at'], 'blocks_dispo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_blocks');
    }
};
