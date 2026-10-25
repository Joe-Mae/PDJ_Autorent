<?php

namespace App\Services;

use App\Mail\BookingMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    public function __construct(private readonly SettingsService $settings) {}

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

            $this->queueBookingMail($booking, $email, 'received', 'booking.received', 'received', $lines);
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
            $this->queueBookingMail($booking, $email, 'confirmed', 'booking.confirmed', 'confirmed', [
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
            $this->queueBookingMail($booking, $email, 'refused', 'booking.refused', 'refused', [
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
            $this->queueBookingMail($booking, $email, 'cancelled', 'booking.cancelled', 'cancelled', [
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
     * Mets l'e-mail en file d'attente, jamais envoyé depuis la requête HTTP.
     *
     * Un e-mail ne part que s'il y a une adresse : la majorité des clients ne
     * donnent que leur numéro, et le canal WhatsApp reste le canal réel. En
     * l'absence de file (worker non lancé), l'envoi se fait dans la foulée —
     * l'e-mail est un miroir, il ne doit jamais faire échouer la réservation.
     *
     * @param  array<string, string>  $lines
     */
    private function queueBookingMail(Booking $booking, string $email, string $subjectKey, string $introKey, string $footerKey, array $lines): void
    {
        if ($booking->with_driver) {
            $lines[__('notifications.mail.lines.driver')] = __('booking.reminder.no_license_needed');
        }

        Mail::to($email)->queue(
            new BookingMail($booking, $subjectKey, 'notifications.intros.'.$introKey, $lines, 'notifications.footers.'.$footerKey)
        );
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
}
