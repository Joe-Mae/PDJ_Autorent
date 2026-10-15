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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customer_name', 160)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email')->nullable();
            $table->text('customer_comment')->nullable();
            $table->string('guest_access_token_hash', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['guest_access_token_hash']);
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_comment',
                'guest_access_token_hash',
            ]);
        });
    }
};
