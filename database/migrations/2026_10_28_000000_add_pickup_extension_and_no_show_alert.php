<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Absence au retrait : rappel aux agents à 2 h, annulation automatique à 5 h.
 *
 * L'heure de retrait convenue peut être décalée par un agent quand le client
 * prévient qu'il arrive plus tard. On la stocke à part plutôt que de toucher à
 * `starts_at` : la période de location et son prix ne changent pas, seul le
 * compte à rebours de l'absence s'appuie sur cette heure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('pickup_extended_until')->nullable()->after('grace_expires_at');
        });

        // Le délai avant annulation passe de 2 h à 5 h. On ne réécrit que la
        // valeur encore égale à l'ancien défaut : un réglage volontairement
        // personnalisé par l'agence n'est pas écrasé.
        DB::table('settings')
            ->where('key', 'booking.grace_period_hours')
            ->where('value', '2')
            ->update([
                'value' => '5',
                'label_fr' => 'Délai avant annulation automatique pour absence (heures)',
                'help_fr' => 'Passé ce délai après l’heure de retrait convenue, la réservation est annulée et la voiture redevient disponible.',
                'updated_at' => now(),
            ]);

        DB::table('settings')->insertOrIgnore([
            'key' => 'booking.no_show_alert_hours',
            'value' => '2',
            'type' => 'int',
            'group' => 'reservation',
            'label_fr' => 'Rappel d’absence aux agents (heures après le retrait)',
            'help_fr' => 'Une fenêtre s’ouvre aux agents pour joindre le client, décaler le retrait ou annuler avec un motif.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Les réservations déjà confirmées portent une échéance calculée avec
        // l'ancien délai de 2 h : on les réaligne pour ne pas les annuler trop tôt.
        $confirmed = DB::table('bookings')
            ->where('status', 'confirmee')
            ->whereNull('pickup_extended_until')
            ->get(['id', 'starts_at']);

        foreach ($confirmed as $booking) {
            DB::table('bookings')->where('id', $booking->id)->update([
                'grace_expires_at' => Carbon::parse($booking->starts_at)->addHours(5),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('pickup_extended_until');
        });

        DB::table('settings')->where('key', 'booking.no_show_alert_hours')->delete();

        DB::table('settings')
            ->where('key', 'booking.grace_period_hours')
            ->update(['value' => '2']);
    }
};
