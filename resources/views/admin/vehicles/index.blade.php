@extends('layouts.panel')

@section('title', __('admin.vehicles'))
@section('breadcrumb', __('admin.vehicles'))

@section('content')
    <x-admin.page-header
        :title="__('admin.vehicles')"
        :subtitle="$view === 'archives'
            ? __('admin.archived_count', ['count' => $vehicles->total()])
            : __('admin.fleet_count', ['count' => $vehicles->total()])">
        <a class="btn btn-primary" href="{{ route('admin.vehicles.create') }}">
            <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_vehicle') }}
        </a>
    </x-admin.page-header>

    <div class="btn-group mb-4" role="group" aria-label="{{ __('admin.fleet_views') }}">
        <a class="btn {{ $view === 'active' ? 'btn-primary' : 'btn-light border' }}"
           href="{{ route('admin.vehicles.index', array_filter(['q' => request('q'), 'statut' => request('statut')])) }}">
            {{ __('admin.fleet_active') }}
            <span class="badge {{ $view === 'active' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary-emphasis' }} ms-1">
                {{ $counts['active'] }}
            </span>
        </a>
        <a class="btn {{ $view === 'archives' ? 'btn-primary' : 'btn-light border' }}"
           href="{{ route('admin.vehicles.index', array_filter(['vue' => 'archives', 'q' => request('q'), 'statut' => request('statut')])) }}">
            {{ __('admin.archived_vehicles') }}
            <span class="badge {{ $view === 'archives' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary-emphasis' }} ms-1">
                {{ $counts['archived'] }}
            </span>
        </a>
    </div>

    <form method="GET" class="pdj-card mb-4">
        <input type="hidden" name="vue" value="{{ $view }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label" for="q">{{ __('admin.search') }}</label>
                <div class="admin-input-icon">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}"
                           placeholder="{{ __('admin.vehicle_search_hint') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="statut">{{ __('admin.status') }}</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 d-flex justify-content-end gap-2">
                <a class="btn btn-light border" href="{{ route('admin.vehicles.index') }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.reset_filters') }}
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('admin.filter') }}
                </button>
            </div>
        </div>
    </form>

    {{-- La sélection est rattachée au formulaire par `form="vehicles-bulk"` :
         imbriquer ce formulaire autour du tableau casserait les formulaires de
         statut ligne par ligne (HTML interdit les formulaires imbriqués). --}}
    <div class="pdj-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAllVehicles" aria-label="{{ __('admin.select_all') }}">
                        </th>
                        <th>{{ __('admin.vehicle') }}</th>
                        <th>{{ __('admin.category') }}</th>
                        <th>{{ __('admin.transmission') }}</th>
                        <th class="text-end">{{ __('admin.daily_price') }}</th>
                        <th>{{ __('admin.availability') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        @php
                            $busyNow = $vehicle->current_bookings_count > 0;
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $vehicle->id }}"
                                       class="vehicle-checkbox" form="vehicles-bulk">
                            </td>
                            <td>
                                <div class="admin-vehicle-cell">
                                    @if ($photo = $vehicle->mainPhotoUrl())
                                        <img class="admin-vehicle-thumb" src="{{ $photo }}"
                                             alt="{{ $vehicle->fullName() }}" loading="lazy">
                                    @else
                                        <span class="admin-vehicle-thumb admin-vehicle-thumb--empty">
                                            <i class="bi bi-car-front"></i>
                                        </span>
                                    @endif
                                    <div class="admin-vehicle-cell__text">
                                        @if ($vehicle->trashed())
                                            <span class="fw-semibold">{{ $vehicle->fullName() }}</span>
                                        @else
                                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.vehicles.show', $vehicle) }}">
                                                {{ $vehicle->fullName() }}
                                            </a>
                                        @endif
                                        <div class="small text-muted font-monospace">{{ $vehicle->plate }}</div>
                                        @if ($vehicle->trashed())
                                            <span class="badge bg-dark mt-1">{{ __('admin.deleted_vehicle') }}</span>
                                        @elseif (! $vehicle->is_active)
                                            <span class="badge bg-secondary mt-1">{{ __('admin.inactive') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                {{ $vehicle->category->name('fr') }}
                                <div class="text-muted">{{ $vehicle->seats }} {{ __('vehicle.seats') }}</div>
                            </td>
                            <td class="small">
                                {{ $vehicle->transmissionLabel() }}
                                <div class="text-muted">{{ $vehicle->fuelLabel() }}</div>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fw-semibold">{{ app(\App\Services\SettingsService::class)->money($vehicle->category->daily_price) }}</span>
                                <div class="small text-muted">{{ __('admin.per_day') }}</div>
                            </td>
                            <td>
                                @if ($vehicle->trashed())
                                    <span class="admin-availability admin-availability--off">
                                        <span class="admin-availability__dot"></span>{{ __('admin.archived') }}
                                    </span>
                                @elseif ($busyNow)
                                    <span class="admin-availability admin-availability--busy">
                                        <span class="admin-availability__dot"></span>{{ __('admin.busy_now') }}
                                    </span>
                                @elseif ($vehicle->is_active && $vehicle->status->isBookable())
                                    <span class="admin-availability admin-availability--free">
                                        <span class="admin-availability__dot"></span>{{ __('admin.free_now') }}
                                    </span>
                                @else
                                    <span class="admin-availability admin-availability--off">
                                        <span class="admin-availability__dot"></span>{{ __('admin.not_bookable') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($vehicle->trashed())
                                    <span class="badge bg-{{ $vehicle->status->color() }}">{{ $vehicle->status->label() }}</span>
                                @else
                                    <form method="POST" action="{{ route('admin.vehicles.setStatus', $vehicle) }}">
                                        @csrf
                                        <select class="form-select form-select-sm admin-status-select" name="status" onchange="this.form.submit()"
                                                aria-label="{{ __('admin.status') }}">
                                            @foreach ($statuses as $item)
                                                <option value="{{ $item->value }}" @selected($vehicle->status === $item)>{{ $item->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($view === 'archives')
                                    <form method="POST" action="{{ route('admin.vehicles.restore', $vehicle) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                                aria-label="{{ __('admin.restore_vehicle') }}" title="{{ __('admin.restore_vehicle') }}">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endif
                                @unless ($vehicle->trashed())
                                    <a class="btn btn-sm btn-light border" href="{{ route('admin.vehicles.show', $vehicle) }}"
                                       aria-label="{{ __('admin.view_details') }}" title="{{ __('admin.view_details') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a class="btn btn-sm btn-light border" href="{{ route('admin.vehicles.edit', $vehicle) }}"
                                       aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="admin-empty-state">
                                    <i class="bi bi-car-front"></i>
                                    <span>{{ __('admin.no_vehicle') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        @if ($view === 'archives')
            <form id="vehicles-bulk" method="POST" action="{{ route('admin.vehicles.restoreMany') }}">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.restore_selection') }}
                </button>
            </form>
        @else
            <form id="vehicles-bulk" method="POST" action="{{ route('admin.vehicles.destroyMany') }}"
                  onsubmit="return confirm('{{ __('admin.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>{{ __('admin.delete_selection') }}
                </button>
            </form>
        @endif
        <div>{{ $vehicles->links() }}</div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('selectAllVehicles');
            if (!selectAll) return;
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.vehicle-checkbox').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
            });
        })();
    </script>
    @endpush
@endsection
