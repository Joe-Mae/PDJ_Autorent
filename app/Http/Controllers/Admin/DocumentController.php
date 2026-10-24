<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewDocumentRequest;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * File de vérification des documents.
 *
 * C'est l'écran que l'agent voit le plus souvent, et celui où une erreur coûte
 * cher : un document validé à la légère laisse passer un permis expiré. D'où
 * trois verdicts seulement, dont un « je regarde plus tard » explicite, et un
 * accès direct à l'image en grand.
 *
 * Tout accès au fichier lui-même passe par show() — c'est là que naissent les
 * URL signées — donc c'est là que le journal est écrit : qui a ouvert la pièce,
 * quand, depuis quelle adresse.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.documents.index', [
            'documents' => CustomerDocument::query()
                ->pending()
                ->with(['user', 'booking.vehicle', 'driverOnlyBooking'])
                ->orderByRaw('COALESCE(
                    (SELECT starts_at FROM bookings WHERE bookings.id = documents.booking_id),
                    (SELECT starts_at FROM driver_only_bookings WHERE driver_only_bookings.id = documents.driver_only_booking_id),
                    documents.created_at
                ) ASC')
                ->paginate(20),
            'stats' => [
                'pending' => CustomerDocument::query()->pending()->count(),
                'today' => CustomerDocument::query()->whereDate('reviewed_at', today())->count(),
            ],
        ]);
    }

    public function show(Request $request, CustomerDocument $document): View
    {
        $this->audit($request, 'document.accessed', $document, [
            'type' => $document->type->value,
            'reservation' => $document->booking?->reference,
        ]);

        return view('admin.documents.show', [
            'document' => $document->load([
                'user', 'booking.vehicle.category', 'booking.documents', 'booking.user', 'driverOnlyBooking',
            ]),
            // L'accès courant est écrit juste au-dessus : on le remonte avec les
            // précédents pour que l'agent voie la ligne qu'il vient de créer.
            'history' => AuditLog::query()
                ->about($document)
                ->whereIn('event', ['document.accessed', 'document.reviewed'])
                ->with('actor')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function review(ReviewDocumentRequest $request, CustomerDocument $document): RedirectResponse
    {
        $data = $request->validated();

        // Un refus sans motif n'aide ni le client ni l'agent suivant.
        if ($data['verdict'] === 'non_conforme' && blank($data['note'] ?? null)) {
            return back()->withErrors(['note' => __('admin.reason_required')]);
        }

        $previous = $document->status;

        $document->update([
            'status' => $data['verdict'],
            'review_note' => $data['note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->audit($request, 'document.reviewed', $document, [
            'type' => $document->type->value,
            'reservation' => $document->booking?->reference,
        ], ['status' => $previous->value], ['status' => $document->status->value]);

        $booking = $document->booking;

        if ($booking !== null) {
            $this->notifyClient($booking, $document);
        }

        return back()->with('status', __('admin.document_reviewed', [
            'type' => $document->type->label(),
            'verdict' => $document->status->label(),
        ]));
    }

    /**
     * Écrit une ligne au journal. Une pièce d'identité se consulte pour un
     * motif précis : sans trace de qui l'a ouverte, on ne peut ni répondre à
     * un client qui demande qui a vu ses documents, ni repérer une ouverture
     * sans raison.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function audit(Request $request, string $event, CustomerDocument $document, array $context, array $old = [], array $new = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => CustomerDocument::class,
            'auditable_id' => $document->getKey(),
            'description' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);
    }

    private function notifyClient(Booking $booking, CustomerDocument $document): void
    {
        // Un refus doit toujours être expliqué au client : c'est lui qui peut
        // corriger, et une reservation sans explication ne se répare pas seule.
        if ($document->status->value === 'non_conforme') {
            app(NotificationService::class)->sendBookingRefused(
                $booking,
                $document->type->label().' : '.$document->review_note,
                $booking->customer_email ?? $booking->user?->email,
            );
        }
    }
}
