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
    use App\Enums\BookingStatus;
    use App\Models\Booking;
    use App\Models\CustomerDocument;
    use App\Support\StaffTier;

    $singleAction = $singleAction ?? false;
    $section = $section ?? 'admin';
    $settings = app(\App\Services\SettingsService::class);

    $monPerimetre = StaffTier::highest(auth()->user()?->getRoleNames()) ?? StaffTier::AgentAccueil;

    $adminGroups = [
        [
            'caption' => __('admin.nav_pilotage'),
            'links' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => __('admin.dashboard_title'), 'icon' => 'grid-1x2', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'admin.bookings.index', 'active' => 'admin.bookings.*', 'label' => __('admin.bookings'), 'icon' => 'calendar-check', 'tier' => StaffTier::AgentAccueil],
                // Le retour est un écran du guichet, mais tout le personnel doit
                // pouvoir y entrer sans passer par le tableau de bord.
                ['route' => 'agent.return.index', 'active' => 'agent.return.*', 'label' => __('admin.returns'), 'icon' => 'box-arrow-in-left', 'tier' => StaffTier::AgentAccueil],
            ],
        ],
        [
            'caption' => __('admin.nav_fleet'),
            'links' => [
                ['route' => 'admin.vehicles.index', 'active' => 'admin.vehicles.*', 'label' => __('admin.vehicles'), 'icon' => 'car-front', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'label' => __('admin.categories'), 'icon' => 'tags', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.drivers.index', 'active' => 'admin.drivers.*', 'label' => __('admin.drivers'), 'icon' => 'person-badge', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.driver-only-bookings.index', 'active' => 'admin.driver-only-bookings.*', 'label' => __('admin.driver_only_requests'), 'icon' => 'person-lines-fill', 'tier' => StaffTier::Gestionnaire],
            ],
        ],
        [
            'caption' => __('admin.nav_customers'),
            'links' => [
                ['route' => 'admin.customers.index', 'active' => 'admin.customers.*', 'label' => __('admin.customers'), 'icon' => 'people', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'admin.documents.index', 'active' => 'admin.documents.*', 'label' => __('admin.documents'), 'icon' => 'file-earmark-check', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'label' => __('admin.cash'), 'icon' => 'cash-stack', 'tier' => StaffTier::AgentAccueil],
            ],
        ],
        [
            'caption' => __('admin.nav_insights'),
            'links' => [
                ['route' => 'admin.statistics.index', 'active' => 'admin.statistics.*', 'label' => __('admin.statistics'), 'icon' => 'bar-chart-line', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.contents.index', 'active' => 'admin.contents.*', 'label' => __('admin.contents'), 'icon' => 'megaphone', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.appearance.edit', 'active' => 'admin.appearance.*', 'label' => __('admin.site_appearance'), 'icon' => 'palette', 'tier' => StaffTier::Gestionnaire],
                ['route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'label' => __('admin.settings'), 'icon' => 'gear', 'tier' => StaffTier::SuperAdmin],
            ],
        ],
    ];

    $agentGroups = [
        [
            'caption' => __('admin.nav_counter'),
            'links' => [
                ['route' => 'agent.pickup.index', 'active' => 'agent.pickup.*', 'label' => __('admin.pickup'), 'icon' => 'box-arrow-right', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'agent.return.index', 'active' => 'agent.return.*', 'label' => __('admin.returns'), 'icon' => 'box-arrow-in-left', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'agent.cash.index', 'active' => 'agent.cash.*', 'label' => __('admin.cash'), 'icon' => 'cash-stack', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => __('admin.dashboard_title'), 'icon' => 'grid-1x2', 'tier' => StaffTier::AgentAccueil],
            ],
        ],
        [
            'caption' => __('admin.nav_tools'),
            'links' => [
                ['route' => 'admin.documents.index', 'active' => 'admin.documents.*', 'label' => __('admin.documents'), 'icon' => 'file-earmark-check', 'tier' => StaffTier::AgentAccueil],
                ['route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'label' => __('admin.cash'), 'icon' => 'cash-stack', 'tier' => StaffTier::AgentAccueil],
            ],
        ],
    ];

    // Un groupe dont tous les liens sont hors périmètre ne s'affiche pas : un
    // titre de section vide laisse croire à un menu cassé.
    $navGroups = collect($section === 'agent' ? $agentGroups : $adminGroups)
        ->map(fn (array $group): array => [
            'caption' => $group['caption'],
            'links' => array_values(array_filter(
                $group['links'],
                fn (array $link): bool => $monPerimetre->isAtLeast($link['tier'])
            )),
        ])
        ->filter(fn (array $group): bool => $group['links'] !== [])
        ->values()
        ->all();

    // Les alertes de la barre haute sont des compteurs, pas un flux : elles
    // répondent à « qu'est-ce qui m'attend maintenant ? ».
    $alerts = ['pending' => 0, 'documents' => 0, 'overdue' => 0, 'no_show' => 0];
    $noShowAlertHours = $settings->int('booking.no_show_alert_hours');
    $lateBookings = collect();

    if (! $singleAction) {
        // Clients attendus au retrait il y a plus de deux heures : la fenêtre
        // est ouverte pour les joindre avant l'annulation automatique à 5 h.
        $lateBookings = Booking::query()
            ->awaitingPickup($noShowAlertHours)
            ->with(['vehicle', 'user'])
            ->orderBy('starts_at')
            ->get();

        $alerts = [
            'pending' => Booking::query()->where('status', BookingStatus::EnAttente->value)->count(),
            'documents' => CustomerDocument::query()->pending()->count(),
            'overdue' => Booking::query()
                ->where('status', BookingStatus::EnCours->value)
                ->where('ends_at', '<', now())
                ->count(),
            'no_show' => $lateBookings->count(),
        ];
    }

    $alertTotal = array_sum($alerts);
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.PDJ = { requiredMessage: @json(__('general.required_field')) };</script>
    <title>@yield('title', 'Espace équipe') — {{ $settings->string('agency.name') }}</title>
    <link rel="icon" href="data:,">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="structured-pages" style="border-top: 0;">

<div id="adminApp"
     class="d-flex {{ $singleAction ? '' : 'admin-layout admin-shell' }}"
     style="min-height: 100vh;">

    @unless ($singleAction)
        <aside class="admin-sidebar flex-shrink-0 p-3 d-none d-lg-flex" id="adminSidebar">
            <a class="admin-brand text-decoration-none" href="{{ route('admin.dashboard') }}">
                <x-brand-logo class="admin-brand__mark" />
                <span class="admin-brand__text"><strong>{{ $settings->string('site.brand_label') }}</strong><small>{{ __('admin.management_space') }}</small></span>
            </a>

            <div class="admin-nav">
                @include('partials.admin-nav', ['navGroups' => $navGroups])
            </div>

            <div class="admin-sidebar__footer mt-auto">
                <a class="nav-link" href="{{ route('home', ['locale' => 'fr']) }}" target="_blank" rel="noopener" title="{{ __('admin.view_public_site') }}">
                    <i class="bi bi-box-arrow-up-right"></i><span>{{ __('admin.view_public_site') }}</span>
                </a>
                <form action="{{ route('logout', ['locale' => 'fr']) }}" method="POST">
                    @csrf
                    <button class="nav-link border-0 bg-transparent w-100 text-start" type="submit" title="{{ __('admin.logout') }}">
                        <i class="bi bi-box-arrow-left"></i><span>{{ __('admin.logout') }}</span>
                    </button>
                </form>
            </div>
        </aside>
    @endunless

    <div class="flex-grow-1" style="min-width: 0;">

        @unless ($singleAction)
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-2 admin-topbar__start">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#panelNav" aria-label="{{ __('admin.open_menu') }}">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="admin-icon-btn d-none d-lg-inline-flex" type="button"
                            data-admin-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true"
                            data-collapse-label="{{ __('admin.collapse_menu') }}"
                            data-expand-label="{{ __('admin.expand_menu') }}"
                            aria-label="{{ __('admin.collapse_menu') }}" title="{{ __('admin.collapse_menu') }}">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div class="admin-topbar__context">
                        <span class="admin-topbar__eyebrow">{{ $section === 'agent' ? __('admin.counter_space') : __('admin.management_space') }}</span>
                        <div class="small text-muted">@yield('breadcrumb', __('admin.dashboard_title'))</div>
                    </div>
                </div>

                <form class="admin-search d-none d-lg-flex" method="GET" action="{{ route('admin.bookings.index') }}" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="admin-search-input">{{ __('admin.search_booking') }}</label>
                    <input id="admin-search-input" type="search" name="q"
                           value="{{ request()->routeIs('admin.bookings.index') ? request('q') : '' }}"
                           placeholder="{{ __('admin.search_placeholder') }}" autocomplete="off">
                </form>

                <div class="admin-topbar__actions">
                    <div class="dropdown">
                        <button class="admin-icon-btn" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="{{ __('admin.notifications') }}" title="{{ __('admin.notifications') }}">
                            <i class="bi bi-bell"></i>
                            @if ($alertTotal > 0)
                                <span class="admin-icon-btn__badge">{{ $alertTotal > 9 ? '9+' : $alertTotal }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end admin-notifications">
                            <div class="admin-notifications__head">{{ __('admin.notifications') }}</div>

                            @if ($alertTotal === 0)
                                <div class="admin-notifications__empty">
                                    <i class="bi bi-check2-circle"></i>
                                    <span>{{ __('admin.no_notifications') }}</span>
                                </div>
                            @else
                                @foreach ([
                                    ['count' => $alerts['pending'], 'label' => __('admin.pending_bookings'), 'url' => route('admin.bookings.index', ['statut' => BookingStatus::EnAttente->value]), 'icon' => 'hourglass-split', 'tone' => 'warning'],
                                    ['count' => $alerts['no_show'], 'label' => __('admin.no_show_bell'), 'url' => route('admin.bookings.index', ['statut' => BookingStatus::Confirmee->value]), 'icon' => 'person-dash', 'tone' => 'warning'],
                                    ['count' => $alerts['documents'], 'label' => __('admin.documents_to_check'), 'url' => route('admin.documents.index'), 'icon' => 'file-earmark-check', 'tone' => 'info'],
                                    ['count' => $alerts['overdue'], 'label' => __('admin.overdue_rentals'), 'url' => route('admin.bookings.index', ['statut' => BookingStatus::EnCours->value]), 'icon' => 'exclamation-triangle', 'tone' => 'danger'],
                                ] as $alert)
                                    @if ($alert['count'] > 0)
                                        <a class="admin-notifications__item" href="{{ $alert['url'] }}">
                                            <span class="admin-notifications__icon text-{{ $alert['tone'] }}">
                                                <i class="bi bi-{{ $alert['icon'] }}"></i>
                                            </span>
                                            <span class="admin-notifications__label">{{ $alert['label'] }}</span>
                                            <strong>{{ $alert['count'] }}</strong>
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="admin-user border-0 bg-transparent p-0" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="{{ __('admin.account_menu') }}">
                            <span class="admin-user__avatar">{{ auth()->user()?->initials() }}</span>
                            <span class="d-none d-md-block text-start">
                                <strong>{{ auth()->user()?->displayName() }}</strong>
                                <small>{{ $monPerimetre->label() }}</small>
                            </span>
                            <i class="bi bi-chevron-down d-none d-md-block admin-user__caret"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end admin-user-menu">
                            <li class="px-3 py-2">
                                <div class="fw-semibold">{{ auth()->user()?->displayName() }}</div>
                                <div class="small text-muted">{{ $monPerimetre->label() }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('account.profile', ['locale' => 'fr']) }}">
                                    <i class="bi bi-person me-2"></i>{{ __('account.my_profile') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('home', ['locale' => 'fr']) }}" target="_blank" rel="noopener">
                                    <i class="bi bi-box-arrow-up-right me-2"></i>{{ __('admin.view_public_site') }}
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout', ['locale' => 'fr']) }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-box-arrow-left me-2"></i>{{ __('admin.logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
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

<div class="offcanvas offcanvas-start {{ $section === 'admin' ? 'admin-mobile-nav' : '' }}" tabindex="-1" id="panelNav">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">{{ __('admin.management_space') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('admin.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <div class="admin-nav">
            @include('partials.admin-nav', ['navGroups' => $navGroups])
        </div>

        <hr>
        <a class="nav-link py-2" href="{{ route('account.profile', ['locale' => 'fr']) }}">
            <i class="bi bi-person-circle"></i><span>{{ __('account.my_profile') }}</span>
        </a>
        <a class="nav-link py-2" href="{{ route('home', ['locale' => 'fr']) }}" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i><span>{{ __('admin.view_public_site') }}</span>
        </a>
        <form action="{{ route('logout', ['locale' => 'fr']) }}" method="POST">
            @csrf
            <button class="nav-link py-2 border-0 bg-transparent w-100 text-start" type="submit">
                <i class="bi bi-box-arrow-left"></i><span>{{ __('admin.logout') }}</span>
            </button>
        </form>
    </div>
</div>

@if ($lateBookings->isNotEmpty())
    {{-- Pop-up d'absence : deux heures après l'heure de retrait convenue, les
         agents doivent pouvoir joindre le client. Sans action de leur part,
         la réservation s'annule seule à l'échéance de grâce (5 h par défaut). --}}
    <button type="button" id="noShowTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#noShowModal"
            aria-hidden="true" tabindex="-1"></button>

    <div class="modal fade" id="noShowModal" tabindex="-1" aria-labelledby="noShowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="noShowModalLabel">
                        <i class="bi bi-person-dash me-2 text-warning"></i>{{ __('admin.no_show_title') }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">{{ __('admin.no_show_intro', ['hours' => $noShowAlertHours]) }}</p>

                    @foreach ($lateBookings as $late)
                        @php
                            $clientPhone = $late->user?->phone ?? $late->customer_phone;
                            $clientName = $late->user?->displayName() ?? $late->customer_name ?? $late->reference;
                        @endphp

                        <div class="pdj-card mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $clientName }}</div>
                                    <div class="small text-muted">
                                        {{ $late->reference }} · {{ $late->vehicle?->displayName() }}
                                    </div>
                                </div>
                                <div class="text-end small">
                                    <div class="fw-semibold">{{ $late->effectivePickupAt()->translatedFormat('D d M, H:i') }}</div>
                                    <div class="text-muted">{{ __('admin.no_show_agreed_pickup') }}</div>
                                </div>
                            </div>

                            @if ($clientPhone)
                                <a class="btn btn-sm btn-outline-primary mb-2" href="tel:{{ preg_replace('/\D/', '', $clientPhone) }}">
                                    <i class="bi bi-telephone me-1"></i>{{ $clientPhone }}
                                </a>
                            @endif

                            @if ($late->hasExtendedPickup())
                                <div class="small text-muted mb-2">
                                    {{ __('admin.no_show_original_pickup', [
                                        'heure' => $late->starts_at->translatedFormat('D d M, H:i'),
                                    ]) }}
                                </div>
                            @endif

                            <div class="small mb-3">
                                <i class="bi bi-hourglass-split me-1"></i>
                                {{ __('admin.no_show_deadline', [
                                    'heure' => $late->grace_expires_at?->translatedFormat('H:i') ?? '—',
                                    'dans' => $late->grace_expires_at?->diffForHumans() ?? '—',
                                ]) }}
                            </div>

                            <x-admin.pickup-actions :booking="$late" />
                        </div>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        {{ __('admin.no_show_later') }}
                    </button>
                    <a class="btn btn-primary" href="{{ route('admin.bookings.index', ['statut' => BookingStatus::Confirmee->value]) }}">
                        {{ __('admin.no_show_see_all') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Le pop-up revient à chaque connexion, mais pas à chaque page tant
            // que le même lot de clients est concerné : l'agent peut fermer la
            // fenêtre et continuer son travail sans être relancé en boucle.
            document.addEventListener('DOMContentLoaded', () => {
                const trigger = document.getElementById('noShowTrigger');
                const signature = @json($lateBookings->pluck('reference')->sort()->values());
                const key = 'pdj.noShowDismissed';
                let dismissed = [];

                try {
                    dismissed = JSON.parse(sessionStorage.getItem(key) || '[]');
                } catch (error) {
                    dismissed = [];
                }

                if (signature.every((reference) => dismissed.includes(reference))) {
                    return;
                }

                document.getElementById('noShowModal')
                    ?.addEventListener('hidden.bs.modal', () => {
                        try {
                            sessionStorage.setItem(key, JSON.stringify(signature));
                        } catch (error) {
                            // Navigation privée : le pop-up se contente de revenir.
                        }
                    });

                trigger?.click();
            });
        </script>
    @endpush
@endif

@stack('scripts')
</body>
</html>