<?php

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rien n'est encaissé en ligne. Cette table trace ce que l'agent a saisi au
 * guichet, pour la caisse du jour et la restitution de caution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', PaymentKind::values());
            $table->enum('method', PaymentMethod::values())->default(PaymentMethod::Especes->value);

            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('reference', 100)->nullable()
                ->comment('référence de la transaction mobile money ou du terminal');

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->string('note', 255)->nullable();

            // Restitution de caution : payment_id pointe vers l'encaissement d'origine
            $table->foreignId('refunds_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedInteger('deducted_amount')->default(0)
                ->comment('prélèvements sur caution : carburant, dégâts, retard');

            $table->timestamps();

            $table->index(['recorded_at', 'kind']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
