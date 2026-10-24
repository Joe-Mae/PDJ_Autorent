@php
    $settings = app(\App\Services\SettingsService::class);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification du numéro — {{ $settings->string('agency.name') }}</title>
    <link rel="icon" href="data:,">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>

<div class="container py-5" style="max-width: 460px;">
    <div class="text-center mb-4">
        <span class="pdj-logo"><x-brand-logo /> {{ $settings->string('site.brand_label') }}</span>
        <h1 class="h5 fw-bold mt-4 mb-1">Vérifiez votre numéro</h1>
        <p class="text-muted">Un code à 6 chiffres a été envoyé au {{ $phone }}.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small">{{ $errors->first() }}</div>
    @endif

    <div class="pdj-card">
        <div class="code-input mb-4">
            @for ($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" data-code-input
                       aria-label="Chiffre {{ $i + 1 }}">
            @endfor
        </div>

        <form method="POST" action="{{ route('phone.verify') }}">
            @csrf
            <input type="hidden" name="code" data-code-hidden>
            <button class="btn btn-primary btn-lg w-100">Vérifier</button>
        </form>
    </div>
</div>

</body>
</html>
