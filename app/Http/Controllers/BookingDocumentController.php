<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\Concerns\GuardsBookingDocuments;
use App\Http\Requests\Site\StoreBookingDocumentRequest;
use App\Http\Requests\Site\StoreGuestDocumentRequest;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Dépôt des pièces du client.
 *
 * Le fichier atterrit sur un disque privé et hors de public/, avec un nom
 * aléatoire : ni le nom d'origine ni le type MIME ne doivent fuiter par l'URL.
 * La lecture passe par une URL signée de 15 minutes.
 *
 * Deux accès : le tunnel authentifié (session ou compte) et la demande invitée,
 * où le jeton du lien fait office de preuve de possession.
 */
class BookingDocumentController extends Controller
{
    use GuardsBookingDocuments;

    public function __construct(private readonly DocumentStorageService $storage) {}

    public function store(StoreBookingDocumentRequest $request, string $locale, Booking $booking): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $type = $this->persist($request->validated(), $file, $booking);

        return back()->with('status', __('document.uploaded', ['type' => $type->label()]));
    }

    /** Dépôt pour une demande sans compte : le jeton remplace l'authentification. */
    public function storeGuest(StoreGuestDocumentRequest $request, string $locale, Booking $booking, string $token): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $type = $this->persist($request->validated(), $file, $booking);

        return back()->with('status', __('document.uploaded', ['type' => $type->label()]));
    }

    public function destroy(Request $request, string $locale, Booking $booking, CustomerDocument $document): RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_unless($document->booking_id === $booking->id, 403);
        abort_unless($document->type->isAcceptedOnline(), 403);
        abort_if($booking->status->isFinal(), 422);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('status', __('document.deleted'));
    }

    public function destroyGuest(Request $request, string $locale, Booking $booking, string $token, CustomerDocument $document): RedirectResponse
    {
        $this->guardGuestToken($booking, $token);
        abort_unless($booking->status->canStillUploadDocuments(), 422);
        abort_unless($document->booking_id === $booking->id, 403);
        abort_unless($document->type->isAcceptedOnline(), 403);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('status', __('document.deleted'));
    }

    /** Stocke et enregistre la pièce ; la validation a eu lieu dans la Form Request.
     *
     * @param  array{type: string}  $data
     */
    private function persist(array $data, UploadedFile $file, Booking $booking): DocumentType
    {
        $type = DocumentType::from($data['type']);

        $stored = $this->storage->store($file, 'reservations/'.$booking->reference, 'file');

        // Un nouveau dépôt annule la vérification d'une pièce déjà validée.
        $booking->documents()
            ->where('type', $type)
            ->where('status', '!=', DocumentStatus::Presente->value)
            ->delete();

        $booking->documents()->create([
            'user_id' => $booking->user_id,
            'type' => $type,
            'status' => DocumentStatus::EnAttente,
            'disk' => $stored->disk,
            'path' => $stored->path,
            'original_filename' => $stored->originalFilename,
            'mime_type' => $stored->mimeType,
            'size_kb' => $stored->sizeKb,
        ]);

        return $type;
    }
}
