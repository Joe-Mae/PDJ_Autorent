<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\CustomerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerDocument>
 */
class CustomerDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'booking_id' => null,
            'driver_only_booking_id' => null,
            'type' => DocumentType::IdentityCard,
            'status' => DocumentStatus::EnAttente,
            'disk' => 'documents',
            'path' => 'tests/'.Str::random(40).'.jpg',
            'original_filename' => 'piece.jpg',
            'mime_type' => 'image/jpeg',
            'size_kb' => 120,
        ];
    }
}
