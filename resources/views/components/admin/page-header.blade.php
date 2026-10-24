{{--
    En-tête commun des pages de gestion.

    Toutes les pages portaient leur propre `h1` et leurs propres boutons, avec
    des tailles différentes d'un écran à l'autre. Ce composant donne le même
    rythme partout : un sur-titre, un titre, une phrase de contexte, et les
    actions à droite.

    @props(['title', 'eyebrow' => null, 'subtitle' => null])
--}}
@props(['title', 'eyebrow' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'admin-page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4']) }}>
    <div class="admin-page-heading__text">
        @if ($eyebrow)
            <span class="admin-eyebrow">{{ $eyebrow }}</span>
        @endif

        <h1 class="fw-bold mb-1">{{ $title }}</h1>

        @if ($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="admin-page-heading__actions d-flex flex-wrap align-items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
