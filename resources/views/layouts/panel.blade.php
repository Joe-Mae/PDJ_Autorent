@php
    /**
     * Layout de l'administration et des écrans agent.
     *
     * Deux usages distincts partagent ce gabarit : l'admin (dense, en colonne,
     * pour travailler) et l'agent (une seule action par écran, gros boutons,
     * pour un client debout en face). C'est pourquoi $singleAction est prévu :
     * quand il est vrai, la barre latérale disparaît et l'écran ne montre que
     * l'action du moment.
     *
     * Chaque entrée du menu porte le périmètre qui la protège. Le menu est
     * filtré sur le rôle de la personne connectée : proposer un lien qui mène
     * à une erreur 403 n'est pas un détail d'ergonomie : c'est laisser croire à
     * l'agent qu'il a le droit de modifier le parc alors que ce n'est pas vrai.
     *
     * @var bool $singleAction
     * @var string $section
     */
    use App\Support\StaffTier;

    $singleAction = $singleAction ?? false;
    $section = $section ?? 'admin';

    $monPerimetre = StaffTier::highest(auth()->user()?->getRoleNames()) ?? StaffTier::AgentAccueil;

    $agentLinks = [
        ['route' => 'agent.pickup.index', 'label' => 'Retraits', 'icon' => 'box-arrow-right', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'agent.return.index', 'label' => 'Retours', 'icon' => 'box-arrow-in-left', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'agent.cash.index', 'label' => 'Caisse', 'icon' => 'cash-stack', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'speedometer2', 'tier' => StaffTier::AgentAccueil],
    ];

    $adminLinks = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'speedometer2', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.bookings.index', 'label' => 'Réservations', 'icon' => 'calendar-check', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.documents.index', 'label' => 'Documents', 'icon' => 'file-earmark-check', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'agent.pickup.index', 'label' => 'Retraits', 'icon' => 'box-arrow-right', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'agent.return.index', 'label' => 'Retours', 'icon' => 'box-arrow-in-left', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.payments.index', 'label' => 'Caisse', 'icon' => 'cash-stack', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.customers.index', 'label' => 'Clients', 'icon' => 'people', 'tier' => StaffTier::AgentAccueil],
        ['route' => 'admin.vehicles.index', 'label' => 'Véhicules', 'icon' => 'car-front', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.categories.index', 'label' => 'Catégories & tarifs', 'icon' => 'tags', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.calendar', 'label' => 'Planning', 'icon' => 'calendar3', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.drivers.index', 'label' => 'Chauffeurs', 'icon' => 'person-badge', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.driver-only-bookings.index', 'label' => 'Demandes chauffeur seul', 'icon' => 'person-lines-fill', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.contents.index', 'label' => 'Contenus', 'icon' => 'megaphone', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.appearance.edit', 'label' => 'Apparence du site', 'icon' => 'palette', 'tier' => StaffTier::Gestionnaire],
        ['route' => 'admin.settings.index', 'label' => 'Paramètres', 'icon' => 'gear', 'tier' => StaffTier::SuperAdmin],
    ];

    $toutes = $section === 'agent' ? $agentLinks : $adminLinks;

    // Le menu ne montre que ce que ce rôle peut réellement ouvrir.
    $links = array_values(array_filter(
        $toutes,
        fn (array $lien): bool => $monPerimetre->isAtLeast($lien['tier'])
    ));
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.PDJ = { requiredMessage: @json(__('general.required_field')) };</script>
    <title>@yield('title', 'Espace équipe') — PDJ Autorent</title>
    <link rel="icon" href="data:,">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="structured-pages" style="border-top: 0;">

<div class="d-flex" style="min-height: 100vh;">

    @unless ($singleAction)
        <aside class="admin-sidebar flex-shrink-0 p-3 d-none d-lg-block" style="width: 250px;">
            <a class="pdj-logo text-white mb-4 d-inline-flex" href="{{ url('/admin') }}">
                <span class="pdj-logo-mark">PDJ</span>
                <span>Équipe</span>
            </a>

            <nav class="d-grid gap-1">
                @foreach ($links as $link)
                    <a class="nav-link @if (request()->routeIs($link['route'])) active @endif"
                       href="{{ route($link['route']) }}">
                        <i class="bi bi-{{ $link['icon'] }}"></i>{{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <hr class="my-3" style="border-color: rgba(255,255,255,.12);">

            <a class="nav-link" href="{{ route('home', ['locale' => 'fr']) }}" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>Voir le site
            </a>
            <form action="{{ route('logout', ['locale' => 'fr']) }}" method="POST">
                @csrf
                <button class="nav-link border-0 bg-transparent w-100 text-start" type="submit">
                    <i class="bi bi-box-arrow-left"></i>Déconnexion
                </button>
            </form>
        </aside>
    @endunless

    <div class="flex-grow-1" style="min-width: 0;">

        @unless ($singleAction)
            <header class="bg-white border-bottom px-3 py-2 d-flex align-items-center justify-content-between">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#panelNav" aria-label="Menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="small text-muted">@yield('breadcrumb', 'Espace équipe')</div>
                <div class="small text-muted d-flex align-items-center gap-3">
                    <span>{{ now()->translatedFormat('l d F Y') }}</span>
                    <span class="badge rounded-pill text-bg-light border">
                        {{ $monPerimetre->label() }}
                    </span>
                </div>
            </header>
        @endunless

        <main class="panel-content p-3 p-lg-4">
            @if (session('status'))
                <div class="alert alert-success d-flex gap-2 align-items-center">
                    <i class="bi bi-check-circle-fill"></i><span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex gap-2 align-items-center">
                    <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- `$errors` est posé par ShareErrorsFromSession, un middleware du
                 groupe web. Une page rendue avant ce middleware — l'écran
                 « envoi trop lourd » — n'a pas la variable : le layout ne doit
                 pas planter parce qu'elle manque. --}}
            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="panelNav">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">PDJ Autorent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="d-grid gap-1">
            @foreach ($links as $link)
                <a class="nav-link py-2 @if (request()->routeIs($link['route'])) active @endif"
                   href="{{ route($link['route']) }}">
                    <i class="bi bi-{{ $link['icon'] }} me-2"></i>{{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>

@stack('scripts')
</body>
</html>
