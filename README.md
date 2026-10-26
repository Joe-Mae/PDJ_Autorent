# PDJ Autorent

Plateforme de location de véhicules pour une agence unique à Lomé.
Réservation en ligne, paiement et caution à l'agence.

---

## Ce que fait le site

Le client cherche une voiture libre sur ses dates, voit le prix exact,
réserve, dépose ses papiers, reçoit un rappel la veille et vient à l'agence
avec ses originaux. **Rien n'est payé en ligne** : le site affiche le montant
à préparer — caution comprise quand le véhicule en exige une — et
l'encaissement se fait au guichet.

## Les cinq règles qui structurent tout

1. **Le site ne montre que ce qui est réellement libre.** Pas de véhicule
   affiché « pas disponible » : ce n'est pas dans la base de données.
2. **Le prix est figé à la réservation.** Si l'agence change ses tarifs le
   lendemain, les réservations déjà prises gardent le leur.
3. **Rien n'est confirmé automatiquement.** Un agent vérifie les documents,
   puis confirme. C'est le seul garde-fou fiable contre les réservations
   fantômes.
4. **Le chauffeur est une ressource.** Trois chauffeurs, trois ventes par
   période : la case se désactive d'elle-même quand il n'y en a plus.
5. **L'arrondi des blocs de chauffeur se fait par jour calendaire.**
   8 h → 19 h = 11 h = 3 blocs. C'est la règle la plus coûteuse à mal expliquer,
   donc elle est affichée en permanence.

## Installation

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# MySQL
php artisan migrate --seed

npm run build      # ou : npm run dev
php artisan serve
```

### Base de données

MySQL 8 en production. Le jeu de démonstration a été validé sur MySQL et
fonctionne aussi sur SQLite, pratique pour les tests.

Le seeding est rejouable : `php artisan db:seed` peut être relancé à tout
moment (après une migration, pour récupérer un réglage oublié) sans dupliquer
aucune ligne — chaque seeder écrit par clé naturelle. Seul l'historique de
démonstration (réservations, paiements, avis) n'est jamais recréé une fois
les réservations en place.

```env
DB_CONNECTION=mysql
DB_DATABASE=pdj_autorent
DB_USERNAME=...
DB_PASSWORD=...
```

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Super admin | `super@pdj-autorent.tg` | `pdjautorent` |
| Gestionnaire | `gestion@pdj-autorent.tg` | `pdjautorent` |
| Agent d'accueil | `agent@pdj-autorent.tg` | `pdjautorent` |

Cinq clients, quinze véhicules répartis sur six catégories, trois chauffeurs,
une vingtaine de réservations passées pour que l'admin et les écrans du
guichet ne soient pas vides.

## Organisation du code

```
app/
├── Enums/              les statuts métier, une enum par notion
│   ├── BookingStatus      en_attente → confirmée → en_cours → terminée, + refus, absence, annulation
│   ├── VehicleStatus      disponible, réservé, en location, maintenance, hors service
│   ├── DocumentType       pièce d'identité, permis, selfie, contrat, photo
│   └── ...
├── Services/           la logique métier, testable sans HTTP
│   ├── AvailabilityService  quels véhicules sont libres, quels chauffeurs
│   ├── PricingService       le prix, à partir de la catégorie et de la période
│   ├── BookingService       création transactionnelle, transitions de statut
│   ├── SettingsService      les réglages modifiables depuis l'admin
│   ├── NotificationService  WhatsApp d'abord, SMS en secours
│   └── ReferenceGenerator   PDJ-8F3K2A, sans caractères ambigus à l'oral
├── Support/
│   ├── BookingPeriod    le créneau, les jours, les blocs de chauffeur
│   └── PriceQuote       le décompte, prêt à afficher
├── Http/Controllers/
│   ├── *                 le public et le tunnel de réservation
│   ├── Admin/            l'administration
│   ├── Account/          le profil du personnel (nom, mot de passe)
│   └── Agent/            les écrans du guichet (tablette)
├── Http/Requests/
│   ├── Admin/ Site/ Agent/ Account/   une Form Request par formulaire,
│   │                   règles et messages hors des contrôleurs
│   └── Concerns/        les gardes partagées (propriété d'une réservation,
│                        jeton invité, dépôt de pièce)
└── Console/Commands/
    ├── MarkNoShows         passe en « non présenté » après le délai de grâce
    └── SendPickupReminders rappel J-1 sur WhatsApp
