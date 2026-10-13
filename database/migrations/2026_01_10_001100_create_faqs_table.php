<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();

            $table->string('category_fr', 80);
            $table->string('category_en', 80);
            $table->string('question_fr');
            $table->string('question_en');
            $table->text('answer_fr');
            $table->text('answer_en')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['is_published', 'category_fr', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
