<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            $table->string('slot', 40)->default('home_hero')
                ->comment('home_hero | home_middle | home_bottom');
            $table->string('title_fr', 150);
            $table->string('title_en', 150)->nullable();
            $table->text('subtitle_fr')->nullable();
            $table->text('subtitle_en')->nullable();
            $table->string('cta_label_fr', 60)->nullable();
            $table->string('cta_url', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->string('background_color', 9)->default('#0B6B3A');

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['slot', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
