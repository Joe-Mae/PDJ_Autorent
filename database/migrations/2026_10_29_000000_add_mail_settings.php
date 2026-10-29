<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réglages d'envoi d'e-mails, saisis depuis /admin/emails.
 *
 * Les valeurs par défaut laissent l'envoi désactivé : une agence qui n'a pas
 * encore rempli son serveur SMTP continue de fonctionner exactement comme
 * avant, sans message d'erreur à chaque réservation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['mail.enabled', '0', 'bool', 'Activer l’envoi des e-mails'],
            ['mail.host', '', 'string', 'Serveur SMTP (hôte)'],
            ['mail.port', '587', 'int', 'Port SMTP'],
            ['mail.encryption', 'tls', 'string', 'Chiffrement SMTP (tls, ssl ou none)'],
            ['mail.username', '', 'string', 'Identifiant SMTP'],
            ['mail.password', '', 'string', 'Mot de passe SMTP (chiffré)'],
            ['mail.from_address', '', 'string', 'Adresse d’expédition des e-mails'],
            ['mail.from_name', '', 'string', 'Nom affiché de l’expéditeur'],
            ['mail.notification_address', '', 'string', 'Boîte qui reçoit les nouvelles demandes'],
        ];

        foreach ($settings as [$key, $value, $type, $label]) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => 'mail',
                'label_fr' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Réglages modifiables par l'agence : un rollback ne doit pas effacer
        // les identifiants SMTP déjà saisis.
    }
};
