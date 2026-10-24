<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Pages juridiques éditables.
 *
 * Le texte par défaut reprend celui des vues Blade : si une page n'existe pas
 * en base, le site continue d'afficher la version livrée avec l'application.
 * Dès qu'une page est créée ici, c'est elle qui est servie — l'agence peut donc
 * adapter ses conditions générales sans redéploiement.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'conditions-generales',
                'title_fr' => 'Conditions générales de location',
                'title_en' => 'Rental terms and conditions',
                'meta_description_fr' => 'Âge minimum, permis, carburant, caution et annulation : les conditions de location de l\'agence.',
                'meta_description_en' => 'Minimum age, licence, fuel, deposit and cancellation: the agency rental conditions.',
                'sort_order' => 1,
                'content_fr' => $this->conditionsGeneralesFr(),
                'content_en' => $this->conditionsGeneralesEn(),
            ],
            [
                'slug' => 'confidentialite',
                'title_fr' => 'Politique de confidentialité',
                'title_en' => 'Privacy policy',
                'meta_description_fr' => 'Quelles données nous collectons, pourquoi, combien de temps nous les conservons et comment exercer vos droits.',
                'meta_description_en' => 'What data we collect, why, how long we keep it and how to exercise your rights.',
                'sort_order' => 2,
                'content_fr' => $this->confidentialiteFr(),
                'content_en' => $this->confidentialiteEn(),
            ],
            [
                'slug' => 'mentions-legales',
                'title_fr' => 'Mentions légales',
                'title_en' => 'Legal notice',
                'meta_description_fr' => 'Éditeur du site, hébergement et propriété intellectuelle.',
                'meta_description_en' => 'Publisher, hosting and intellectual property.',
                'sort_order' => 3,
                'content_fr' => $this->mentionsLegalesFr(),
                'content_en' => $this->mentionsLegalesEn(),
            ],
        ];
    }

    private function conditionsGeneralesFr(): string
    {
        return <<<'HTML'
<p>La location est consentie pour la durée indiquée au contrat. Le véhicule doit être restitué à la date, à l'heure et au lieu convenus.</p>
<h2>Conditions d'accès</h2>
<ul>
    <li>Être âgé d'au moins 18 ans révolus.</li>
    <li>Présenter une pièce d'identité en cours de validité.</li>
    <li>Présenter un permis de conduire valide depuis un an au minimum, sauf location avec chauffeur.</li>
</ul>
<h2>Utilisation du véhicule</h2>
<ul>
    <li>Le carburant est à la charge du client ; le véhicule est rendu avec le même niveau qu'au départ.</li>
    <li>Le kilométrage est illimité, dans le cadre d'un usage raisonnable.</li>
    <li>La vitesse est limitée à 120 km/h. Les infractions restent à la charge du conducteur.</li>
    <li>La conduite est réservée aux personnes inscrites au contrat. La sous-location est interdite.</li>
    <li>Aucune sortie du territoire sans autorisation écrite de l'agence.</li>
</ul>
<h2>Assurance et caution</h2>
<p>L'assurance responsabilité civile, vol, incendie et bris de glaces est incluse. Une franchise reste à la charge du client en cas de dommage. La caution est encaissée au retrait et restituée au retour, après déduction éventuelle des pleins, dommages ou pénalités de retard.</p>
<h2>Annulation</h2>
<p>L'annulation est gratuite tant que la location n'a pas commencé. Aucun frais d'annulation n'est prélevé. Le paiement se fait exclusivement à l'agence, au moment du retrait.</p>
HTML;
    }

    private function conditionsGeneralesEn(): string
    {
        return <<<'HTML'
<p>The rental is granted for the period stated in the agreement. The vehicle must be returned on the agreed date, at the agreed time and place.</p>
<h2>Eligibility</h2>
<ul>
    <li>Be at least 18 years old.</li>
    <li>Present a valid identity document.</li>
    <li>Present a driving licence held for at least one year, unless renting with a chauffeur.</li>
</ul>
<h2>Use of the vehicle</h2>
<ul>
    <li>Fuel is at the customer's expense; the vehicle is returned with the same level as at pickup.</li>
    <li>Mileage is unlimited, within reasonable use.</li>
    <li>Speed is limited to 120 km/h. Traffic offences remain the driver's responsibility.</li>
    <li>Only the people named on the agreement may drive. Sub-letting is prohibited.</li>
    <li>No leaving the country without the agency's written authorisation.</li>
</ul>
<h2>Insurance and deposit</h2>
<p>Third-party liability, theft, fire and glass insurance are included. An excess remains at the customer's expense in the event of damage. The deposit is collected at pickup and returned on return, after any deductions for fuel, damage or late penalties.</p>
<h2>Cancellation</h2>
<p>Cancellation is free as long as the rental has not started. No cancellation fee is charged. Payment is made exclusively at the agency, at pickup.</p>
HTML;
    }

    private function confidentialiteFr(): string
    {
        return <<<'HTML'
<h2>Qui traite vos données</h2>
<p>L'agence de location, représentée par son responsable de traitement, est seule destinataire des données collectées sur ce site.</p>
<h2>Ce que nous collectons</h2>
<p>Votre identité (nom, date de naissance), votre contact (téléphone, e-mail), vos documents (pièce d'identité, permis, éventuel selfie) et vos réservations. Rien d'autre.</p>
<h2>Pourquoi</h2>
<p>Vérifier que vous pouvez conduire, éviter les réservations fantômes et tenir la location. C'est tout.</p>
<h2>Vos documents</h2>
<p>Vos pièces sont stockées hors du site, sur un espace privé. Elles ne sont accessibles qu'aux agents habilités, par un lien qui expire après quinze minutes. Elles sont supprimées douze mois après la fin de votre location, ou immédiatement sur votre demande.</p>
<h2>Vos droits</h2>
<p>Vous pouvez demander à voir, corriger ou supprimer vos données à tout moment. Il suffit d'écrire à l'agence : la demande est traitée sous quinze jours.</p>
HTML;
    }

    private function confidentialiteEn(): string
    {
        return <<<'HTML'
<h2>Who processes your data</h2>
<p>The rental agency, represented by its data controller, is the sole recipient of the data collected on this website.</p>
<h2>What we collect</h2>
<p>Your identity (name, date of birth), your contact details (phone, email), your documents (identity document, driving licence, optional selfie) and your bookings. Nothing else.</p>
<h2>Why</h2>
<p>To check that you may drive, to prevent phantom bookings and to run the rental. That is all.</p>
<h2>Your documents</h2>
<p>Your documents are stored outside the website, in a private space. They are accessible only to authorised staff, through a link that expires after fifteen minutes. They are deleted twelve months after the end of your rental, or immediately on your request.</p>
<h2>Your rights</h2>
<p>You may ask to view, correct or delete your data at any time. Simply write to the agency: requests are handled within fifteen days.</p>
HTML;
    }

    private function mentionsLegalesFr(): string
    {
        return <<<'HTML'
<h2>Éditeur du site</h2>
<p>Ce site est édité et exploité par l'agence de location de véhicules, basée à Lomé, République du Togo. Ses coordonnées complètes figurent sur la page Contact.</p>
<h2>Hébergement</h2>
<p>Les données sont hébergées sur des serveurs situés dans l'Union européenne. Aucune donnée n'est transférée à des pays tiers sans votre accord.</p>
<h2>Propriété intellectuelle</h2>
<p>Les photographies de la flotte, les textes et la charte graphique de ce site appartiennent à l'agence. Toute reproduction sans autorisation écrite est interdite.</p>
HTML;
    }

    private function mentionsLegalesEn(): string
    {
        return <<<'HTML'
<h2>Publisher</h2>
<p>This website is published and operated by the vehicle rental agency based in Lomé, Republic of Togo. Its full contact details are available on the Contact page.</p>
<h2>Hosting</h2>
<p>Data is hosted on servers located in the European Union. No data is transferred to third countries without your consent.</p>
<h2>Intellectual property</h2>
<p>Fleet photographs, texts and the graphic identity of this website belong to the agency. Any reproduction without written authorisation is prohibited.</p>
HTML;
    }
}
