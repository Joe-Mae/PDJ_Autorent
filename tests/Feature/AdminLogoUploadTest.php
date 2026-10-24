<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Téléversement du logo de l'agence.
 *
 * Le blanc-marque arrive avec son propre logo : il l'importe depuis
 * /admin/apparence, et le monogramme texte n'est plus qu'un repli. Trois
 * choses sont verrouillées ici — le fichier atterrit bien sur le disque
 * public et le réglage pointe dessus, il s'affiche réellement sur le site,
 * et il disparaît du disque quand on le retire (un logo abandonné à la
 * racine du disque public, c'est un fichier que plus personne ne pourra
 * supprimer).
 */
class AdminLogoUploadTest extends TestCase
{
    public function test_super_admin_can_upload_a_logo_and_it_appears_on_the_site(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('admin.appearance.edit'));

        $path = $this->settings()->string('site.logo_path');

        $this->assertStringStartsWith('brand/', $path, 'Le logo doit être stocké dans le dossier brand/.');
        Storage::disk('public')->assertExists($path);

        $this->get('/fr')
            ->assertOk()
            ->assertSee($path);

        $this->actingAs($this->staff('super_admin'))
            ->get(route('admin.appearance.edit'))
            ->assertOk()
            ->assertSee('Retirer le logo actuel');
    }

    public function test_removing_the_logo_deletes_the_file_and_falls_back_to_the_monogram(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('logo.png'),
            ]);

        $path = $this->settings()->string('site.logo_path');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'remove_logo' => 1,
            ])
            ->assertRedirect(route('admin.appearance.edit'));

        $this->assertSame('', $this->settings()->string('site.logo_path'));
        Storage::disk('public')->assertMissing($path);

        // Repli sur le monogramme : le logo n'apparaît plus nulle part.
        $this->get('/fr')
            ->assertOk()
            ->assertDontSee($path);
    }

    public function test_a_replacing_upload_keeps_only_the_new_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('first.png'),
            ]);

        $firstPath = $this->settings()->string('site.logo_path');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('second.png'),
            ]);

        $secondPath = $this->settings()->string('site.logo_path');

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath, 'L’ancien logo doit être supprimé après enregistrement du nouveau.');
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->create('shell.php', 12),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertSame('', $this->settings()->string('site.logo_path'));
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'brand_color' => '#123456',
            'accent_color' => '#ABCDEF',
            'brand_mark' => 'NP',
            'brand_label' => 'Nom public',
            'hero_eyebrow_fr' => 'Location à Lomé',
            'hero_eyebrow_en' => 'Car rental in Lome',
            'footer_tagline_fr' => 'Une signature française.',
            'footer_tagline_en' => 'An English tagline.',
            'nav_home_fr' => 'Découvrir',
            'nav_home_en' => 'Discover',
            'nav_fleet_fr' => 'Garage',
            'nav_fleet_en' => 'Fleet',
            'nav_driver_fr' => 'Chauffeur',
            'nav_driver_en' => 'Driver',
            'nav_faq_fr' => 'FAQ',
            'nav_faq_en' => 'FAQ',
            'nav_contact_fr' => 'Contact',
            'nav_contact_en' => 'Contact',
            'nav_lookup_fr' => 'Consulter ma réservation',
            'nav_lookup_en' => 'Check my booking',
        ];
    }
}
