<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
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
    public function __construct(private readonly DocumentStorageService $storage) {}

    public function store(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $this->guardOwnership($request, $booking);
        abort_if($booking->status->isFinal(), 422);

        $type = $this->persist($request, $booking);

        return back()->with('status', __('document.uploaded', ['type' => $type->label()]));
    }

    /** Dépôt pour une demande sans compte : le jeton remplace l'authentification. */
    public function storeGuest(Request $request, string $locale, Booking $booking, string $token): RedirectResponse
    {
        $this->guardGuestToken($booking, $token);
        abort_unless($booking->status->canStillUploadDocuments(), 422);

        $type = $this->persist($request, $booking);

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

    /** Valide, stocke et enregistre la pièce ; commun aux deux parcours. */
    private function persist(Request $request, Booking $booking): DocumentType
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_map(
                fn (DocumentType $type) => $type->value,
                DocumentType::acceptedOnline(),
            ))],
        ]);

        $type = DocumentType::from($data['type']);
        $request->validate([
            'file' => in_array($type, [DocumentType::IdentityCard, DocumentType::Selfie], true)
                ? ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']
                : ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

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

    /**
     * Réservation invitée : le hash stocké et le jeton du lien doivent coïncider.
     * Une réservation rattachée à un compte n'est jamais accessible par ce lien.
     */
    private function guardGuestToken(Booking $booking, string $token): void
    {
        abort_unless(
            $booking->user_id === null
                && $booking->guest_access_token_hash !== null
                && hash_equals($booking->guest_access_token_hash, hash('sha256', $token)),
            404,
        );
    }

    private function guardOwnership(Request $request, Booking $booking): void
    {
        abort_if($booking->guest_access_token_hash !== null, 404);

        $user = $request->user();

        if ($user === null) {
            return;
        }

        abort_unless($booking->user_id === $user->id || $user->isStaff(), 403);
    }
}
