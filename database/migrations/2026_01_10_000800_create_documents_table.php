<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pièces clients et photos d'état des lieux.
 *
 * Les fichiers ne sont jamais dans public/ : disk privé, accès par URL signée.
 * Un document appartient à la fois à un client (pièce d'identité réutilisable
 * d'une réservation à l'autre) et à une réservation (usage ponctuel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', DocumentType::values());
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::EnAttente->value);
            $table->string('disk')->default('documents');
            $table->string('path');
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_kb');

            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Pièce d'identité : 5 ans. Permis : jusqu'à son expiration.
            $table->date('expires_at')->nullable();
            $table->timestamp('purge_after')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index(['status', 'created_at'], 'documents_file_attente');
            $table->index('purge_after');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
