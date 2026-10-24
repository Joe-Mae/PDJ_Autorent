<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CustomerDocument;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Traçabilité des accès aux pièces d'identité.
 *
 * Ouvrir la fiche d'un document, c'est ouvrir la pièce d'identité d'une
 * personne : les URL signées naissent là. Sans ligne au journal, l'agence ne
 * peut répondre à personne quand un client demande qui a consulté ses
 * documents, ni distinguer une consultation motivée d'une curiosité.
 *
 * Le journal est append-only : ces tests vérifient qu'une ligne apparaît,
 * avec le bon acteur et le bon objet, et qu'elle n'apparaît nulle part ailleurs.
 */
class DocumentAccessAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::disk('documents')->buildTemporaryUrlsUsing(fn (): string => 'https://example.test/document');
    }

    public function test_ouvrir_la_fiche_d_un_document_est_journalise(): void
    {
        $document = $this->aPendingDocument();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->get(route('admin.documents.show', $document))
            ->assertOk();

        $log = AuditLog::query()->where('event', 'document.accessed')->firstOrFail();

        $this->assertSame($agent->id, $log->user_id);
        $this->assertSame(CustomerDocument::class, $log->auditable_type);
        $this->assertSame($document->id, $log->auditable_id);
        $this->assertNotNull($log->ip_address);
        $this->assertSame('piece_identite', json_decode((string) $log->description, true)['type']);

        // Le journal sans écran est une donnée morte : l'agent doit voir qui a
        // ouvert la pièce, y compris sa propre ouverture en cours.
        $this->actingAs($agent)
            ->get(route('admin.documents.show', $document))
            ->assertOk()
            ->assertSee(__('admin.access_history'))
            ->assertSee($agent->displayName());
    }

    /** La liste des documents à vérifier n'ouvre aucun fichier : rien à journaliser. */
    public function test_consulter_la_file_ne_consulte_aucun_document(): void
    {
        $this->aPendingDocument();

        $this->actingAs($this->staff())
            ->get(route('admin.documents.index'))
            ->assertOk();

        $this->assertSame(0, AuditLog::query()->where('event', 'document.accessed')->count());
    }

    public function test_un_verdict_est_journalise_avec_l_ancien_et_le_nouveau_statut(): void
    {
        $document = $this->aPendingDocument();

        $this->actingAs($this->staff())
            ->post(route('admin.documents.review', $document), [
                'verdict' => 'conforme',
                'note' => 'Permis lisible, en cours de validité.',
            ])
            ->assertRedirect();

        $log = AuditLog::query()->where('event', 'document.reviewed')->firstOrFail();

        $this->assertSame($document->id, $log->auditable_id);
        $this->assertSame(['status' => 'en_attente'], $log->old_values);
        $this->assertSame(['status' => 'conforme'], $log->new_values);
        $this->assertNotNull($log->description);
    }

    /** Le verdict modifie le document : le journal doit garder la trace des deux états. */
    public function test_deux_verdits_de_suite_sont_tous_les_deux_au_journal(): void
    {
        $document = $this->aPendingDocument();
        $agent = $this->staff();

        foreach (['a_relever', 'conforme'] as $verdict) {
            $this->actingAs($agent)
                ->post(route('admin.documents.review', $document), ['verdict' => $verdict])
                ->assertRedirect();
        }

        $logs = AuditLog::query()->where('event', 'document.reviewed')->orderBy('id')->get();

        $this->assertCount(2, $logs);
        $this->assertSame('en_attente', $logs[0]->old_values['status']);
        $this->assertSame('a_relever', $logs[0]->new_values['status']);
        $this->assertSame('a_relever', $logs[1]->old_values['status']);
        $this->assertSame('conforme', $logs[1]->new_values['status']);
    }

    public function test_un_acces_sans_session_est_refuse_et_non_journalise(): void
    {
        $document = $this->aPendingDocument();

        $this->get(route('admin.documents.show', $document))
            ->assertRedirect(route('login', ['locale' => 'fr']));

        $this->assertSame(0, AuditLog::query()
            ->where('event', 'like', 'document.%')
            ->count());
    }

    private function aPendingDocument(): CustomerDocument
    {
        return CustomerDocument::factory()->create([
            'user_id' => null,
            'booking_id' => $this->bookings()->create([
                'vehicle' => $this->vehicle(),
                'period' => $this->period(),
                'user' => $this->customer(),
                'with_driver' => false,
            ])->id,
            'driver_only_booking_id' => null,
            'type' => 'piece_identite',
            'status' => 'en_attente',
        ]);
    }
}
