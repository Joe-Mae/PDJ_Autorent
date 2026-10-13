<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal append-only : qui a fait quoi, sur quel objet, et l'état avant/après.
 * Aucun écran de modification n'est exposé — c'est un registre, pas une table CRUD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80)->comment('booking.confirmed, document.reviewed…');
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
