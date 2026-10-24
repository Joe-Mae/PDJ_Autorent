<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Page;
use Tests\TestCase;

/**
 * Contenus juridiques et messages de contact.
 *
 * Les pages légales ont une version livrée avec l'application. Dès qu'une page
 * est publiée en base, c'est elle qui est servie ; si elle est désactivée, on
 * retombe sur la version d'origine. C'est ce repli qui garantit qu'une page ne
 * disparaît jamais parce qu'un contenu a été oublié.
 */
class PageContentTest extends TestCase
{
    public function test_une_page_publiee_en_base_remplace_le_texte_livre(): void
    {
        Page::factory()->create([
            'slug' => 'conditions-generales',
            'title_fr' => 'Conditions générales personnalisées',
            'content_fr' => '<p>Texte propre à cette agence.</p>',
        ]);

        $this->get('/fr/conditions-generales')
            ->assertOk()
            ->assertSee('Conditions générales personnalisées')
            ->assertSee('Texte propre à cette agence.', escape: false);
    }

    public function test_une_page_desactivee_laisse_le_texte_livre(): void
    {
        Page::factory()->inactive()->create([
            'slug' => 'conditions-generales',
            'title_fr' => 'Brouillon jamais publié',
            'content_fr' => '<p>Ce texte ne doit pas apparaître.</p>',
        ]);

        $this->get('/fr/conditions-generales')
            ->assertOk()
            ->assertDontSee('Brouillon jamais publié')
            ->assertSee(__('page.terms_title'));
    }

    public function test_la_page_de_confidentialite_suit_la_langue_de_la_page(): void
    {
        Page::factory()->create([
            'slug' => 'confidentialite',
            'title_fr' => 'Confidentialité maison',
            'title_en' => 'Our privacy policy',
            'content_fr' => '<p>Version française.</p>',
            'content_en' => '<p>English version.</p>',
        ]);

        $this->get('/fr/confidentialite')
            ->assertSee('Confidentialité maison')
            ->assertSee('Version française.', escape: false);

        $this->get('/en/confidentialite')
            ->assertSee('Our privacy policy')
            ->assertSee('English version.', escape: false);
    }

    public function test_le_formulaire_de_contact_enregistre_le_message(): void
    {
        $this->post('/fr/contact', [
            'name' => 'Ama K.',
            'phone' => '+228 90 11 22 33',
            'email' => 'ama@example.com',
            'message' => 'Je cherche une voiture pour trois jours.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ama K.',
            'phone' => '+228 90 11 22 33',
            'email' => 'ama@example.com',
            'status' => 'nouveau',
        ]);

        $message = ContactMessage::query()->firstOrFail();
        $this->assertTrue($message->isNew());
    }

    public function test_un_message_sans_coordonnees_est_refuse(): void
    {
        $this->post('/fr/contact', [
            'message' => 'Bonjour',
        ])->assertSessionHasErrors(['name', 'phone']);

        $this->assertDatabaseCount('contact_messages', 0);
    }
}
