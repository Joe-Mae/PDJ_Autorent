<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\CustomerDocument;
use App\Services\SettingsService;
use Tests\TestCase;

/**
 * Durée de conservation des documents, réglable par l'agence.
 *
 * La purge est automatique et définitive : un réglage ignoré laisserait des
 * pièces d'identité en base au-delà de la durée annoncée au client, et un
 * réglage mal typé les ferait disparaître trop tôt. Les deux durées sont
 * distinctes parce que les pièces d'état civil ne suivent pas la même règle
 * qu'un selfie — les confondre est exactement le bug que ce test empêche.
 */
class DocumentRetentionTest extends TestCase
{
    public function test_les_pieces_d_identite_suivent_leur_propre_duree_de_conservation(): void
    {
        $this->assertSame(60, DocumentType::IdentityCard->retentionMonths());
        $this->assertSame(60, DocumentType::DriverLicense->retentionMonths());
        $this->assertSame(12, DocumentType::Selfie->retentionMonths());
        $this->assertSame(12, DocumentType::Contract->retentionMonths());
        $this->assertSame(12, DocumentType::VehiclePhoto->retentionMonths());
    }

    public function test_changer_le_reglage_change_la_duree_calculee(): void
    {
        $this->settings()->put('document.identity_retention_months', 24);
        $this->settings()->put('document.retention_months', 3);

        $this->assertSame(24, DocumentType::IdentityCard->retentionMonths());
        $this->assertSame(24, DocumentType::DriverLicense->retentionMonths());
        $this->assertSame(3, DocumentType::Selfie->retentionMonths());
    }

    /**
     * La date de purge est figée à la création : c'est elle que la tâche
     * nocturne scrute. Un délai non respecté ici ne se rattrape pas.
     */
    public function test_la_date_de_purge_de_une_piece_d_identite_est_recalculee(): void
    {
        $this->settings()->put('document.identity_retention_months', 24);

        $document = $this->aDocument(DocumentType::IdentityCard);

        $this->assertPurgeDateIs(now()->addMonths(24), $document);
    }

    public function test_la_date_de_purge_d_un_document_courant_est_recalculee(): void
    {
        $this->settings()->put('document.retention_months', 3);

        $document = $this->aDocument(DocumentType::Selfie);

        $this->assertPurgeDateIs(now()->addMonths(3), $document);
    }

    /** Comparé à la seconde : la colonne ne stocke pas les microsecondes. */
    private function assertPurgeDateIs(\DateTimeInterface $expected, CustomerDocument $document): void
    {
        $this->assertNotNull($document->purge_after, 'purge_after non renseignée.');
        $this->assertSame(
            $expected->format('Y-m-d H:i:s'),
            $document->purge_after->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Un réglage illisible rendrait retentionMonths() faux, donc la purge
     * aussi. Les deux clés doivent rester dans les défauts déclarés.
     */
    public function test_les_deux_reglages_de_retention_sont_declares(): void
    {
        $this->assertArrayHasKey('document.retention_months', SettingsService::DEFAULTS);
        $this->assertArrayHasKey('document.identity_retention_months', SettingsService::DEFAULTS);

        $this->assertIsInt($this->settings()->int('document.retention_months'));
        $this->assertIsInt($this->settings()->int('document.identity_retention_months'));
    }

    private function aDocument(DocumentType $type): CustomerDocument
    {
        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(),
            'user' => $this->customer(),
            'with_driver' => false,
        ]);

        return CustomerDocument::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'type' => $type,
        ]);
    }
}
