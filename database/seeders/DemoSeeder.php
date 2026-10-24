<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Models\Banner;
use App\Models\Driver;
use App\Models\Faq;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Jeu de démonstration : une flotte de 12 véhicules à Lomé, 3 chauffeurs, les
 * réglages par défaut et les contenus de la page d'accueil.
 *
 * Les tarifs sont ceux d'une agence de Lomé de taille moyenne : 18 000 F CFA
 * pour une citadine, 150 000 pour une berline de luxe. Ce sont des ordres de
 * grandeur crédibles, pas des prix réels — à ajuster avec l'agence.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedSettings();
        $this->seedCategories();
        $this->seedVehicles();
        $this->seedDrivers();
        $this->seedBanners();
        $this->seedFaqs();
        $this->seedHolidays();
    }

    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super_admin' => ['Tout voir, tout faire, gérer les comptes et les paramètres.'],
            'gestionnaire' => ['Gère la flotte, les tarifs, le planning et les contenus.'],
            'agent_accueil' => ['Traite les réservations, les documents, la caisse, le retrait et le retour.'],
        ];

        foreach ($roles as $name => $description) {
            Role::findOrCreate($name, 'web', ['description' => $description]);
        }
    }

    private function seedSettings(): void
    {
        $rows = [
            ['agency.name', 'PDJ-AUTORENT', 'general', "Nom de l'agence", 'string'],
            ['agency.address', 'Bd du Mono, Lomé', 'general', "Adresse de l'agence", 'string'],
            ['agency.phone', '+228 90 12 34 56', 'general', 'Téléphone affiché', 'string'],
            ['agency.whatsapp', '+22890123456', 'general', 'Numéro WhatsApp, format international', 'string'],
            ['agency.open_time', '08:00', 'general', "Heure d'ouverture", 'string'],
            ['agency.close_time', '19:00', 'general', 'Heure de fermeture', 'string'],
            ['general.currency', 'FCFA', 'general', 'Devise affichée (code ou symbole)', 'string'],

            ['booking.turnaround_hours', '2', 'reservation', 'Battement entre deux locations (heures)', 'int'],
            ['booking.grace_period_hours', '2', 'reservation', 'Délai de grâce avant "non présenté" (heures)', 'int'],
            ['booking.max_active_bookings', '2', 'reservation', 'Réservations actives maximum par client', 'int'],
            ['booking.min_age', '18', 'reservation', 'Âge minimum', 'int'],
            ['booking.min_license_years', '1', 'reservation', 'Ancienneté minimum du permis (années)', 'int'],
            ['booking.speed_limit_kmh', '120', 'reservation', 'Vitesse maximale (km/h)', 'int'],
            ['booking.free_cancellation_before', '18:00', 'reservation', "Heure limite d'annulation gratuite (J-1)", 'string'],
            ['booking.min_days', '1', 'reservation', 'Durée minimum de location (jours)', 'int'],
            ['booking.late_fee_multiplier', '1', 'reservation', 'Pénalité de retard : 1 = un jour de location par jour commencé', 'int'],
            ['booking.insurance_deductible', '150000', 'reservation', 'Franchise assurance (:currency)', 'int'],

            ['driver.block_hours', '4', 'driver', 'Durée d\'un bloc chauffeur (heures)', 'int'],
            ['driver.block_price', '7000', 'driver', 'Supplément chauffeur avec voiture (:currency/jour)', 'int'],
            ['driver.auto_check_days', '5', 'driver', 'Au-delà de N jours, proposer le chauffeur', 'int'],
            ['driver.solo_daily_price', '15000', 'driver', 'Chauffeur seul : tarif journalier jusqu\'à 3 jours (:currency)', 'int'],
            ['driver.solo_discount_daily_price', '13000', 'driver', 'Chauffeur seul : tarif journalier réduit au-delà de 3 jours (:currency)', 'int'],
            ['driver.solo_discount_after_days', '3', 'driver', 'Chauffeur seul : durée avant tarif réduit (jours)', 'int'],
            ['driver.with_car_mode', 'supplement', 'driver', 'Mode tarif chauffeur avec voiture: supplement ou package', 'string'],

            ['no_show.max_absences', '2', 'reservation', 'Absences avant blocage du compte', 'int'],
            ['document.retention_months', '12', 'reservation', 'Conservation des documents courants (mois)', 'int'],
            ['document.identity_retention_months', '60', 'reservation', 'Conservation des pièces d\'identité et du permis (mois)', 'int'],
            ['document.selfie_enabled', '1', 'reservation', 'Proposer le selfie facultatif', 'bool'],
            ['contact.email', 'contact@pdj-autorent.tg', 'general', 'E-mail de contact', 'string'],
        ];

        foreach ($rows as [$key, $value, $group, $label, $type]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'label_fr' => $label, 'type' => $type],
            );
        }

        $websiteSettings = [
            ['site.hero_vehicle_ids', '[]', 'json', 'Ordre des photos du bandeau accueil'],
            ['site.hero_uploaded_images', '[]', 'json', 'Photos importées du diaporama accueil'],
            ['site.brand_color', '#0A6E4C', 'string', 'Couleur principale du site'],
            ['site.accent_color', '#FFC400', 'string', 'Couleur accent du site'],
            ['site.brand_mark', 'PDJ', 'string', 'Monogramme affiché dans le logo'],
            ['site.brand_label', 'AUTORENT', 'string', 'Nom affiché dans le logo'],
            ['site.hero_eyebrow.fr', 'Location de voitures à Lomé', 'string', 'Accroche du bandeau accueil (FR)'],
            ['site.hero_eyebrow.en', 'Car rental in Lomé', 'string', 'Accroche du bandeau accueil (EN)'],
            ['site.footer_tagline.fr', 'Une agence, un point à Lomé, des prix qui ne changent pas à la réservation.', 'string', 'Signature du pied de page (FR)'],
            ['site.footer_tagline.en', 'One agency, one spot in Lomé, prices that do not change after booking.', 'string', 'Footer tagline (EN)'],
            ['nav.home.fr', 'Accueil', 'string', 'Navigation : accueil (FR)'],
            ['nav.home.en', 'Home', 'string', 'Navigation: home (EN)'],
            ['nav.fleet.fr', 'Garage', 'string', 'Navigation : garage (FR)'],
            ['nav.fleet.en', 'The fleet', 'string', 'Navigation: fleet (EN)'],
            ['nav.driver.fr', 'Chauffeur', 'string', 'Navigation : chauffeur (FR)'],
            ['nav.driver.en', 'With chauffeur', 'string', 'Navigation: chauffeur (EN)'],
            ['nav.faq.fr', 'Questions fréquentes', 'string', 'Navigation : FAQ (FR)'],
            ['nav.faq.en', 'Frequently asked questions', 'string', 'Navigation: FAQ (EN)'],
            ['nav.contact.fr', 'Contact', 'string', 'Navigation : contact (FR)'],
            ['nav.contact.en', 'Contact', 'string', 'Navigation: contact (EN)'],
            ['nav.lookup.fr', 'Consulter ma réservation', 'string', 'Navigation : consulter ma réservation (FR)'],
            ['nav.lookup.en', 'Check my booking', 'string', 'Navigation: check my booking (EN)'],
        ];

        foreach ($websiteSettings as [$key, $value, $type, $label]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => 'website', 'label_fr' => $label],
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            [
                'slug' => 'citadines', 'name_fr' => 'Citadines', 'name_en' => 'City cars',
                'daily_price' => 18000, 'deposit_amount' => 60000, 'seats' => 5, 'bags' => 2, 'sort_order' => 1,
                'description_fr' => 'Petites voitures faciles à garer à Lomé. Parfaites pour deux personnes en ville.',
            ],
            [
                'slug' => 'berlines', 'name_fr' => 'Berlines', 'name_en' => 'Sedans',
                'daily_price' => 28000, 'deposit_amount' => 90000, 'seats' => 5, 'bags' => 3, 'sort_order' => 2,
                'description_fr' => 'Plus de place et plus de confort pour les longues distances.',
            ],
            [
                'slug' => 'suv-4x4', 'name_fr' => 'SUV / 4x4', 'name_en' => 'SUV / 4x4',
                'daily_price' => 45000, 'deposit_amount' => 150000, 'seats' => 5, 'bags' => 4, 'sort_order' => 3, 'is_featured' => true,
                'description_fr' => 'Route dégradée, pistes, Monaco, Kpimandji : le SUV passe là où la citadine ne passe pas.',
            ],
            [
                'slug' => 'luxe', 'name_fr' => 'Luxe', 'name_en' => 'Luxury',
                'daily_price' => 150000, 'deposit_amount' => 400000, 'seats' => 5, 'bags' => 3, 'sort_order' => 4, 'is_featured' => true,
                'description_fr' => 'Voitures de réception, avec chauffeur disponible. Pour les occasions qui comptent.',
            ],
            [
                'slug' => 'utilitaires', 'name_fr' => 'Utilitaires & pick-up', 'name_en' => 'Vans & pickups',
                'daily_price' => 35000, 'deposit_amount' => 120000, 'seats' => 5, 'bags' => 6, 'sort_order' => 5,
                'description_fr' => 'Déménagements, livraisons, transporting de matériaux. Pick-up et fourgons.',
            ],
            [
                'slug' => 'electriques', 'name_fr' => 'Électriques & hybrides', 'name_en' => 'Electric & hybrid',
                'daily_price' => 55000, 'deposit_amount' => 180000, 'seats' => 5, 'bags' => 3, 'sort_order' => 6, 'is_featured' => true,
                'description_fr' => 'Silencieux, économique en ville. Perfectible sur une borne de l\'agence.',
            ],
        ];

        foreach ($categories as $category) {
            VehicleCategory::query()->create([
                ...$category,
                'name_en' => $category['name_en'] ?? $category['name_fr'],
                'doors' => $category['slug'] === 'utilitaires' ? 4 : 5,
            ]);
        }
    }

    private function seedVehicles(): void
    {
        $catalog = [
            ['citadines', 'Toyota', 'Vitz', 'TG-1234-AB', 2022, 'manuelle', 'essence', 4, true],
            ['citadines', 'Dacia', 'Logan', 'TG-2345-BC', 2021, 'manuelle', 'essence', 5, true],
            ['citadines', 'Renault', 'Clio', 'TG-3456-CD', 2023, 'manuelle', 'essence', 5, false],
            ['citadines', 'Nissan', 'Note', 'TG-4567-DE', 2022, 'automatique', 'essence', 5, true],
            ['berlines', 'Toyota', 'Corolla', 'TG-5678-EF', 2023, 'automatique', 'essence', 5, true],
            ['berlines', 'Hyundai', 'Elantra', 'TG-6789-FG', 2022, 'automatique', 'essence', 5, true],
            ['berlines', 'Kia', 'Cerato', 'TG-7890-GH', 2023, 'automatique', 'essence', 5, false],
            ['suv-4x4', 'Toyota', 'RAV4', 'TG-8901-HI', 2023, 'automatique', 'essence', 5, true],
            ['suv-4x4', 'Nissan', 'Qashqai', 'TG-9012-IJ', 2022, 'automatique', 'essence', 5, true],
            ['utilitaires', 'Toyota', 'Hilux', 'TG-0123-JK', 2021, 'manuelle', 'diesel', 5, true],
            ['utilitaires', 'Renault', 'Kangoo', 'TG-1234-KL', 2022, 'manuelle', 'diesel', 2, true],
            ['luxe', 'Mercedes-Benz', 'E-Class', 'TG-2345-LM', 2023, 'automatique', 'essence', 5, true],
            ['luxe', 'BMW', 'Serie 5', 'TG-3456-MN', 2023, 'automatique', 'essence', 5, true],
            ['electriques', 'Renault', 'Zoe', 'TG-4567-NO', 2023, 'automatique', 'electrique', 5, true],
            ['electriques', 'Toyota', 'Hybrid Corolla', 'TG-5678-PQ', 2024, 'automatique', 'hybride', 5, true],
        ];

        $airConditioned = ['luxe', 'berlines', 'suv-4x4', 'electriques'];

        foreach ($catalog as [$categorySlug, $brand, $model, $plate, $year, $transmission, $fuel, $bags, $featured]) {
            $category = VehicleCategory::query()->where('slug', $categorySlug)->firstOrFail();

            Vehicle::query()->create([
                'category_id' => $category->id,
                'brand' => $brand,
                'model' => $model,
                'plate' => $plate,
                'year' => $year,
                'transmission' => $transmission,
                'fuel' => $fuel,
                'seats' => $category->seats,
                'doors' => $category->doors,
                'bags' => $bags,
                'air_conditioning' => in_array($categorySlug, $airConditioned, true),
                'features' => array_values(array_filter([
                    'gps', 'bluetooth', 'cruise', 'usb', 'reversing_camera',
                    in_array($categorySlug, ['luxe', 'berlines'], true) ? 'leather' : null,
                ])),
                'description_fr' => "{$brand} {$model} {$year}, entretenu par nos soins et révisé avant chaque location.",
                'mileage' => fake()->numberBetween(8000, 120000),
                'next_technical_control_at' => now()->addMonths(fake()->numberBetween(2, 10)),
                'status' => VehicleStatus::Disponible,
                'is_active' => true,
            ]);
        }
    }

    private function seedDrivers(): void
    {
        $drivers = [
            ['Komla', 'Aholou', '+228 90 11 22 33', 5, 180],
            ['Yao', 'Mensah', '+228 91 44 55 66', 4, 95],
            ['Serge', 'Akakpo', '+228 92 77 88 99', 5, 240],
        ];

        foreach ($drivers as [$first, $last, $phone, $rating, $rentals]) {
            Driver::query()->create([
                'first_name' => $first,
                'last_name' => $last,
                'phone' => $phone,
                'whatsapp' => $phone,
                'status' => DriverStatus::Actif,
                'rating' => $rating,
                'completed_rentals' => $rentals,
                'bio' => "Chauffeur professionnel, {$rentals} locations réalisées à Lomé.",
            ]);
        }
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'slot' => 'home_hero',
                'title_fr' => 'Une voiture pour la semaine, à Lomé',
                'title_en' => 'A car for the week, in Lomé',
                'subtitle_fr' => 'Prix clair en francs CFA. Assurance incluse. Vous payez en arrivant, rien avant.',
                'subtitle_en' => 'Clear prices in CFA francs. Insurance included. You pay on arrival, nothing before.',
                'background_color' => '#0a6e4c',
                'sort_order' => 1,
            ],
            [
                'slot' => 'home_middle',
                'title_fr' => 'Besoin d\'un chauffeur ?',
                'title_en' => 'Need a chauffeur?',
                'subtitle_fr' => '5 000 F CFA par jour avec une voiture. Chauffeur seul à partir de 6 500 F CFA par jour.',
                'subtitle_en' => '5,000 F CFA per day with a car. Chauffeur-only from 6,500 F CFA per day.',
                'background_color' => '#ffc72c',
                'sort_order' => 1,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::query()->create($banner);
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'category_fr' => 'Réservation', 'category_en' => 'Booking',
                'question_fr' => 'Faut-il payer en ligne ?',
                'question_en' => 'Do I have to pay online?',
                'answer_fr' => "Non. Rien n'est encaissé sur ce site. Vous réglez à l'agence le matin du retrait, en espèces, mobile money ou Visa. Le site vous affiche quand même le montant exact à prévoir, caution comprise.",
                'answer_en' => 'No. Nothing is taken on this site. You pay at the agency on pickup morning, by cash, mobile money or Visa. The site still shows you the exact amount to bring, deposit included.',
                'sort_order' => 1,
            ],
            [
                'category_fr' => 'Réservation', 'category_en' => 'Booking',
                'question_fr' => 'Quel âge faut-il avoir ?',
                'question_en' => 'How old do I have to be?',
                'answer_fr' => "18 ans révolus, et un permis de conduire valable depuis au moins un an. Le permis doit rester valable jusqu'à la fin de la location. Ces trois conditions sont vérifiées à partir de vos documents.",
                'answer_en' => '18 years old, and a driving licence held for at least one year. The licence must stay valid until the end of the rental. All three are checked against your documents.',
                'sort_order' => 2,
            ],
            [
                'category_fr' => 'Réservation', 'category_en' => 'Booking',
                'question_fr' => 'Puis-je annuler ?',
                'question_en' => 'Can I cancel?',
                'answer_fr' => "Oui, gratuitement, en un clic, tant que la location n'a pas commencé. Il n'y a aucun frais d'annulation. Tant que vous n'avez pas payé, il n'y a rien à rembourser.",
                'answer_en' => 'Yes, free of charge, in one click, as long as the rental has not started. There is no cancellation fee. As long as you have not paid, there is nothing to refund.',
                'sort_order' => 3,
            ],
            [
                'category_fr' => 'Conduite', 'category_en' => 'Driving',
                'question_fr' => 'Le kilométrage est-il limité ?',
                'question_en' => 'Is mileage limited?',
                'answer_fr' => "Non, le kilométrage est illimité. On compte sur un usage raisonnable : ce n'est pas faire 1 500 km par jour pendant trois jours. Au-delà, l'agence peut vous demander de discuter.",
                'answer_en' => 'No, mileage is unlimited. We count on reasonable use: driving 1,500 km a day for three days is not reasonable. Beyond that, the agency may ask to discuss it.',
                'sort_order' => 1,
            ],
            [
                'category_fr' => 'Conduite', 'category_en' => 'Driving',
                'question_fr' => 'Qui paie le carburant ?',
                'question_en' => 'Who pays for fuel?',
                'answer_fr' => "Le carburant est à votre charge. Vous rendez le véhicule avec le même niveau qu'au départ. Si ce n'est pas le cas, on refait le plein et la facture est prélevée sur la caution, après vous avoir montré le montant.",
                'answer_en' => 'Fuel is at your expense. You return the vehicle with the same level as at pickup. If not, we refuel and the receipt is deducted from the deposit, after showing you the amount.',
                'sort_order' => 2,
            ],
            [
                'category_fr' => 'Conduite', 'category_en' => 'Driving',
                'question_fr' => 'Quelle vitesse maximale ?',
                'question_en' => 'What is the speed limit?',
                'answer_fr' => '120 km/h, comme partout au Togo. Les amendes éventuelles sont à votre charge, et un traceur GPS peut être installé sur certains véhicules.',
                'answer_en' => '120 km/h, as everywhere in Togo. Any fines are at your expense, and a GPS tracker may be fitted on some vehicles.',
                'sort_order' => 3,
            ],
            [
                'category_fr' => 'Argent', 'category_en' => 'Money',
                'question_fr' => 'Comment fonctionne la caution ?',
                'question_en' => 'How does the deposit work?',
                'answer_fr' => "C'est une somme que vous déposez à l'agence au moment du retrait, et qui vous est rendue au retour. Le montant dépend de la catégorie. Ce n'est pas un paiement : c'est un dépôt, et vous recevez un reçu pour cela.",
                'answer_en' => 'It is a sum you leave at the agency at pickup and that is refunded on return. The amount depends on the category. It is not a payment: it is a deposit, and you get a receipt for it.',
                'sort_order' => 1,
            ],
            [
                'category_fr' => 'Argent', 'category_en' => 'Money',
                'question_fr' => 'Qu\'est-ce qui peut être prélevé sur la caution ?',
                'question_en' => 'What can be deducted from the deposit?',
                'answer_fr' => "Le carburant manquant, les dégâts constatés sur devis, et les pénalités de retard. Chaque prélèvement est détaillé sur un reçu avant d'être effectué.",
                'answer_en' => 'Missing fuel, damage identified on a quote, and late-return penalties. Every deduction is itemised on a receipt before it is made.',
                'sort_order' => 2,
            ],
            [
                'category_fr' => 'Chauffeur', 'category_en' => 'Chauffeur',
                'question_fr' => 'Comment le chauffeur est-il facturé ?',
                'question_en' => 'How is the chauffeur charged?',
                'answer_fr' => "Avec une voiture de l'agence, le chauffeur coûte 7 000 FCFA par jour (supplément). Pour une réservation de chauffeur seul, le tarif est de 15 000 FCFA par jour jusqu'à 3 jours, puis 13 000 FCFA par jour appliqué à toute la durée au-delà de 3 jours.",
                'answer_en' => 'With a car rented from the agency, a chauffeur costs 7,000 FCFA per day (supplement). For a chauffeur-only booking, the rate is 15,000 FCFA per day for up to 3 days, then 13,000 FCFA per day applied to the whole duration beyond 3 days.',
                'sort_order' => 1,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->create($faq);
        }
    }

    private function seedHolidays(): void
    {
        $holidays = [
            ['01-01', 'Jour de l\'An', 'New Year\'s Day', false, false],
            ['01-13', 'Fête de la Liberté', 'Freedom Day', false, true],
            ['04-27', 'Fête de l\'Indépendance', 'Independence Day', false, true],
            ['05-01', 'Fête du Travail', 'Labour Day', false, true],
            ['08-15', 'Assomption', 'Assumption Day', false, true],
            ['11-01', 'Fête de l\'Indépendance (2e jour)', 'Independence Day (2nd day)', false, true],
            ['12-25', 'Noël', 'Christmas', true, false],
        ];

        $year = (int) now()->format('Y');

        foreach ($holidays as [$date, $fr, $en, $closed, $surcharged]) {
            $full = "{$year}-{$date}";

            Holiday::query()->updateOrCreate(
                ['date' => $full],
                ['label_fr' => $fr, 'label_en' => $en, 'is_agency_closed' => $closed, 'is_price_surcharged' => $surcharged],
            );
        }
    }
}
