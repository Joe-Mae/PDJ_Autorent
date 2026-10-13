<?php

use App\Http\Controllers\Agent\CashReportController;
use App\Http\Controllers\Agent\PickupController;
use App\Http\Controllers\Agent\ReturnController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Écrans agent (tablette au guichet)
|--------------------------------------------------------------------------
| Hors du layout admin : gros boutons, une action par écran, parcours
| verrouillé dans l'ordre. On ne lance un retrait qu'après le contrôle des
| originaux, et on ne rend la caution qu'après le contrôle du carburant.
*/

Route::prefix('agent')
    ->middleware(['auth', EnsureStaff::class])
    ->name('agent.')
    ->group(function () {

        // Retrait
        Route::get('/retrait', [PickupController::class, 'index'])->name('pickup.index');
        Route::post('/retrait', [PickupController::class, 'pick'])->name('pickup.pick');
        Route::get('/retrait/{booking}/originaux', [PickupController::class, 'documents'])->name('pickup.documents');
        Route::post('/retrait/{booking}/originaux', [PickupController::class, 'storeDocuments'])->name('pickup.documents.store');
        Route::get('/retrait/{booking}/etat-des-lieux', [PickupController::class, 'conditionReport'])->name('pickup.condition');
        Route::post('/retrait/{booking}/etat-des-lieux', [PickupController::class, 'storeConditionReport'])->name('pickup.condition.store');
        Route::get('/retrait/{booking}/encaissement', [PickupController::class, 'payment'])->name('pickup.payment');
        Route::post('/retrait/{booking}/encaissement', [PickupController::class, 'storePayment'])->name('pickup.payment.store');
        Route::get('/retrait/{booking}/caution', [PickupController::class, 'deposit'])->name('pickup.deposit');
        Route::post('/retrait/{booking}/caution', [PickupController::class, 'storeDeposit'])->name('pickup.deposit.store');
        Route::get('/retrait/{booking}/contrat', [PickupController::class, 'contract'])->name('pickup.contract');
        Route::post('/retrait/{booking}/contrat', [PickupController::class, 'signContract'])->name('pickup.contract.store');
        Route::get('/retrait/{booking}/cle', [PickupController::class, 'keys'])->name('pickup.keys');
        Route::post('/retrait/{booking}/cle', [PickupController::class, 'complete'])->name('pickup.complete');

        // Retour
        Route::get('/retour', [ReturnController::class, 'index'])->name('return.index');
        Route::get('/retour/{booking}', [ReturnController::class, 'control'])->name('return.control');
        Route::post('/retour/{booking}', [ReturnController::class, 'store'])->name('return.store');
        Route::get('/retour/{booking}/caution', [ReturnController::class, 'deposit'])->name('return.deposit');
        Route::post('/retour/{booking}/caution', [ReturnController::class, 'refundDeposit'])->name('return.deposit.store');

        // Caisse
        Route::get('/caisse', [CashReportController::class, 'index'])->name('cash.index');
        Route::post('/caisse/cloture', [CashReportController::class, 'close'])->name('cash.close');
    });
