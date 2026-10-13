<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Mes documents.
     *
     * On montre d'abord ce qui est réutilisable, parce que c'est ce qui
     * intéresse le client : « est-ce que je dois renvoyer mes papiers ? »
     */
    public function index(Request $request): View
    {
        return view('account.documents', [
            'reusable' => $request->user()->documents()
                ->whereIn('status', ['conforme', 'presente'])
                ->orderByDesc('reviewed_at')
                ->get()
                ->groupBy(fn (CustomerDocument $document) => $document->type),

            'history' => $request->user()->documents()
                ->orderByDesc('created_at')
                ->paginate(15),
        ]);
    }

    /**
     * Téléchargement. Le fichier est sur un disque privé : on sert une URL
     * signée de 15 minutes plutôt qu'un lien permanent.
     */
    public function show(Request $request, string $locale, CustomerDocument $document)
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        return redirect()->away($document->temporaryUrl());
    }
}
