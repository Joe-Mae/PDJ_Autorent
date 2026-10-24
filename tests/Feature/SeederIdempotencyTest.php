<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\Driver;
use App\Models\Faq;
use App\Models\Holiday;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le seeding est rejouable.
 *
 * `php artisan db:seed` est souvent relancé en développement — sur un
 * changement de migration, sur une nouvelle donnée de démo. Un seeder qui
 * duplique à chaque passage oblige à remettre la base à zéro pour rien, et
 * un comptes en double ferait échouer l'inscription. Ce test verrouille :
 * relancer le seeding complet ne change strictement rien aux effectifs.
 */
class SeederIdempotencyTest extends TestCase
{
    public function test_seeding_twice_changes_nothing(): void
    {
        Storage::fake('documents');

        $this->seed();
        $afterFirstRun = $this->counts();

        $this->seed();

        $this->assertSame($afterFirstRun, $this->counts(), 'Un second db:seed ne doit dupliquer aucune ligne.');
    }

    public function test_staff_accounts_keep_their_login_details_on_reseed(): void
    {
        Storage::fake('documents');

        $this->seed();

        $staff = User::query()->where('email', 'super@pdj-autorent.tg')->firstOrFail();
        $phone = $staff->phone;
        $password = $staff->password;

        $this->seed();

        $staff->refresh();

        $this->assertSame($phone, $staff->phone, 'Le téléphone du compte de démo ne bouge pas au re-seeding.');
        $this->assertSame($password, $staff->password, 'Le mot de passe n’est pas réécrit au re-seeding.');
        $this->assertTrue($staff->hasRole('super_admin'));
    }

    public function test_sample_bookings_are_not_recreated_when_reservations_exist(): void
    {
        Storage::fake('documents');

        $this->seed();
        $bookings = Booking::query()->count();
        $reviews = Review::query()->count();
        $payments = Payment::query()->count();

        $this->seed();

        $this->assertSame($bookings, Booking::query()->count());
        $this->assertSame($reviews, Review::query()->count());
        $this->assertSame($payments, Payment::query()->count());
        $this->assertGreaterThan(0, $bookings, 'Le jeu de démonstration doit contenir des réservations.');
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'rôles' => Role::query()->count(),
            'comptes' => User::query()->count(),
            'réglages' => Setting::query()->count(),
            'catégories' => VehicleCategory::query()->count(),
            'véhicules' => Vehicle::query()->count(),
            'chauffeurs' => Driver::query()->count(),
            'bannières' => Banner::query()->count(),
            'faq' => Faq::query()->count(),
            'jours fériés' => Holiday::query()->count(),
            'pages' => Page::query()->count(),
            'réservations' => Booking::query()->count(),
            'paiements' => Payment::query()->count(),
            'documents' => CustomerDocument::query()->count(),
            'avis' => Review::query()->count(),
        ];
    }
}
