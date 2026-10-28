<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Agent;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
| Trois rôles, trois périmètres emboîtés — voir App\Support\StaffTier :
|
|   agent_accueil  → tableau de bord, réservations, documents, paiements,
|                     clients, caisse. C'est le quotidien du guichet.
|   gestionnaire    → + véhicules, catégories, chauffeurs, contenus.
|                     Le parc et les tarifs appartiennent à la direction.
|   super_admin     → + paramètres de l'agence. Horaires, caution, prix du
|                     chauffeur : ce qui engage l'entreprise.
|
| Le tableau de bord reste ouvert à tout employé, mais il n'affiche que ce que
| le rôle voit — un agent d'accueil n'a aucune raison de lire le prix d'un
| utilitaire.
*/

Route::prefix('admin')
    ->middleware(['auth', EnsureStaff::class])
    ->name('admin.')
    ->group(function () {

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // ── Périmètre gestionnaire : le parc et les tarifs ───────────────────
        Route::middleware('tier:gestionnaire')->group(function () {

            Route::get('/statistiques', [Admin\StatisticsController::class, 'index'])->name('statistics.index');

            // Véhicules et catalogue. L'URL reste au pluriel (c'est ce que voit
            // l'agent), le nom de route est au singulier (c'est ce qu'on écrit).
            Route::delete('vehicules/bulk', [Admin\VehicleController::class, 'destroyMany'])
                ->name('vehicles.destroyMany');
            Route::post('vehicules/remettre-en-service', [Admin\VehicleController::class, 'restoreMany'])
                ->name('vehicles.restoreMany');
            Route::post('vehicules/{vehicle}/statut', [Admin\VehicleController::class, 'setStatus'])
                ->name('vehicles.setStatus');
            Route::post('vehicules/{vehicle}/remettre-en-service', [Admin\VehicleController::class, 'restore'])
                ->withTrashed()
                ->name('vehicles.restore');
            Route::resource('vehicules', Admin\VehicleController::class)
                ->parameters(['vehicules' => 'vehicle'])
                ->names([
                    'index' => 'vehicles.index',
                    'create' => 'vehicles.create',
                    'store' => 'vehicles.store',
                    'show' => 'vehicles.show',
                    'edit' => 'vehicles.edit',
                    'update' => 'vehicles.update',
                    'destroy' => 'vehicles.destroy',
                ]);

            Route::resource('categories', Admin\CategoryController::class)
                ->parameters(['categories' => 'category'])
                ->names([
                    'index' => 'categories.index',
                    'create' => 'categories.create',
                    'store' => 'categories.store',
                    'show' => 'categories.show',
                    'edit' => 'categories.edit',
                    'update' => 'categories.update',
                    'destroy' => 'categories.destroy',
                ]);

            Route::resource('chauffeurs', Admin\DriverController::class)
                ->parameters(['chauffeurs' => 'driver'])
                ->names([
                    'index' => 'drivers.index',
                    'create' => 'drivers.create',
                    'store' => 'drivers.store',
                    'show' => 'drivers.show',
                    'edit' => 'drivers.edit',
                    'update' => 'drivers.update',
                    'destroy' => 'drivers.destroy',
                ]);
            Route::get('/reservations-chauffeur-seul', [Admin\DriverOnlyBookingController::class, 'index'])
                ->name('driver-only-bookings.index');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/confirmer', [Admin\DriverOnlyBookingController::class, 'confirm'])
                ->name('driver-only-bookings.confirm');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/refuser', [Admin\DriverOnlyBookingController::class, 'refuse'])
                ->name('driver-only-bookings.refuse');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/demarrer', [Admin\DriverOnlyBookingController::class, 'start'])
                ->name('driver-only-bookings.start');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/terminer', [Admin\DriverOnlyBookingController::class, 'finish'])
                ->name('driver-only-bookings.finish');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/annuler', [Admin\DriverOnlyBookingController::class, 'cancel'])
                ->name('driver-only-bookings.cancel');
            Route::post('/reservations-chauffeur-seul/{driverOnlyBooking}/chauffeur', [Admin\DriverOnlyBookingController::class, 'assign'])
                ->name('driver-only-bookings.assign');

            // Contenus éditables du site
            Route::get('/contenus', [Admin\ContentController::class, 'index'])->name('contents.index');
            Route::get('/contenus/bloc-chauffeur', [Admin\ContentController::class, 'driver'])->name('contents.driver.edit');
            Route::post('/contenus/bloc-chauffeur', [Admin\ContentController::class, 'updateDriver'])->name('contents.driver.update');
            Route::get('/contenus/{type}', [Admin\ContentController::class, 'edit'])->name('contents.edit');
            Route::post('/contenus/{type}', [Admin\ContentController::class, 'update'])->name('contents.update');
            Route::get('/contenus/{type}/{id}/modifier', [Admin\ContentController::class, 'form'])->name('contents.form');
            Route::post('/contenus/{type}/{id}', [Admin\ContentController::class, 'save'])->name('contents.save');
            Route::delete('/contenus/{type}/{id}', [Admin\ContentController::class, 'destroy'])->name('contents.destroy');

            Route::get('/apparence-site', [Admin\SiteAppearanceController::class, 'edit'])->name('appearance.edit');
            Route::post('/apparence-site', [Admin\SiteAppearanceController::class, 'update'])->name('appearance.update');
        });

        // ── Périmètre agent d'accueil : le guichet ──────────────────────────
        Route::get('/reservations', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/reservations/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::post('/reservations/{booking}/confirmer', [Admin\BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::post('/reservations/{booking}/refuser', [Admin\BookingController::class, 'refuse'])->name('bookings.refuse');
        Route::post('/reservations/{booking}/annuler', [Admin\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/reservations/{booking}/modifier', [Admin\BookingController::class, 'update'])->name('bookings.update');
        Route::post('/reservations/{booking}/chauffeur/{driver}', [Admin\BookingController::class, 'assignDriver'])->name('bookings.driver.assign');
        Route::post('/reservations/{booking}/permis', [Admin\DocumentController::class, 'storePermit'])->name('bookings.permit.store');

        // File de vérification des documents
        Route::get('/documents', [Admin\DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}', [Admin\DocumentController::class, 'show'])->name('documents.show');
        Route::post('/documents/{document}/verdict', [Admin\DocumentController::class, 'review'])->name('documents.review');

        // Clients : voir, bloquer, demander un acompte. Rouvrir un compte bloqué
        // est une décision de la direction, pas du guichet.
        Route::get('/clients', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/clients/invites/{bookingId}', [Admin\CustomerController::class, 'showGuest'])->name('customers.guest.show');
        Route::get('/clients/{user}', [Admin\CustomerController::class, 'show'])->name('customers.show');
        Route::middleware('tier:gestionnaire')->group(function () {
            Route::post('/clients/{user}/debloquer', [Admin\CustomerController::class, 'unblock'])->name('customers.unblock');
            Route::post('/clients/{user}/prepayer', [Admin\CustomerController::class, 'requirePrepayment'])->name('customers.prepay');
        });

        // Caisse
        Route::get('/paiements', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('/paiements', [Admin\PaymentController::class, 'store'])->name('payments.store');
        Route::post('/paiements/{payment}/restituer', [Admin\PaymentController::class, 'refundDeposit'])->name('payments.refund');
        Route::get('/rapport-caisse', [Admin\PaymentController::class, 'report'])->name('payments.report');

        // ── Périmètre super-admin : ce qui engage l'agence ───────────────────
        Route::middleware('tier:super_admin')->group(function () {
            Route::get('/parametres', [Admin\SettingController::class, 'index'])->name('settings.index');
            Route::post('/parametres', [Admin\SettingController::class, 'update'])->name('settings.update');
        });
    });
