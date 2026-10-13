<?php

use App\Models\CustomerDocument;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Affiche une citation');

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
| Trois rhythmmes, trois raisons :
|   - le passage en « non présenté » est scruté toutes les 15 minutes, parce
|     que chaque quart d'heure compte sur la disponibilité d'une voiture ;
|   - le rappel J-1 part le matin, quand le client pense à sa location ;
|   - la purge des documents tourne la nuit, quand personne n'attend.
*/

Schedule::command('bookings:mark-no-shows')->everyFifteenMinutes();

Schedule::command('bookings:send-reminders')->dailyAt('09:00');

Schedule::call(function () {
    CustomerDocument::query()
        ->whereNotNull('purge_after')
        ->where('purge_after', '<', now())
        ->each(function (CustomerDocument $document) {
            // Le fichier part avec la ligne : le deleting() du modèle s'en charge.
            $document->delete();
        });
})->dailyAt('03:00')->name('documents:purge');
