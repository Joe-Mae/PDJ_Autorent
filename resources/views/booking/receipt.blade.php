@php
    /**
     * Bon de réservation imprimable, inspiré du modèle AutoLoc, adapté à PDJ Autorent.
     *
     * dompdf ne comprend pas le CSS moderne : on ne compte que sur les
     * attributes inline, la police par défaut et des bordures en tirets.
     */
    $money = fn (int $amount) => $settings->money($amount);
    $line = 'border-bottom:1px dashed #999; padding:6px 0; font-size:11px;';
    $label = 'color:#555;';
    $agencyName = $settings->string('agency.name');
    $agencyAddress = $settings->string('agency.address');
    $agencyPhone = $settings->formattedPhone();
    $agencyEmail = $settings->string('contact.email');
    $agencyWhatsapp = $settings->whatsappNumber();
    $clientName = $booking->user?->displayName() ?? $booking->customer_name ?? '—';
    $clientPhone = $booking->user?->phone ?? $booking->customer_phone ?? '—';
    $clientEmail = $booking->user?->email ?? $booking->customer_email ?? '—';
    $serviceType = $booking->with_driver ? 'Voiture + chauffeur' : 'Voiture seule';
    $pickupLocation = $agencyAddress;
    $returnLocation = $agencyAddress;
    $amountPaid = $booking->rentalPaid();
    $remaining = max(0, (int) $booking->price_total - $amountPaid);
    $paymentMethod = $booking->payments->first()?->method?->label() ?? '—';
    $paymentStatus = $booking->isFullyPaid() ? 'Payé' : 'En attente';
    $subtotal = (int) $booking->price_rental + (int) $booking->price_driver;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de réservation {{ $booking->reference }}</title>
    <style>
        @page { margin: 6mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #14201b; }
        h1 { font-size: 13px; margin: 0; }
        h2 { font-size: 10px; margin: 0 0 2px; }
        .mono { font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #555; }
        .dash { border-top: 1px dashed #999; margin: 4px 0; }
        .bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; font-size: 8.5px; vertical-align: top; }
        .section { margin-top: 4px; }
        .section-title { font-size: 9.5px; font-weight: bold; margin-bottom: 2px; border-bottom: 1px solid #14201b; padding-bottom: 1px; }
        .info-row { display: flex; justify-content: space-between; padding: 1px 0; }
    </style>
</head>
<body>

    {{-- ── En-tête ─────────────────────────────────────────────────────────── --}}
    <table>
        <tr>
            <td style="width:60%;">
                <h1>{{ strtoupper($agencyName) }}</h1>
                <div class="muted">{{ $agencyAddress }}</div>
                <div class="muted">{{ $agencyPhone }}</div>
                <div class="muted">{{ $agencyEmail }}</div>
                <div class="muted" style="margin-top:4px;">Location de voitures</div>
                <div class="muted" style="font-style:italic;">Votre mobilité, notre priorité</div>
                <div class="muted">Lomé, Togo</div>
            </td>
            <td class="right" style="width:40%;">
                <div style="font-size:13px;font-weight:bold;">BON DE RÉSERVATION</div>
                <div class="mono" style="font-size:15px;font-weight:bold;margin-top:4px;">{{ $booking->reference }}</div>
                <div class="muted" style="margin-top:8px;font-size:9px;">Scanner pour vérifier</div>
                <div class="center" style="margin-top:4px;">
                    @php
                        $qrRenderer = new \BaconQrCode\Renderer\ImageRenderer(
                            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(55),
                            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                        );
                        $qrSvg = (new \BaconQrCode\Writer($qrRenderer))->writeString($booking->reference);
                    @endphp
                    {!! $qrSvg !!}
                </div>
            </td>
        </tr>
    </table>

    <div class="dash"></div>

    <div class="center" style="font-size:9px;font-weight:bold;margin:4px 0;">
        Merci de votre confiance ! Présentez ce bon au retrait.
    </div>

    {{-- La pièce à apporter dépend de la prestation : annoncer le permis à un
         client avec chauffeur le ferait courir pour rien, et l'oublier à un
         client sans chauffeur se paie au guichet. --}}
    <div class="center" style="font-size:8.5px;font-weight:bold;margin:2px 0 6px;padding:4px;border:1px solid #14201b;background:#f3f5f4;">
        {{ $booking->with_driver ? __('booking.receipt.driver_notice') : __('booking.receipt.license_notice') }}
    </div>

    <table>
        <tr>
            <td style="{{ $label }}">Date de réservation</td>
            <td class="right">{{ $booking->created_at->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>

    <div class="dash"></div>

    {{-- ── Informations client ─────────────────────────────────────────────── --}}
    <div class="section-title">Informations client</div>
    <table>
        <tr>
            <td style="{{ $label }}">Nom</td>
            <td class="right">{{ $clientName }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Téléphone</td>
            <td class="right">{{ $clientPhone }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Email</td>
            <td class="right">{{ $clientEmail }}</td>
        </tr>
    </table>

    {{-- ── Détails de la réservation ───────────────────────────────────────── --}}
    <div class="section-title">Détails de la réservation</div>
    <table>
        <tr>
            <td style="{{ $label }}">Type de prestation</td>
            <td class="right">{{ $serviceType }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Date de début</td>
            <td class="right">{{ $booking->starts_at->format('d/m/Y à H:i') }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Date de fin</td>
            <td class="right">{{ $booking->ends_at->format('d/m/Y à H:i') }} ({{ $booking->days }} jour(s))</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Lieu de prise en charge</td>
            <td class="right">{{ $pickupLocation }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Lieu de retour</td>
            <td class="right">{{ $returnLocation }}</td>
        </tr>
    </table>

    {{-- ── Véhicule & chauffeur ────────────────────────────────────────────── --}}
    <div class="section-title">Véhicule</div>
    <table>
        <tr>
            <td style="{{ $label }}">Véhicule</td>
            <td class="right">{{ $booking->vehicle->fullName() }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Catégorie</td>
            <td class="right">{{ $booking->vehicle->category->name('fr') }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Transmission</td>
            <td class="right">{{ $booking->vehicle->transmissionLabel() }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Carburant</td>
            <td class="right">{{ $booking->vehicle->fuelLabel() }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Places</td>
            <td class="right">{{ $booking->vehicle->seats }}</td>
        </tr>
        @if ($booking->vehicle->air_conditioning)
            <tr>
                <td style="{{ $label }}">Climatisation</td>
                <td class="right">Oui</td>
            </tr>
        @endif
    </table>

    @if ($booking->with_driver && $booking->driver)
        <div class="section-title" style="margin-top:8px;">Chauffeur</div>
        <table>
            <tr>
                <td style="{{ $label }}">Chauffeur</td>
                <td class="right">{{ $booking->driver->displayName() }}</td>
            </tr>
            <tr>
                <td style="{{ $label }}">Téléphone</td>
                <td class="right">{{ $booking->driver->phone }}</td>
            </tr>
            @if ($booking->driver->rating)
                <tr>
                    <td style="{{ $label }}">Note</td>
                    <td class="right">{{ $booking->driver->rating }} / 5</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="dash"></div>

    {{-- ── Détail du prix ──────────────────────────────────────────────────── --}}
    <div class="section-title">Détail du prix</div>
    <table>
        <tr>
            <td style="{{ $label }}">Location du véhicule ({{ $booking->days }} jour(s))</td>
            <td class="right">{{ $money($booking->price_rental) }}</td>
        </tr>
        @if ($booking->with_driver && $booking->price_driver > 0)
            <tr>
                <td style="{{ $label }}">Frais du chauffeur ({{ $booking->days }} jour(s))</td>
                <td class="right">{{ $money($booking->price_driver) }}</td>
            </tr>
        @endif
        <tr>
            <td style="{{ $label }}">Sous-total</td>
            <td class="right">{{ $money($subtotal) }}</td>
        </tr>
        <tr>
            <td style="font-size:11px;font-weight:bold;">Montant total</td>
            <td class="right" style="font-size:13px;font-weight:bold;">{{ $money($booking->price_total) }}</td>
        </tr>
        @if ($booking->deposit_amount > 0)
            <tr>
                <td style="{{ $label }}">Caution</td>
                <td class="right">{{ $money($booking->deposit_amount) }}</td>
            </tr>
        @endif
    </table>

    <div class="dash"></div>

    {{-- ── Informations de paiement ────────────────────────────────────────── --}}
    <div class="section-title">Informations de paiement</div>
    <table>
        <tr>
            <td style="{{ $label }}">Statut du paiement</td>
            <td class="right">{{ $paymentStatus }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Mode de paiement</td>
            <td class="right">{{ $paymentMethod }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Montant versé</td>
            <td class="right">{{ $money($amountPaid) }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Reste à payer</td>
            <td class="right" style="font-weight:bold;">{{ $money($remaining) }}</td>
        </tr>
    </table>

    <div class="dash"></div>

    {{-- ── Informations importantes ────────────────────────────────────────── --}}
    <p style="font-size:8px;margin:4px 0 0;" class="muted">
        Valable uniquement pour la date et les horaires indiqués. Annulation : prévenir 24h à l'avance. Modification : {{ $agencyPhone }}. Assurance incluse : RC, vol, incendie, bris de vitres. Franchise : {{ $settings->money($settings->int('booking.insurance_deductible')) }}.
    </p>

    <div class="dash"></div>

    {{-- ── Signature ───────────────────────────────────────────────────────── --}}
    <table>
        <tr>
            <td style="width:50%;text-align:center;padding-top:12px;">
                <div class="muted" style="font-size:9px;">Pour {{ $agencyName }}</div>
                <div style="margin-top:12px;border-top:1px solid #999;padding-top:2px;font-size:8px;" class="muted">
                    Service Réservation
                </div>
            </td>
            <td style="width:50%;text-align:center;padding-top:12px;">
                <div style="font-size:9px;font-weight:bold;">{{ $agencyName }}</div>
                <div class="muted" style="font-size:9px;">Roulez en toute sérénité</div>
                <div class="muted" style="font-size:9px;">{{ $agencyPhone }}</div>
                <div class="muted" style="font-size:9px;">{{ $agencyEmail }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
