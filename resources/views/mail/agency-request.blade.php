<x-mail::message>
# {{ __('notifications.mail.agency_title', ['reference' => $reference]) }}

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

<x-mail::button :url="$url">
{{ __('notifications.mail.agency_cta') }}
</x-mail::button>

{{ __('notifications.footers.agency_internal', ['company' => $settings->string('agency.name')]) }}

</x-mail::message>
