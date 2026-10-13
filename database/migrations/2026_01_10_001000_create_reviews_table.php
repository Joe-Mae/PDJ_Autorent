<?php

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating')->comment('1 à 5');
            $table->unsignedTinyInteger('cleanliness_rating')->nullable();
            $table->unsignedTinyInteger('condition_rating')->nullable();
            $table->unsignedTinyInteger('service_rating')->nullable();

            $table->string('author_name', 100);
            $table->string('author_city', 80)->nullable();
            $table->string('locale', 5)->default('fr');
            $table->text('comment')->nullable();
            $table->string('agency_answer', 500)->nullable();

            $table->enum('status', ReviewStatus::values())->default(ReviewStatus::EnAttente->value);
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['status', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
