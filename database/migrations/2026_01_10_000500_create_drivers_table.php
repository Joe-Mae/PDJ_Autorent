<?php

use App\Enums\DriverStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le chauffeur est une ressource, pas une option décorative : on ne peut pas en
 * vendre plus qu'on n'en a. La table reste volontairement minimaliste — les
 * réservations font foi pour les créneaux occupés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 20)->unique();
            $table->string('whatsapp', 20)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();

            $table->enum('status', DriverStatus::values())->default(DriverStatus::Actif->value);
            $table->unsignedTinyInteger('rating')->nullable()->comment('note sur 5');
            $table->unsignedInteger('completed_rentals')->default(0);

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
