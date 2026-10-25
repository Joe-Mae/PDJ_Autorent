<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * État des lieux au retrait et au retour.
 *
 * L'agent inspecte la voiture à vue d'œil. Une voiture intacte ne demande
 * aucune photo ; dès qu'une rayure est constatée, la description et les
 * quatre photos deviennent obligatoires. Ces photos sont la preuve qui
 * protège l'agence en cas de litige.
 */
class ConditionReportTest extends TestCase
{
    public function test_un_etat_des_lieux_sans_dommage_n_exige_aucune_photo(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
                'damage_present' => '0',
            ])
            ->assertRedirect(route('agent.pickup.payment', $booking))
            ->assertSessionHasNoErrors();

        $booking->refresh();

        $this->assertSame(12000, (int) $booking->mileage_at_pickup);
        $this->assertSame('full', $booking->fuel_level_pickup);
        $this->assertNull($booking->pickup_notes);
        $this->assertSame(0, $booking->getMedia(Booking::MEDIA_CONDITION_REPORT)->count());
    }

    /** Un navigateur peut envoyer un tableau vide : il ne doit pas bloquer. */
    public function test_un_tableau_de_photos_vide_passe_sans_dommage(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
                'damage_present' => '0',
                'photos' => [],
            ])
            ->assertRedirect(route('agent.pickup.payment', $booking))
            ->assertSessionHasNoErrors();
    }

    public function test_un_dommage_sans_photo_est_refuse(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->from(route('agent.pickup.condition', $booking))
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
                'damage_present' => '1',
                'damage_reported' => 'Rayure sur la portière avant droite.',
            ])
            ->assertSessionHasErrors('photos');

        $this->assertNull($booking->refresh()->mileage_at_pickup);
    }

    public function test_un_dommage_exige_quatre_photos_pas_trois(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->from(route('agent.pickup.condition', $booking))
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
                'damage_present' => '1',
                'damage_reported' => 'Rayure sur la portière avant droite.',
                'photos' => $this->photos(3),
            ])
            ->assertSessionHasErrors('photos');

        $this->assertSame(0, $booking->getMedia(Booking::MEDIA_CONDITION_REPORT)->count());
    }

    /** Le 500 venait de `Booking::addMedia()` : le modèle n'était pas HasMedia. */
    public function test_un_dommage_avec_quatre_photos_est_enregistre(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
                'damage_present' => '1',
                'damage_reported' => 'Rayure sur la portière avant droite.',
                'photos' => $this->photos(4),
            ])
            ->assertRedirect(route('agent.pickup.payment', $booking))
            ->assertSessionHasNoErrors();

        $booking->refresh();

        $this->assertSame('Rayure sur la portière avant droite.', $booking->pickup_notes);
        $this->assertSame(4, $booking->getMedia(Booking::MEDIA_CONDITION_REPORT)->count());
    }

    public function test_l_inspection_est_obligatoire(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->from(route('agent.pickup.condition', $booking))
            ->post(route('agent.pickup.condition.store', $booking), [
                'mileage' => 12000,
                'fuel_level' => 'full',
            ])
            ->assertSessionHasErrors('damage_present');
    }

    public function test_un_retour_sans_dommage_n_exige_aucune_photo(): void
    {
        $booking = $this->bookingOnRoad();

        $this->actingAs($this->staff())
            ->post(route('agent.return.store', $booking), [
                'mileage' => 12500,
                'fuel_level' => 'full',
                'damage_present' => '0',
            ])
            ->assertRedirect(route('agent.return.deposit', $booking))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $booking->getMedia(Booking::MEDIA_CONDITION_REPORT)->count());
    }

    public function test_un_retour_avec_dommage_exige_quatre_photos(): void
    {
        Storage::fake('documents');

        $booking = $this->bookingOnRoad();

        $this->actingAs($this->staff())
            ->from(route('agent.return.control', $booking))
            ->post(route('agent.return.store', $booking), [
                'mileage' => 12500,
                'fuel_level' => 'full',
                'damage_present' => '1',
                'damage_note' => 'Aile arrière gauche enfoncée.',
                'photos' => $this->photos(4),
            ])
            ->assertRedirect(route('agent.return.deposit', $booking))
            ->assertSessionHasNoErrors();

        $booking->refresh();

        $this->assertSame('Aile arrière gauche enfoncée.', $booking->return_notes);
        $this->assertSame(4, $booking->getMedia(Booking::MEDIA_CONDITION_REPORT)->count());
    }

    /** La signature du contrat utilise le même mécanisme média. */
    public function test_la_signature_du_contrat_est_enregistree(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.contract.store', $booking), [
                'accepted_conditions' => '1',
                'signature' => 'Afi Mensah',
            ])
            ->assertRedirect(route('agent.pickup.keys', $booking))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $booking->getMedia(Booking::MEDIA_SIGNATURE)->count());
    }

    private function confirmedBooking(): Booking
    {
        return $this->bookings()->confirm(
            $this->bookings()->create([
                'vehicle' => $this->vehicle(),
                'period' => $this->period(),
                'user' => null,
                'with_driver' => false,
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]),
            $this->staff(),
        );
    }

    private function bookingOnRoad(): Booking
    {
        $booking = $this->confirmedBooking();
        $booking->update([
            'status' => BookingStatus::EnCours,
            'mileage_at_pickup' => 12000,
            'fuel_level_pickup' => 'full',
        ]);

        return $booking;
    }

    /** @return list<UploadedFile> */
    private function photos(int $count): array
    {
        return array_map(
            fn (int $index) => UploadedFile::fake()->image('dommage-'.$index.'.jpg'),
            range(1, $count),
        );
    }
}
