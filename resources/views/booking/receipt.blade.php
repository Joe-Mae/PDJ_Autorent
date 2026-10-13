@php
    /**
     * Bon de réservation imprimable, en style ticket.
     *
     * dompdf ne comprend pas le CSS moderne : on ne compte que sur les
     * attributes inline, la police par défaut et des bordures en tirets.
     */
    $money = fn (int $amount) => number_format($amount, 0, ' ', ' ').' F CFA';
    $line = 'border-bottom:1px dashed #999; padding:6px 0; font-size:11px;';
    $label = 'color:#555;';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de réservation {{ $booking->reference }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #14201b; }
        h1 { font-size: 16px; margin: 0; }
        .mono { font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #555; }
        .dash { border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; font-size: 11px; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td>
                <h1>PDJ AUTORENT</h1>
                <div class="muted">{{ $settings->string('agency.address') }}</div>
                <div class="muted">{{ $settings->formattedPhone() }}</div>
            </td>
            <td class="right">
                <div style="font-size:13px;font-weight:bold;">BON DE RÉSERVATION</div>
                <div class="mono" style="font-size:15px;font-weight:bold;">{{ $booking->reference }}</div>
            </td>
        </tr>
    </table>

    <div class="dash"></div>

    <div style="font-size:14px;font-weight:bold;">{{ $booking->vehicle->fullName() }}</div>
    <table>
        <tr>
            <td style="{{ $label }}">Départ</td>
            <td class="right">{{ $booking->starts_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Retour</td>
            <td class="right">{{ $booking->ends_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Durée</td>
            <td class="right">{{ $booking->days }} jour(s)</td>
        </tr>
    </table>

    <div class="dash"></div>

    <table>
        <tr>
            <td style="{{ $label }}">Location ({{ $booking->days }} j)</td>
            <td class="right">{{ $money($booking->price_rental) }}</td>
        </tr>
        @if ($booking->with_driver)
            <tr>
                <td style="{{ $label }}">
                    @if ($booking->driver_daily_price !== null)
                        Chauffeur ({{ $booking->days }} j × {{ number_format($booking->driver_daily_price, 0, ' ', ' ') }} F CFA/j)
                    @else
                        Chauffeur ({{ $booking->driver_blocks }} blocs de 4 h)
                    @endif
                </td>
                <td class="right">{{ $money($booking->price_driver) }}</td>
            </tr>
        @endif
    </table>

    <table style="margin-top:8px;">
        <tr>
            <td style="font-size:13px;font-weight:bold;">À PAYER À L'AGENCE</td>
            <td class="right" style="font-size:15px;font-weight:bold;">{{ $money($booking->price_total) }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Caution</td>
            <td class="right">{{ $money($booking->deposit_amount) }}</td>
        </tr>
    </table>

    <div style="border:1px solid #14201b;padding:8px;margin-top:8px;">
        <div style="font-size:11px;font-weight:bold;">À AVOIR SUR SOI : {{ $money($booking->amountToCarry()) }}</div>
    </div>

    <div class="dash"></div>

    <table>
        <tr>
            <td style="{{ $label }}">Client</td>
            <td class="right">{{ $booking->user?->displayName() ?? '—' }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Téléphone</td>
            <td class="right">{{ $booking->user?->phone ?? '—' }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Statut</td>
            <td class="right">{{ $booking->status->label() }}</td>
        </tr>
    </table>

    <div class="dash"></div>

    <div style="font-size:11px;font-weight:bold;">À PRÉSENTER AU RETRAIT</div>
    <ul style="margin:4px 0 0 16px;padding:0;font-size:11px;">
        <li>Pièce d'identité originale</li>
        @unless ($booking->with_driver)
            <li>Permis de conduire original</li>
        @endunless
        <li>{{ $money($booking->amountToCarry()) }} (location + caution)</li>
    </ul>

    <div class="dash"></div>

    <p class="muted" style="font-size:10px;margin:0;">
        Assurance incluse (RC, vol, incendie, bris de vitres), franchise 150 000 F CFA.
        Kilométrage illimité. Carburant à la charge du client, même niveau au retour.
        Vitesse maximale 120 km/h.
    </p>
    <p class="muted" style="font-size:10px;margin:4px 0 0;">
        Ce bon n'est pas un reçu de paiement : rien n'a été encaissé. Le règlement
        se fait à l'agence, au moment du retrait.
    </p>

    <div class="center" style="margin-top:14px;">
        {!! \SimpleSoftwareInQrCode\QrCode::encode($booking->reference) !!}
    </div>

</body>
</html>
