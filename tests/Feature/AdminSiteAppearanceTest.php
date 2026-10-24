<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSiteAppearanceTest extends TestCase
{
    public function test_manager_can_save_appearance_and_control_homepage_slideshow_order(): void
    {
        Storage::fake('fleet');
        $firstVehicle = $this->vehicle(attributes: ['brand' => 'Toyota', 'model' => 'Corolla']);
        $firstVehicle->addMedia(UploadedFile::fake()->image('first.jpg'))
            ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        $secondVehicle = $this->vehicle(attributes: ['brand' => 'Nissan', 'model' => 'Qashqai']);
        $secondVehicle->addMedia(UploadedFile::fake()->image('second.jpg'))
            ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');

        $manager = $this->staff('gestionnaire');

        $this->actingAs($manager)
            ->get(route('admin.appearance.edit'))
            ->assertOk()
            ->assertSee('Diaporama de l’accueil')
            ->assertSee('Toyota Corolla');

        $this->actingAs($manager)
            ->post(route('admin.appearance.update'), $this->validPayload([
                $secondVehicle->id,
                $firstVehicle->id,
            ]))
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHas('status', 'Apparence du site mise à jour.');

        $this->assertDatabaseHas('settings', [
            'key' => 'site.hero_vehicle_ids',
            'value' => json_encode([$secondVehicle->id, $firstVehicle->id]),
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'nav.home.fr',
            'value' => 'Découvrir',
            'updated_by' => auth()->id(),
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'site.brand_mark',
            'value' => 'NP',
            'updated_by' => auth()->id(),
        ]);

        $html = $this->get('/fr')
            ->assertOk()
            ->assertSee('Découvrir')
            ->assertSee('Nom public')
            ->assertSee('--pdj-site-brand: #123456; --pdj-site-accent: #ABCDEF;', escape: false)
            ->getContent();

        $secondPhotoPosition = strpos($html, (string) $secondVehicle->mainPhotoUrl());
        $firstPhotoPosition = strpos($html, (string) $firstVehicle->mainPhotoUrl());

        $this->assertNotFalse($secondPhotoPosition);
        $this->assertNotFalse($firstPhotoPosition);
        $this->assertTrue($secondPhotoPosition < $firstPhotoPosition);
    }

    public function test_agent_cannot_view_site_appearance_settings(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get(route('admin.appearance.edit'))
            ->assertForbidden();
    }

    public function test_manager_can_upload_a_slideshow_photo_and_it_appears_on_homepage(): void
    {
        Storage::fake('public');
        $manager = $this->staff('gestionnaire');

        $this->actingAs($manager)
            ->get(route('admin.appearance.edit'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', escape: false)
            ->assertSee('name="hero_images[]"', escape: false);

        $this->actingAs($manager)
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'hero_images' => [UploadedFile::fake()->image('accueil.jpg')],
            ])
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHas('status', 'Apparence du site mise à jour.');

        $heroImages = $this->settings()->get('site.hero_uploaded_images', 'array');
        $this->assertCount(1, $heroImages);
        Storage::disk('public')->assertExists($heroImages[0]);

        $this->get('/fr')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($heroImages[0]), escape: false);
    }

    public function test_manager_can_remove_an_uploaded_slideshow_photo(): void
    {
        Storage::fake('public');
        $path = 'hero-slides/ancienne-photo.jpg';
        Storage::disk('public')->put($path, 'image');
        $this->settings()->put('site.hero_uploaded_images', [$path]);

        $this->actingAs($this->staff('gestionnaire'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'remove_hero_images' => [$path],
            ])
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHas('status', 'Apparence du site mise à jour.');

        $this->assertSame([], $this->settings()->get('site.hero_uploaded_images', 'array'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_upload_is_rejected_without_changing_slideshow(): void
    {
        Storage::fake('public');
        $this->settings()->put('site.hero_uploaded_images', []);

        $this->actingAs($this->staff('gestionnaire'))
            ->from(route('admin.appearance.edit'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload(),
                'hero_images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHasErrors([
                'hero_images.0' => 'Chaque fichier du diaporama doit être une image valide.',
            ]);

        $this->assertSame([], $this->settings()->get('site.hero_uploaded_images', 'array'));
        $this->assertSame([], Storage::disk('public')->allFiles('hero-slides'));
    }

    public function test_slideshow_rejects_more_than_four_total_photos(): void
    {
        Storage::fake('public');
        Storage::fake('fleet');
        $vehicleIds = [];
        foreach (range(1, 4) as $number) {
            $vehicle = $this->vehicle();
            $vehicle->addMedia(UploadedFile::fake()->image("vehicle-{$number}.jpg"))
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
            $vehicleIds[] = $vehicle->id;
        }

        $this->actingAs($this->staff('gestionnaire'))
            ->from(route('admin.appearance.edit'))
            ->post(route('admin.appearance.update'), [
                ...$this->validPayload($vehicleIds),
                'hero_images' => [UploadedFile::fake()->image('extra.jpg')],
            ])
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHasErrors([
                'hero_images' => 'Le diaporama peut contenir quatre photos au maximum. Retirez une photo ou un véhicule avant d’en ajouter.',
            ]);

        $this->assertSame([], Storage::disk('public')->allFiles('hero-slides'));
        $this->assertSame([], $this->settings()->get('site.hero_uploaded_images', 'array'));
    }

    public function test_invalid_color_is_rejected_without_saving_other_appearance_changes(): void
    {
        $payload = $this->validPayload();
        $payload['brand_color'] = 'red; background:url(javascript:alert(1))';

        $this->actingAs($this->staff('gestionnaire'))
            ->from(route('admin.appearance.edit'))
            ->post(route('admin.appearance.update'), $payload)
            ->assertRedirect(route('admin.appearance.edit'))
            ->assertSessionHasErrors('brand_color');

        $this->assertDatabaseHas('settings', [
            'key' => 'site.brand_color',
            'value' => '#0A6E4C',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'nav.home.fr',
            'value' => 'Accueil',
        ]);
    }

    public function test_navigation_labels_are_escaped_before_public_display(): void
    {
        $payload = $this->validPayload();
        $payload['nav_home_fr'] = '<script>alert(1)</script>';

        $this->actingAs($this->staff('gestionnaire'))
            ->post(route('admin.appearance.update'), $payload)
            ->assertRedirect(route('admin.appearance.edit'));

        $this->get('/fr')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    public function test_super_admin_can_edit_solo_driver_prices_from_agency_settings(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Chauffeur seul : tarif journalier (FCFA)')
            ->assertSee('Chauffeur seul : tarif réduit (FCFA/jour)');

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.settings.update'), [
                'settings' => [
                    'driver.solo_daily_price' => '9000',
                    'driver.solo_discount_daily_price' => '7000',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'driver.solo_daily_price',
            'value' => '9000',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'driver.solo_discount_daily_price',
            'value' => '7000',
        ]);
        $this->assertSame(9000, $this->settings()->int('driver.solo_daily_price'));
        $this->assertSame(7000, $this->settings()->int('driver.solo_discount_daily_price'));
    }

    /**
     * @param  list<int>  $heroVehicleIds
     * @return array<string, mixed>
     */
    private function validPayload(array $heroVehicleIds = []): array
    {
        return [
            'hero_vehicle_ids' => $heroVehicleIds,
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
