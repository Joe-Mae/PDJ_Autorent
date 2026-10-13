<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Un peu d'historique : sans réservations passées, le tableau de bord est vide
 * et on ne peut pas juger l'ergonomie de l'espace client ni de l'admin.
 */
class SampleBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = Vehicle::query()->with('category')->get();
        $drivers = Driver::query()->available()->get();

        $customers = [
            ['Kossi', 'Mensah', '+22890112233', 'Lomé', 30, 1, 5],
            ['Aïcha', 'Sow', '+22890223344', 'Kara', 27, 2, 4],
            ['Jean-Baptiste', 'Dossou', '+22890334455', 'Sokodé', 41, 0, 8],
            ['Nadia', 'Tchalo', '+22890445566', 'Lomé', 24, 1, 3],
            ['Serge', 'Koffi', '+22890556677', 'Aného', 35, 0, 2],
        ];

        foreach ($customers as $index => [$first, $last, $phone, $city, $age, $noShows, $pastRentals]) {
            $user = User::query()->create([
                'name' => "{$first} {$last}",
                'first_name' => $first,
                'last_name' => $last,
                'email' => str($first.'.'.$last)->lower()->ascii().'@exemple.tg',
                'phone' => $phone,
                'birth_date' => now()->subYears($age)->subMonths(fake()->numberBetween(0, 11)),
                'password' => Hash::make('motdepasse'),
                'preferred_locale' => 'fr',
                'no_show_count' => $noShows,
            ]);

            $user->assignRole('client');

            for ($i = 0; $i < $pastRentals; $i++) {
                $this->pastBooking($user, $vehicles->random(), $drivers, $city);
            }
        }

        $this->upcomingBookings($vehicles, $drivers);
        $this->reviews($vehicles);
    }

    private function pastBooking(User $user, Vehicle $vehicle, $drivers, string $city): void
    {
        $daysAgo = fake()->numberBetween(10, 120);
        $start = now()->subDays($daysAgo)->setTime(8, 0);
        $length = fake()->numberBetween(2, 7);

        $withDriver = fake()->boolean(35) && $drivers->isNotEmpty();
        $blocks = $withDriver ? $length * 3 : 0;
        $rental = $vehicle->category->daily_price * $length;
        $driverPrice = $blocks * 5000;

        $booking = Booking::query()->create([
            'reference' => 'PDJ-'.$this->token(),
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'category_id' => $vehicle->category_id,
            'driver_id' => $withDriver ? $drivers->random()->id : null,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays($length - 1)->setTime(19, 0),
            'days' => $length,
            'pickup_hour' => 8,
            'return_hour' => 19,
            'with_driver' => $withDriver,
            'driver_blocks' => $blocks,
            'driver_hours' => $withDriver ? $length * 11 : 0,
            'daily_price' => $vehicle->category->daily_price,
            'price_rental' => $rental,
            'price_driver' => $driverPrice,
            'price_total' => $rental + $driverPrice,
            'deposit_amount' => $vehicle->category->deposit_amount,
            'currency' => 'XOF',
            'status' => BookingStatus::Terminee,
            'confirmed_at' => $start->copy()->subHours(20),
            'started_at' => $start,
            'ended_at' => $start->copy()->addDays($length - 1)->setTime(19, 0),
            'mileage_at_pickup' => $vehicle->mileage,
            'mileage_at_return' => $vehicle->mileage + fake()->numberBetween(80, 600),
            'fuel_level_pickup' => 'full',
            'fuel_level_return' => 'full',
            'phone_verified_at' => $start->copy()->subDays(2),
            'grace_expires_at' => $start->copy()->addHours(2),
            'public_token' => bin2hex(random_bytes(20)),
        ]);

        $this->payments($booking, $rental + $driverPrice);
    }

    private function upcomingBookings($vehicles, $drivers): void
    {
        $customer = User::query()->where('phone', '+22890112233')->firstOrFail();

        // Une réservation qui part demain : c'est celle que l'agent voit en tête
        // du tableau de bord et qui déclenche un rappel J-1.
        $tomorrow = now()->addDay()->setTime(8, 0);
        $vehicle = $vehicles->firstWhere('category.slug', 'suv-4x4') ?? $vehicles->first();

        $booking = Booking::query()->create([
            'reference' => 'PDJ-'.$this->token(),
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'category_id' => $vehicle->category_id,
            'starts_at' => $tomorrow,
            'ends_at' => $tomorrow->copy()->addDays(4)->setTime(19, 0),
            'days' => 5,
            'pickup_hour' => 8,
            'return_hour' => 19,
            'with_driver' => false,
            'driver_blocks' => 0,
            'driver_hours' => 0,
            'daily_price' => $vehicle->category->daily_price,
            'price_rental' => $vehicle->category->daily_price * 5,
            'price_driver' => 0,
            'price_total' => $vehicle->category->daily_price * 5,
            'deposit_amount' => $vehicle->category->deposit_amount,
            'currency' => 'XOF',
            'status' => BookingStatus::Confirmee,
            'confirmed_at' => now()->subHours(3),
            'phone_verified_at' => now()->subDay(),
            'grace_expires_at' => $tomorrow->copy()->addHours(2),
            'public_token' => bin2hex(random_bytes(20)),
        ]);

        Storage::disk('documents')->put("demo/identite-{$booking->reference}.txt", 'pièce d\'identité (démo)');
        $booking->documents()->create([
            'user_id' => $customer->id,
            'type' => DocumentType::IdentityCard,
            'status' => DocumentStatus::Presente,
            'disk' => 'documents',
            'path' => "demo/identite-{$booking->reference}.txt",
            'mime_type' => 'text/plain',
            'size_kb' => 1,
        ]);

        // Une autre en attente : c'est la file de validation côté agent.
        $pending = Booking::query()->create([
            'reference' => 'PDJ-'.$this->token(),
            'user_id' => $customer->id,
            'vehicle_id' => $vehicles->random()->id,
            'category_id' => $vehicles->random()->category_id,
            'starts_at' => now()->addDays(9)->setTime(8, 0),
            'ends_at' => now()->addDays(11)->setTime(19, 0),
            'days' => 3,
            'pickup_hour' => 8,
            'return_hour' => 19,
            'with_driver' => true,
            'driver_blocks' => 9,
            'driver_hours' => 33,
            'driver_daily_price' => 5000,
            'daily_price' => 45000,
            'price_rental' => 135000,
            'price_driver' => 15000,
            'price_total' => 150000,
            'deposit_amount' => 150000,
            'currency' => 'XOF',
            'status' => BookingStatus::EnAttente,
            'phone_verified_at' => now()->subHours(2),
            'grace_expires_at' => now()->addDays(9)->setTime(10, 0),
            'public_token' => bin2hex(random_bytes(20)),
        ]);
    }

    private function reviews($vehicles): void
    {
        $texts = [
            [5, 'Voiture propre, clim au frais, l\'agent a été rapide au guichet. Je repasserai.'],
            [4, 'Rien à redire. Le prix annoncé était exactement le prix payé.'],
            [5, 'Très bon rapport qualité-prix pour une semaine à Lomé.'],
            [4, 'La voiture était nickel. Petit bémol sur le retard de 15 minutes au retour.'],
            [5, 'Le chauffeur était très sérieux, ponctuel. Ça change tout à Lomé.'],
            [3, 'Correct pour le prix. La caution a été rendue sans discuter.'],
        ];

        Booking::query()
            ->where('status', BookingStatus::Terminee)
            ->with('user')
            ->get()
            ->each(function (Booking $booking, int $index) use ($texts) {
                [$rating, $comment] = $texts[$index % count($texts)];

                Review::query()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'vehicle_id' => $booking->vehicle_id,
                    'rating' => $rating,
                    'condition_rating' => min(5, $rating),
                    'service_rating' => min(5, $rating + 1),
                    'author_name' => $booking->user->displayName(),
                    'author_city' => fake()->randomElement(['Lomé', 'Kara', 'Sokodé', 'Aného', 'Tsévié']),
                    'locale' => 'fr',
                    'comment' => $comment,
                    'status' => ReviewStatus::Publiee,
                ]);
            });
    }

    private function payments(Booking $booking, int $rentalTotal): void
    {
        $booking->payments()->create([
            'kind' => PaymentKind::Rental,
            'method' => fake()->randomElement([PaymentMethod::Especes, PaymentMethod::MobileMoney]),
            'amount' => $rentalTotal,
            'recorded_at' => $booking->started_at,
        ]);

        $booking->payments()->create([
            'kind' => PaymentKind::Deposit,
            'method' => PaymentMethod::Especes,
            'amount' => $booking->deposit_amount,
            'recorded_at' => $booking->started_at->copy()->addMinutes(10),
        ]);

        $booking->payments()->create([
            'kind' => PaymentKind::DepositRefund,
            'method' => PaymentMethod::Especes,
            'amount' => $booking->deposit_amount,
            'deducted_amount' => 0,
            'recorded_at' => $booking->ended_at,
        ]);
    }

    /** 6 caractères sans 0/O ni 1/I, comme le générateur de référence. */
    private function token(): string
    {
        return strtoupper(fake()->unique()->bothify('??######'));
    }
}
