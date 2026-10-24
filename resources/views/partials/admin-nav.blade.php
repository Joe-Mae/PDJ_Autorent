{{--
    Navigation de l'espace de gestion.

    Le même balisage sert la barre latérale et le panneau mobile : une seule
    source, donc aucune entrée qui existe d'un côté et pas de l'autre.

    @var array<int, array{caption: string, links: array<int, array{route: string, active: string, label: string, icon: string}>}> $navGroups
--}}
@foreach ($navGroups as $group)
    @if ($group['links'] !== [])
        <div class="admin-nav-caption">{{ $group['caption'] }}</div>
        <nav class="d-grid gap-1" aria-label="{{ $group['caption'] }}">
            @foreach ($group['links'] as $link)
                <a class="nav-link @if (request()->routeIs($link['active'])) active @endif"
                   href="{{ route($link['route']) }}"
                   title="{{ $link['label'] }}"
                   @if (request()->routeIs($link['active'])) aria-current="page" @endif>
                    <i class="bi bi-{{ $link['icon'] }}"></i><span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif
@endforeach
