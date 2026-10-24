<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['site.hero_vehicle_ids', '[]', 'json', 'Ordre des photos du bandeau accueil'],
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
        ];

        foreach ($settings as [$key, $value, $type, $label]) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => 'website',
                'label_fr' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['driver.block_hours', '4', 'Durée d’un bloc chauffeur (heures)'],
            ['driver.block_price', '7000', 'Tarif chauffeur avec voiture (:currency/jour)'],
            ['driver.auto_check_days', '5', 'Durée avant de proposer un chauffeur (jours)'],
            ['driver.solo_daily_price', '15000', 'Chauffeur seul : tarif journalier (:currency)'],
            ['driver.solo_discount_daily_price', '13000', 'Chauffeur seul : tarif réduit (:currency/jour)'],
            ['driver.solo_discount_after_days', '3', 'Durée avant tarif réduit (jours)'],
        ] as [$key, $value, $label]) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'type' => 'int',
                'group' => 'driver',
                'label_fr' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // These are user-editable settings; rolling back must not erase agency choices.
    }
};
