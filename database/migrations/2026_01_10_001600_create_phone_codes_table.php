<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes de verification SMS / WhatsApp. On ne garde que le hash : une fuite de la
 * table ne permet pas de recuperer les codes des comptes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_codes', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20);
            $table->string('code_hash', 60);
            $table->enum('channel', ['sms', 'whatsapp'])->default('whatsapp');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->ipAddress('ip')->nullable();

            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_codes');
    }
};
