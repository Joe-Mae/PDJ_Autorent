<?php

namespace App\Services;

use App\Mail\AgencyRequestMail;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Models\DriverOnlyBooking;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envois client : WhatsApp d'abord, SMS en secours, e-mail pour les documents.
 *
 * Le driver de canaux est volontairement simple : en V1 l'envoi WhatsApp passe
 * par une passerelle HTTP (Meta Cloud API ou un service local type BulkSMS). Tant
 * qu'elle n'est pas branchée, on journalise et on ne casse pas le tunnel — une
 * réservation ne doit jamais échouer parce qu'une passerelle SMS est hors ligne.
 */
class NotificationService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MailConfigurator $mail,
    ) {}

    public function sendBookingReceived(Booking $booking, ?string $email = null): bool
    {
        $sent = $this->dispatch($booking, 'whatsapp', 'booking.received', [
            'reference' => $booking->reference,
            'vehicle' => $booking->vehicle?->displayName(),
            'debut' => $booking->starts_at->translatedFormat('d/m H:i'),
            'fin' => $booking->ends_at->translatedFormat('d/m H:i'),
            'total' => $this->formatAmount((int) $booking->price_total),
            'a_payer' => $this->formatAmount((int) $booking->price_total),
            'caution' => $this->formatAmount((int) $booking->deposit_amount),
            'a_emporter' => $this->formatAmount($booking->amountToCarry()),
            'permis' => $booking->with_driver
                ? __('booking.reminder.no_license_needed')
                : __('booking.reminder.license_needed'),
            'lien' => $this->publicLink($booking),
        ]);

        if ($email !== null) {
            $lines = [
                __('notifications.mail.lines.reference') => $booking->reference,
                __('notifications.mail.lines.vehicle') => $booking->vehicle?->displayName() ?? '—',
                __('notifications.mail.lines.period') => $booking->starts_at->translatedFormat('d/m H:i').' → '.$booking->ends_at->translatedFormat('d/m H:i'),
                __('notifications.mail.lines.to_pay') => $this->formatAmount($booking->amountToCarry()),
            ];

            // Une location sans caution n'a pas de ligne « caution : 0 » à
            // afficher dans le mail de confirmation.
            if ($booking->requiresDeposit()) {
                $lines[__('notifications.mail.lines.deposit')] = $this->formatAmount((int) $booking->deposit_amount);
            }

            $this->sendBookingMail($booking, $email, 'received', 'booking.received', 'received', $lines);
        }

        return $sent;
    }

    public function sendBookingConfirmed(Booking $booking, ?string $email = null): bool
    {
        $sent = $this->dispatch($booking, 'whatsapp', 'booking.confirmed', [
            'reference' => $booking->reference,
            'vehicle' => $booking->vehicle?->displayName(),
            'debut' => $booking->starts_at->translatedFormat('d/m H:i'),
            'fin' => $booking->ends_at->translatedFormat('d/m H:i'),
            'a_emporter' => $this->formatAmount($booking->amountToCarry()),
            'annulation' => $this->cancellationDeadline($booking),
            'agence' => $this->settings->formattedPhone(),
            'documents' => $booking->with_driver
                ? __('booking.documents_for_pickup.with_driver')
                : __('booking.documents_for_pickup.self_drive'),
        ]);

        if ($email !== null) {
            $this->sendBookingMail($booking, $email, 'confirmed', 'booking.confirmed', 'confirmed', [
                __('notifications.mail.lines.reference') => $booking->reference,
                __('notifications.mail.lines.vehicle') => $booking->vehicle?->displayName() ?? '—',
                __('notifications.mail.lines.period') => $booking->starts_at->translatedFormat('d/m H:i').' → '.$booking->ends_at->translatedFormat('d/m H:i'),
                __('notifications.mail.lines.to_pay') => $this->formatAmount($booking->amountToCarry()),
            ]);
        }

        return $sent;
    }

    public function sendBookingRefused(Booking $booking, string $reason, ?string $email = null): bool
    {
        $sent = $this->dispatch($booking, 'whatsapp', 'booking.refused', [
            'reference' => $booking->reference,
            'motif' => $reason,
            'agence' => $this->settings->formattedPhone(),
        ]);

        if ($email !== null) {
            $this->sendBookingMail($booking, $email, 'refused', 'booking.refused', 'refused', [
                __('notifications.mail.lines.reference') => $booking->reference,
                __('notifications.mail.lines.reason') => $reason,
            ]);
        }

        return $sent;
    }

    public function sendBookingCancelled(Booking $booking, ?string $email = null): bool
    {
        $sent = $this->dispatch($booking, 'whatsapp', 'booking.cancelled', [
            'reference' => $booking->reference,
            'agence' => $this->settings->formattedPhone(),
        ]);

        if ($email !== null) {
            $this->sendBookingMail($booking, $email, 'cancelled', 'booking.cancelled', 'cancelled', [
                __('notifications.mail.lines.reference') => $booking->reference,
            ]);
        }

        return $sent;
    }

    /** Rappel J-1 : le message le plus important pour éviter une absence. */
    public function sendPickupReminder(Booking $booking): bool
    {
        return $this->dispatch($booking, 'whatsapp', 'booking.reminder', [
            'reference' => $booking->reference,
            'vehicle' => $booking->vehicle?->displayName(),
            'demain' => $booking->starts_at->translatedFormat('d/m \à H:i'),
            'agence' => $this->settings->string('agency.address'),
            'lien' => $this->reminderLink($booking),
        ]);
    }

    public function sendNoShowNotice(Booking $booking): bool
    {
        return $this->dispatch($booking, 'whatsapp', 'booking.no_show', [
            'reference' => $booking->reference,
            'agence' => $this->settings->formattedPhone(),
        ]);
    }

    public function sendPhoneCode(string $phone, string $code, string $channel = 'whatsapp'): bool
    {
        return $this->raw($phone, $channel, 'phone.code', ['code' => $code]);
    }

    public function publicLink(Booking $booking): string
    {
        return route('booking.show', [
            'locale' => $booking->user?->preferred_locale ?? 'fr',
            'booking' => $booking,
        ]);
    }

    /** Lien de rappel : jeton opaque, jamais la référence seule. */
    public function reminderLink(Booking $booking): string
    {
        return route('booking.confirm-presence', [
            'token' => $booking->public_token,
        ]);
    }

    public function cancellationDeadline(Booking $booking): string
    {
        return $booking->starts_at
            ->copy()
            ->subDay()
            ->setTimeFromTimeString($this->settings->string('booking.free_cancellation_before'))
            ->translatedFormat('d/m \à H:i');
    }

    public function formatAmount(int $amount): string
    {
        return $this->settings->money($amount);
    }

    /**
     * Envoie l'e-mail au client.
     *
     * L'envoi est synchrone : sans worker de file, un message « en file »
     * n'arriverait jamais. Le délai SMTP est borné (10 s) et toute erreur est
     * journalisée sans interrompre le tunnel — une réservation ne doit jamais
     * échouer parce que la boîte de l'agence est muette.
     *
     * @param  array<string, string>  $lines
     */
    private function sendBookingMail(Booking $booking, string $email, string $subjectKey, string $introKey, string $footerKey, array $lines): void
    {
        if ($booking->with_driver) {
            $lines[__('notifications.mail.lines.driver')] = __('booking.reminder.no_license_needed');
        }

        $this->send(
            $email,
            new BookingMail($booking, $subjectKey, 'notifications.intros.'.$introKey, $lines, 'notifications.footers.'.$footerKey),
        );
    }

    /**
     * Prévient l'agence qu'une demande attend une confirmation.
     *
     * Sans cette alerte, une réservation déposée le soir pouvait attendre le
     * lendemain : le client, lui, recevait déjà son accusé de réception.
     */
    public function sendNewBookingRequest(Booking $booking): bool
    {
        $email = $this->mail->notificationAddress();

        if ($email === '') {
            return false;
        }

        $lines = [
            __('notifications.mail.lines.client') => $booking->user?->displayName() ?? $booking->customer_name ?? '—',
            __('notifications.mail.lines.phone') => $booking->user?->phone ?? $booking->customer_phone ?? '—',
            __('notifications.mail.lines.vehicle') => $booking->vehicle?->displayName() ?? '—',
            __('notifications.mail.lines.period') => $booking->starts_at->translatedFormat('d/m H:i').' → '.$booking->ends_at->translatedFormat('d/m H:i'),
            __('notifications.mail.lines.to_pay') => $this->formatAmount($booking->amountToCarry()),
            __('notifications.mail.lines.driver_option') => $booking->with_driver
                ? __('notifications.mail.with_driver')
                : __('notifications.mail.without_driver'),
        ];

        $clientEmail = $booking->customer_email ?? $booking->user?->email;

        if (filled($clientEmail)) {
            $lines[__('notifications.mail.lines.email')] = $clientEmail;
        }

        return $this->send($email, new AgencyRequestMail(
            reference: $booking->reference,
            lines: $lines,
            url: route('admin.bookings.show', ['booking' => $booking]),
        ));
    }

    /** Même alerte pour une demande de chauffeur seul, qui n'a pas d'e-mail client. */
    public function sendNewDriverOnlyRequest(DriverOnlyBooking $booking): bool
    {
        $email = $this->mail->notificationAddress();

        if ($email === '') {
            return false;
        }

        return $this->send($email, new AgencyRequestMail(
            reference: $booking->reference,
            lines: [
                __('notifications.mail.lines.client') => $booking->customer_name,
                __('notifications.mail.lines.phone') => $booking->phone,
                __('notifications.mail.lines.service') => __('driver.solo_title'),
                __('notifications.mail.lines.period') => $booking->starts_at->translatedFormat('d/m H:i').' → '.$booking->ends_at->translatedFormat('d/m H:i'),
                __('notifications.mail.lines.to_pay') => $this->formatAmount((int) $booking->price_total),
            ],
            url: route('admin.driver-only-bookings.index'),
            subjectKey: 'new_driver_only_request',
            introKey: 'new_driver_only_request',
        ));
    }

    /**
     * Point d'envoi unique : jamais d'exception vers l'appelant.
     *
     * Le mail est un miroir de la réservation, pas sa condition de succès. Une
     * panne SMTP doit rester visible dans les journaux, pas casser le tunnel.
     */
    private function send(string $email, Mailable $mailable): bool
    {
        try {
            Mail::to($email)->send($mailable);

            return true;
        } catch (Throwable $e) {
            Log::error('Échec de l’envoi d’un e-mail', [
                'destinataire' => self::maskEmail($email),
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function dispatch(Booking $booking, string $channel, string $template, array $data): bool
    {
        $phone = $booking->user?->phone ?? $booking->customer_phone;

        return $phone ? $this->raw($phone, $channel, $template, $data) : false;
    }

    /**
     * Point de sortie unique. À brancher sur la passerelle WhatsApp/SMS réelle.
     *
     * @param  array<string, mixed>  $data
     */
    private function raw(string $phone, string $channel, string $template, array $data): bool
    {
        $data['company'] = $this->settings->string('agency.name');

        $message = __('notifications.'.$template, $data, 'fr');

        Log::info('notification sortante', [
            'canal' => $channel,
            'modele' => $template,
            'destinataire' => self::maskPhone($phone),
            'message' => $message,
        ]);

        return true;
    }

    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) < 6 ? '***' : '***'.substr($digits, -4);
    }

    /** Les journaux ne doivent pas recopier l'adresse complète. */
    private static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return $domain === '' ? '***' : substr($local, 0, 1).'***@'.$domain;
    }
}
