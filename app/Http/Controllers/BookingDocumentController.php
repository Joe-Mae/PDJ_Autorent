<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\CustomerDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dépôt des pièces du client.
 *
 * Le fichier atterrit sur un disque privé et hors de public/, avec un nom
 * aléatoire : ni le nom d'origine ni le type MIME ne doivent fuiter par l'URL.
 * La lecture passe par une URL signée de 15 minutes.
 */
class BookingDocumentController extends Controller
{
    public function store(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_column(DocumentType::cases(), 'value'))],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $type = DocumentType::from($data['type']);
        $file = $request->file('file');

        $path = $file->storeAs(
            'reservations/'.$booking->reference,
            Str::random(40).'.'.$file->getClientOriginalExtension(),
            disk: 'documents',
        );

        // Un nouveau dépôt annule la vérification d'une pièce déjà validée.
        $booking->documents()
            ->where('type', $type)
            ->where('status', '!=', DocumentStatus::Presente->value)
            ->delete();

        $booking->documents()->create([
            'user_id' => $booking->user_id,
            'type' => $type,
            'status' => DocumentStatus::EnAttente,
            'disk' => 'documents',
            'path' => $path,
            'original_filename' => Str::limit($file->getClientOriginalName(), 120, ''),
            'mime_type' => $file->getMimeType(),
            'size_kb' => (int) ceil($file->getSize() / 1024),
        ]);

        return back()->with('status', __('document.uploaded', ['type' => $type->label()]));
    }

    public function destroy(Request $request, string $locale, Booking $booking, CustomerDocument $document): RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_unless($document->booking_id === $booking->id, 403);
        abort_if($booking->status->isFinal(), 422);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('status', __('document.deleted'));
    }

    private function guardOwnership(Request $request, Booking $booking): void
    {
        $user = $request->user();

        if ($user === null) {
            return;
        }

        abort_unless($booking->user_id === $user->id || $user->isStaff(), 403);
    }
}
