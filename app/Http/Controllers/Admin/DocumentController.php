<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function show(CustomerDocument $document): View
    {
        return view('admin.documents.show', [
            'document' => $document->load([
                'user', 'booking.vehicle.category', 'booking.documents', 'booking.user', 'driverOnlyBooking',
            ]),
        ]);
    }

    public function review(Request $request, CustomerDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'verdict' => ['required', 'in:conforme,non_conforme,a_relever'],
            'note' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ], [
            'verdict.required' => __('admin.verdict_required'),
        ]);

        // Un refus sans motif n'aide ni le client ni l'agent suivant.
        if ($data['verdict'] === 'non_conforme' && blank($data['note'] ?? null)) {
            return back()->withErrors(['note' => __('admin.reason_required')]);
        }

        $document->update([
            'status' => $data['verdict'],
            'review_note' => $data['note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $booking = $document->booking;

        if ($booking !== null) {
            $this->notifyClient($booking, $document);
        }

        return back()->with('status', __('admin.document_reviewed', [
            'type' => $document->type->label(),
            'verdict' => $document->status->label(),
        ]));
    }

    private function notifyClient(Booking $booking, CustomerDocument $document): void
    {
        // Un refus doit toujours être expliqué au client : c'est lui qui peut
        // corriger, et une reservation sans explication ne se répare pas seule.
        if ($document->status->value === 'non_conforme') {
            app(NotificationService::class)->sendBookingRefused(
                $booking,
                $document->type->label().' : '.$document->review_note,
            );
        }
    }
}
