<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestMailRequest;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Services\MailConfigurator;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Configuration des e-mails sortants.
 *
 * L'agence change de boîte mail sans développeur : tout ce que Laravel lit
 * dans .env (hôte, port, chiffrement, identifiants, expéditeur) se saisit ici
 * et s'applique à l'exécution. Un bouton d'envoi de test permet de vérifier
 * les identifiants avant de laisser partir les vraies demandes.
 *
 * Réservé au super-administrateur : ces réglages engagent l'entreprise, au
 * même titre que les tarifs et les horaires.
 */
class MailSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MailConfigurator $mail,
    ) {}

    public function edit(): View
    {
        $this->settings->flush();

        return view('admin.mail.edit', [
            'enabled' => $this->settings->bool('mail.enabled'),
            'host' => $this->settings->string('mail.host'),
            'port' => $this->settings->int('mail.port') ?: 587,
            'encryption' => $this->mail->encryption(),
            'username' => $this->settings->string('mail.username'),
            'hasPassword' => $this->mail->hasPassword(),
            'fromAddress' => $this->settings->string('mail.from_address'),
            'fromName' => $this->settings->string('mail.from_name'),
            'notificationAddress' => $this->mail->notificationAddress(),
            'contactEmail' => $this->settings->string('contact.email'),
            'configured' => $this->mail->isConfigured(),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settings->putMany([
            'mail.enabled' => $request->boolean('enabled') ? '1' : '0',
            'mail.host' => trim((string) ($data['host'] ?? '')),
            'mail.port' => (int) ($data['port'] ?? 587),
            'mail.encryption' => $data['encryption'],
            'mail.username' => trim((string) ($data['username'] ?? '')),
            'mail.from_address' => trim($data['from_address']),
            'mail.from_name' => trim((string) ($data['from_name'] ?? '')),
            'mail.notification_address' => trim((string) ($data['notification_address'] ?? '')),
        ]);

        // Le mot de passe n'est réécrit que s'il est saisi : un formulaire
        // enregistré sans y toucher ne doit pas effacer les identifiants.
        if (filled($data['password'] ?? null)) {
            $this->settings->put('mail.password', $this->mail->encryptPassword($data['password']));
        }

        if ($request->boolean('clear_password')) {
            $this->settings->put('mail.password', '');
        }

        $this->settings->flush();

        return to_route('admin.mail.edit')->with('status', __('admin.mail_saved'));
    }

    /**
     * Envoi de test : la seule façon de savoir que les identifiants sont bons.
     *
     * L'e-mail part en synchrone, sans file : l'erreur SMTP remonte tout de
     * suite dans le formulaire au lieu de dormir dans un journal.
     */
    public function test(SendTestMailRequest $request): RedirectResponse
    {
        $this->settings->flush();
        $this->mail->apply();
        Mail::forgetMailers();

        $to = $request->validated('email') ?: $this->mail->notificationAddress();

        if (blank($to)) {
            return back()->with('error', __('admin.mail_test_no_address'));
        }

        try {
            Mail::raw(
                __('admin.mail_test_body', ['company' => $this->settings->string('agency.name')]),
                function ($message) use ($to): void {
                    $message->to($to)->subject(__('admin.mail_test_subject', [
                        'company' => $this->settings->string('agency.name'),
                    ]));
                },
            );
        } catch (Throwable $e) {
            return back()->with('error', __('admin.mail_test_failed', ['message' => $e->getMessage()]));
        }

        return back()->with('status', __('admin.mail_test_sent', ['email' => $to]));
    }
}
