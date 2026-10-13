<?php

use App\Enums\VehicleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->string('brand', 60);
            $table->string('model', 80);
            $table->string('plate', 20)->unique();
            $table->unsignedSmallInteger('year')->default(2024);

            // Caractéristiques qui servent de filtres côté client
            $table->enum('transmission', ['manuelle', 'automatique'])->default('manuelle');
            $table->enum('fuel', ['essence', 'diesel', 'hybride', 'electrique'])->default('essence');
            $table->unsignedSmallInteger('seats')->default(5);
            $table->unsignedSmallInteger('doors')->default(5);
            $table->unsignedSmallInteger('bags')->default(2);
            $table->unsignedSmallInteger('air_conditioning')->default(1);

            $table->text('features')->nullable();          // JSON : GPS, bluetooth, cruise…
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            // Suivi exploitation
            $table->unsignedInteger('mileage')->default(0);
            $table->date('technical_control_at')->nullable();
            $table->date('next_technical_control_at')->nullable();
            $table->string('fuel_level', 20)->default('full');  // niveau au retrait

            $table->enum('status', VehicleStatus::values())->default(VehicleStatus::Disponible->value)
                ->comment('un seul statut concurrent à la fois : voir VehicleStatus::beats()');

            $table->boolean('is_active')->default(true);
            $table->softDeletes();

            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['transmission', 'fuel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
