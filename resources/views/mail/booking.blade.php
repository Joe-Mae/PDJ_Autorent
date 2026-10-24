<x-mail::message>
# {{ __('notifications.mail.title', ['company' => $settings->string('agency.name')]) }}

{{ $intro }}

<x-mail::panel>
    <table style="width:100%;border-collapse:collapse;">
        @foreach ($lines as $label => $value)
            <tr>
                <td style="padding:4px 0;color:#555;">{{ $label }}</td>
                <td style="padding:4px 0;text-align:right;font-weight:600;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
</x-mail::panel>

@if ($booking->with_driver)
{{ __('notifications.mail.no_license') }}
@endif

{{-- Lien de suivi : la seule action qu'on demande au client par e-mail. --}}
<x-mail::button :url="$link">
{{ __('notifications.mail.cta') }}
</x-mail::button>

{{ $footer }}

</x-mail::message>