```

### Pourquoi les services existent

Les trois règles les plus coûteuses à casser — la disponibilité, le prix et
le cycle de vie d'une réservation — vivent dans des services, pas dans les
contrôleurs. Une règle de métier se lit à un seul endroit, se teste sans
HTTP, et ne se duplique pas entre l'admin, le tunnel et l'écran agent.

```php
// La disponibilité tient en une requête : le test de chevauchement est le
// même partout, la seule variable est le battement.
Booking::query()
    ->active()
    ->forPeriod($padded->startsAt, $padded->endsAt)
    ->exists();
```

## Le tunnel de réservation

Cinq écrans, un seul but par écran :

| Étape | Écran | Ce que le client fait |
|---|---|---|
| 1 | Récapitulatif | choisit la période, active ou non le chauffeur |
| 2 | Conducteur | son identité, son téléphone |
| 3 | Vérification | tape le code reçu par WhatsApp |
| 4 | Documents | dépose pièce d'identité, et permis seulement s'il conduit |
| 5 | Confirmation | relit tout, envoie |

Le prix est figé dès l'étape 1. Changer de dates ou ajouter un chauffeur le
recalcule, mais rien ne part vers l'agence avant l'étape 5.

### Avec chauffeur, le permis n'est pas demandé

C'est le gros gain de conversion de l'option : le client n'a qu'une pièce à
envoyer au lieu de deux, et la case chauffeur se pré-coche au-delà de cinq
jours de location.

### Avec ou sans voiture

Le chauffeur se réserve aussi seul, à la journée : rendez-vous, transferts,
mariages ou soirées. L'accueil présente les deux tarifs — avec une voiture de
l'agence, ou chauffeur seul avec un tarif dégressif au-delà de quelques jours.
Les montants se règlent dans les paramètres de l'agence.

## Disponibilité et anti-double réservation

Un créneau est pris si `debut_demande < fin_existante ET fin_demandee > debut_existant`,
créneau élargi du battement (2 h par défaut) avant confrontation.

La création d'une réservation rejoue ce contrôle **à l'intérieur** d'un verrou
de ligne sur le véhicule :

```php
DB::transaction(function () {
    $locked = Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
    $this->assertVehicleBookable($locked, $period);  // refait ici, pas avant
    // ...
});
```

L'index `(vehicle_id, starts_at, ends_at, status)` rend ce contrôle peu
coûteux ; il n'y a pas de cache d'invalidité à gérer.

## Une flotte qui se recycle

Une voiture qui a déjà loué n'est jamais effacée : elle porte des réservations,
des contrats, des états des lieux et des reçus. La supprimer l'archive
(masquée + hors service). L'écran des véhicules sépare donc **Flotte active**
(par défaut) et **Archivés** ; une remise en service, seule ou en masse, rend
la fiche et ses photos telles quelles, sans rien ressaisir. Un véhicule rendu
après une location repasse automatiquement en `disponible`.

## Les documents du client

Stockés **hors de `public/`**, sur un disque privé, avec un nom de fichier
aléatoire. Accès par URL signée valable 15 minutes. Suppression automatique
après une durée réglable par l'agence, ou sur demande : `document.retention_months`
pour les documents courants, `document.identity_retention_months` pour la
pièce d'identité et le permis, qui relèvent d'une conservation plus longue.

Un agent vérifie chaque pièce : conforme, non conforme, ou à revoir plus tard.
Un refus exige un motif, parce qu'un client à qui on dit non sans
explanation ne revient pas.

## Les écrans du guichet

Parcours de retrait verrouillé dans l'ordre, une action par écran :

1. contrôle de la pièce d'identité → 2. état des lieux (photos seulement si dommage) →
3. encaissement de la location → 4. caution, sur un reçu séparé →
5. contrat signé au doigt → 6. contrôle du permis, photo et remise des clés

L'état des lieux est d'abord visuel : l'agent fait le tour de la voiture. Si
elle est intacte, aucune photo n'est demandée. Dès qu'une rayure ou un choc
est constaté, la description et **quatre photos** deviennent obligatoires ;
elles sont conservées sur la réservation et servent de preuve en cas de
litige. La même règle s'applique au retour, pour comparer avec le départ.

La caution, elle, se décide véhicule par véhicule depuis sa fiche. Quand
l'agence ne l'exige pas, l'étape 4 est sautée, la réservation part sans
caution et le retour se clôture sans restitution. Le montant reste figé sur
les réservations déjà prises : changer d'avis ne réécrit pas un dossier.

Le permis du locataire n'est pas vérifié à distance : la caissière contrôle
l'original au guichet, le photographie, et cette photo est enregistrée sur la
réservation. L'enregistrement fait passer la réservation en « en cours » — la
voiture est partie. N'importe quel employé peut aussi joindre cette photo plus
tôt, depuis le dossier de la réservation : à la remise des clés, une nouvelle
photo n'est alors demandée que si le dossier n'en contient pas. Une prestation
avec chauffeur ne demande pas de permis.

On ne rend pas les clés avant d'avoir tout validé : c'est la seule façon que
la clôture de journée corresponde à ce qui s'est passé.

## Tâches planifiées

```php
// routes/console.php
Schedule::command('bookings:mark-no-shows')->everyFifteenMinutes();
Schedule::command('bookings:send-reminders')->dailyAt('09:00');
Schedule::command('documents:purge')->dailyAt('03:00');
```

## Langues

Toutes les pages publiques en français et en anglais, avec préfixe d'URL
(`/fr/...`, `/en/...`). Un lien envoyé par WhatsApp s'ouvre dans la langue
du lien, pas dans celle du destinataire.

Le tunnel de réservation et l'administration suivent la langue de l'URL. Il
n'y a pas d'espace client : le public réserve en mode invité, sans compte, et
la seule page personnelle — le profil du personnel — reste en français.

## Tests

```bash
php vendor/bin/phpunit tests/Unit
```

Le calcul des blocs de chauffeur et le test de chevauchement sont couverts
cas par cas, parce que ce sont les deux endroits où une erreur se voit sur
la facture du client.

## Réglages

Tout ce qui touche au métier est modifiable depuis `/admin/parametres`, sans
redéploiement : horaires, battement, délai de grâce, tarif du bloc chauffeur,
âge minimum, ancienneté du permis, vitesse maximale, cautions par catégorie
(chaque véhicule pouvant en être dispensé),
nombre de réservations actives, seuil de blocage pour absence, devise
(`general.currency`), préfixe des références de réservation
(`booking.reference_prefix`).

L'identité visuelle se règle depuis `/admin/apparence` : couleurs, monogramme,
nom affiché, et le logo de l'agence (JPG, PNG ou WebP, 2 Mo maximum), stocké
sur le disque public et affiché partout — en-tête, pied de page, panneau
d'administration. Sans logo, le monogramme sert de repli.

Les textes du bloc chauffeur de l'accueil — badge « Options premium », titre,
arguments (mariages compris), bouton et introduction de la page chauffeur —
se modifient depuis `/admin/contenus/bloc-chauffeur`, en français et en
anglais. Un champ vidé réaffiche le texte livré avec l'application ; les
montants restent dans `/admin/parametres`.

Aucun montant n'est écrit en dur : tout affichage passe par
`SettingsService::money()`, qui lit la devise en cours. Changer la devise sur
`/admin/parametres` suffit à faire basculer le site entier — prix, cautions,
pénalités, reçus et tableaux de bord.

## Passer en production
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` en `https://` et certificat TLS actif
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] sauvegardes MySQL quotidiennes, rétention 30 jours
- [ ] brancher la passerelle WhatsApp dans `NotificationService::raw()`
- [ ] photos réelles de la flotte et de la façade de l'agence
- [ ] changer le mot de passe des comptes de démonstration, ou les supprimer
