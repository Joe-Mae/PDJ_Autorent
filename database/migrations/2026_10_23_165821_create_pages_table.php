<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique(); // conditions-generales, mentions-legales, confidentialite, etc.
            $table->string('title_fr', 200);
            $table->string('title_en', 200)->nullable();
            $table->longText('content_fr');
            $table->longText('content_en')->nullable();
            $table->string('meta_description_fr', 300)->nullable();
            $table->string('meta_description_en', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
