<?php

use App\Enums\DriverOnlyBookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_only_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->string('customer_name', 120);
            $table->string('phone', 30);
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('days');
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('price_total');
            $table->enum('status', DriverOnlyBookingStatus::values())
                ->default(DriverOnlyBookingStatus::EnAttente->value);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['driver_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_only_bookings');
    }
};
