<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Textes du bloc chauffeur, éditables depuis « Contenus du site ».
 *
 * Le repli compte autant que l'enregistrement : un champ vidé doit rendre la
 * main aux traductions livrées, jamais laisser un trou sur l'accueil.
 */
class AdminContentTextsTest extends TestCase
{
    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'premium_badge_fr' => 'NOS OPTIONS',
            'title_fr' => 'Un chauffeur pour votre mariage',
            'intro_fr' => 'Nous conduisons les mariés et leurs invités.',
            'point1_fr' => 'Avec voiture : :price par jour.',
            'point2_fr' => 'Sans voiture, à la journée.',
            'point3_fr' => 'Mariages et grandes occasions.',
            'point4_fr' => 'Permis non demandé.',
            'cta_fr' => 'Réserver un chauffeur',
            'page_intro_fr' => 'Page chauffeur personnalisée.',
            'premium_badge_en' => 'OUR OPTIONS',
            'title_en' => 'A chauffeur for your wedding',
            'intro_en' => 'We drive the bride and groom and their guests.',
            'point1_en' => 'With a car: :price per day.',
            'point2_en' => 'Without a car, by the day.',
            'point3_en' => 'Weddings and big occasions.',
            'point4_en' => 'No licence needed.',
            'cta_en' => 'Book a chauffeur',
            'page_intro_en' => 'Custom chauffeur page.',
        ];
    }

    public function test_le_gestionnaire_peut_modifier_le_bloc_chauffeur_de_l_accueil(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->get(route('admin.contents.index'))
            ->assertOk()
            ->assertSee(route('admin.contents.driver.edit'), escape: false);

        $this->actingAs($this->staff('gestionnaire'))
            ->get(route('admin.contents.driver.edit'))
            ->assertOk()
            ->assertSee('OPTIONS PREMIUM');

        $this->actingAs($this->staff('gestionnaire'))
            ->post(route('admin.contents.driver.update'), $this->payload())
            ->assertRedirect(route('admin.contents.driver.edit'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'content.driver.title.fr',
            'value' => 'Un chauffeur pour votre mariage',
        ]);

        $this->get('/fr')
            ->assertOk()
            ->assertSee('NOS OPTIONS')
            ->assertSee('Un chauffeur pour votre mariage')
            ->assertSee('Nous conduisons les mariés et leurs invités.')
            ->assertSee('Avec voiture : 7 000 FCFA par jour.')
            ->assertSee('Réserver un chauffeur');

        $this->get('/en')
            ->assertOk()
            ->assertSee('OUR OPTIONS')
            ->assertSee('A chauffeur for your wedding');

        $this->get('/fr/location-avec-chauffeur')
            ->assertOk()
            ->assertSee('Page chauffeur personnalisée.');
    }

    public function test_un_champ_vide_revient_au_texte_livre(): void
    {
        $this->settings()->put('content.driver.title.fr', 'Titre maison');

        $this->get('/fr')->assertSee('Titre maison');

        $this->actingAs($this->staff('gestionnaire'))
            ->post(route('admin.contents.driver.update'), ['title_fr' => ''])
            ->assertRedirect(route('admin.contents.driver.edit'));

        $this->get('/fr')
            ->assertSee(__('driver.title'))
            ->assertDontSee('Titre maison');
    }

    public function test_l_agent_d_accueil_ne_peut_pas_modifier_les_textes(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get(route('admin.contents.driver.edit'))
            ->assertForbidden();

        $this->actingAs($this->staff('agent_accueil'))
            ->post(route('admin.contents.driver.update'), ['title_fr' => 'Texte interdit'])
            ->assertForbidden();

        $this->get('/fr')->assertDontSee('Texte interdit');
    }

    public function test_les_textes_sont_limites_et_echappes(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->from(route('admin.contents.driver.edit'))
            ->post(route('admin.contents.driver.update'), ['title_fr' => str_repeat('a', 121)])
            ->assertSessionHasErrors('title_fr');

        $this->actingAs($this->staff('gestionnaire'))
            ->post(route('admin.contents.driver.update'), ['title_fr' => '<script>alert(1)</script>'])
            ->assertRedirect(route('admin.contents.driver.edit'));

        $this->get('/fr')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }
}
