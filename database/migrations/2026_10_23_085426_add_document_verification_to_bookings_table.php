<?php

use App\Enums\DocumentVerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('document_verification_status', DocumentVerificationStatus::values())
                ->default(DocumentVerificationStatus::EnAttente->value);
            $table->boolean('identity_original_verified')->default(false);
            $table->boolean('identity_name_matches')->default(false);
            $table->boolean('customer_age_eligible')->default(false);
            $table->boolean('driver_license_original_verified')->default(false);
            $table->boolean('driver_license_category_authorized')->default(false);
            $table->string('driver_license_category', 30)->nullable();
            $table->date('driver_license_issued_at')->nullable();
            $table->date('driver_license_expires_at')->nullable();
            $table->text('document_verification_note')->nullable();
            $table->timestamp('documents_verified_at')->nullable();
            $table->foreignId('documents_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('driver_license_copy_consent_at')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('document_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['document_verification_status']);
            $table->dropConstrainedForeignId('documents_verified_by');
            $table->dropColumn([
                'document_verification_status',
                'identity_original_verified',
                'identity_name_matches',
                'customer_age_eligible',
                'driver_license_original_verified',
                'driver_license_category_authorized',
                'driver_license_category',
                'driver_license_issued_at',
                'driver_license_expires_at',
                'document_verification_note',
                'documents_verified_at',
                'driver_license_copy_consent_at',
            ]);
        });
    }
};
