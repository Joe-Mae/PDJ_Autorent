<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\BookingReceiptController;
use App\Http\Controllers\DriverOnlyBookingController;
use App\Http\Controllers\GuestBookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\VehicleController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du site
|--------------------------------------------------------------------------
| La langue est dans l'URL (/fr, /en) : un lien partagé s'ouvre toujours
| dans la langue du lien, et le bouton de bascule change d'adresse.
*/

Route::redirect('/', '/fr')->name('root');

Route::prefix('{locale}')
    ->whereIn('locale', ['fr', 'en'])
    ->middleware(SetLocale::class)
    ->group(function () {

        // ── Public ───────────────────────────────────────────────────────────
        Route::get('/', HomeController::class)->name('home');

        Route::get('/vehicules', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicules/categorie/{category}', [VehicleController::class, 'index'])->name('vehicles.category');
        Route::get('/vehicules/{vehicle}/devis', [GuestBookingController::class, 'quote'])
            ->middleware('throttle:60,1')
            ->name('booking.guest.quote');
        Route::get('/vehicules/{vehicle}/reserver-sans-compte', [GuestBookingController::class, 'create'])->name('booking.guest.create');
        Route::post('/vehicules/{vehicle}/reserver-sans-compte', [GuestBookingController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('booking.guest.store');
        Route::get('/vehicules/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::get('/demande-reservation/{booking}/{token}', [GuestBookingController::class, 'confirmation'])
            ->middleware('throttle:30,1')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('booking.guest.confirmation');
        Route::get('/consulter-reservation', [GuestBookingController::class, 'lookup'])->name('booking.lookup');
        Route::post('/consulter-reservation', [GuestBookingController::class, 'lookupStore'])
            ->middleware('throttle:20,1')
            ->name('booking.lookup.store');

        Route::get('/demande-reservation/{booking}/{token}/documents', [GuestBookingController::class, 'documents'])
            ->middleware('throttle:30,1')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('booking.guest.documents');
        Route::post('/demande-reservation/{booking}/{token}/documents', [BookingDocumentController::class, 'storeGuest'])
            ->middleware('throttle:20,1')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('booking.guest.documents.store');
        Route::delete('/demande-reservation/{booking}/{token}/documents/{document}', [BookingDocumentController::class, 'destroyGuest'])
            ->middleware('throttle:20,1')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('booking.guest.documents.destroy');
        Route::post('/vehicules/filtres', [VehicleController::class, 'filter'])->name('vehicles.filter');

        // ── Tunnel de réservation ────────────────────────────────────────────
        Route::post('/reservation', [BookingController::class, 'create'])->name('booking.create');
        Route::get('/reservation/{booking}', [BookingController::class, 'show'])->name('booking.show');
        Route::post('/reservation/{booking}/chauffeur', [BookingController::class, 'toggleDriver'])->name('booking.driver');
        Route::get('/reservation/{booking}/conducteur', [BookingController::class, 'contact'])->name('booking.contact');
        Route::post('/reservation/{booking}/conducteur', [BookingController::class, 'storeContact'])->name('booking.contact.store');
        Route::get('/reservation/{booking}/verification', [BookingController::class, 'verifyPhone'])->name('booking.verify-phone');
        Route::post('/reservation/{booking}/verification', [BookingController::class, 'verifyPhoneStore'])->name('booking.verify-phone.store');
        Route::post('/reservation/{booking}/verification/renvoi', [BookingController::class, 'resendCode'])->name('booking.code.resend');
        Route::get('/reservation/{booking}/documents', [BookingController::class, 'documents'])->name('booking.documents');
        Route::post('/reservation/{booking}/documents', [BookingDocumentController::class, 'store'])->name('booking.documents.store');
        Route::delete('/reservation/{booking}/documents/{document}', [BookingDocumentController::class, 'destroy'])->name('booking.documents.destroy');
        Route::get('/reservation/{booking}/recapitulatif', [BookingController::class, 'review'])->name('booking.review');
        Route::post('/reservation/{booking}/confirmer', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/reservation/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');
        Route::get('/reservation/{booking}/bon.pdf', BookingReceiptController::class)->name('booking.receipt');

        // Cible du bouton « Je confirme ma venue » du rappel WhatsApp : hors
        // préfixe de langue, car le lien est envoyé tel quel dans le message.
        Route::get('/confirmer-ma-venue/{token}', [BookingController::class, 'confirmPresence'])
            ->middleware('throttle:10,1')
            ->name('booking.confirm-presence');

        // ── Comptes ──────────────────────────────────────────────────────────
        Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
        Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

        // Le profil est le seul écran personnel : il est réservé au personnel
        // connecté, comme le reste de l'espace de gestion.
        Route::middleware(['auth', 'staff'])->group(function () {
            Route::get('/mon-espace/profil', [Account\ProfileController::class, 'edit'])->name('account.profile');
            Route::put('/mon-espace/profil', [Account\ProfileController::class, 'update'])->name('account.profile.update');
            Route::put('/mon-espace/profil/mot-de-passe', [Account\ProfileController::class, 'updatePassword'])->name('account.password.update');
        });

        // ── Pages de contenu ─────────────────────────────────────────────────
        Route::get('/l-agence', [PageController::class, 'agency'])->name('page.agency');
        Route::get('/contact', [PageController::class, 'contact'])->name('page.contact');
        Route::post('/contact', [PageController::class, 'sendContact'])->middleware('throttle:3,1')->name('page.contact.store');
        Route::get('/faq', [PageController::class, 'faq'])->name('page.faq');
        Route::get('/avis', [PageController::class, 'reviews'])->name('page.reviews');
        Route::get('/location-avec-chauffeur', [PageController::class, 'withDriver'])->name('page.driver');
        Route::post('/chauffeur-seul', [DriverOnlyBookingController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('driver-only-bookings.store');
        Route::get('/comment-payer', [PageController::class, 'howToPay'])->name('page.payment');
        Route::get('/caution', [PageController::class, 'deposit'])->name('page.deposit');
        Route::get('/conditions-generales', [PageController::class, 'terms'])->name('page.terms');
        Route::get('/assurance', [PageController::class, 'insurance'])->name('page.insurance');
        Route::get('/confidentialite', [PageController::class, 'privacy'])->name('page.privacy');
        Route::get('/mentions-legales', [PageController::class, 'legal'])->name('page.legal');

        Route::get('/telephone/{code}', [PhoneVerificationController::class, 'show'])->name('phone.verify');
        Route::post('/telephone/{code}', [PhoneVerificationController::class, 'verify'])->name('phone.verify.store');
    });
