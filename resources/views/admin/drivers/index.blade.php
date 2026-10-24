@extends('layouts.panel')

@section('title', __('admin.drivers'))
@section('breadcrumb', __('admin.drivers'))

@section('content')
    <x-admin.page-header
        :title="__('admin.drivers')"
        :subtitle="__('admin.drivers_count', ['count' => $drivers->count()])">
        <a class="btn btn-primary" href="{{ route('admin.drivers.create') }}">
            <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_driver') }}
        </a>
    </x-admin.page-header>

    <div class="admin-info-strip mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <span>
            {{ __('admin.free_slots', ['count' => $freeSlots, 'price' => number_format($vehicleDailyPrice, 0, ' ', ' ')]) }}
        </span>
    </div>

    <form method="GET" class="pdj-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label" for="q">{{ __('admin.search') }}</label>
                <div class="admin-input-icon">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}"
                           placeholder="{{ __('admin.driver_search_hint') }}">
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
                <a class="btn btn-light border" href="{{ route('admin.drivers.index') }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.reset_filters') }}
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('admin.filter') }}
                </button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($drivers as $driver)
            <div class="col-12 col-md-6 col-xxl-4">
                <article class="pdj-card admin-driver-card h-100">
                    <div class="d-flex align-items-start gap-3">
                        <span class="admin-avatar">{{ $driver->initials() }}</span>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                <div>
                                    <h2 class="h6 fw-bold mb-0">{{ $driver->displayName() }}</h2>
                                    <a class="small text-muted text-decoration-none" href="tel:{{ $driver->phone }}">
                                        <i class="bi bi-telephone me-1"></i>{{ $driver->phone }}
                                    </a>
                                </div>
                                <span class="badge rounded-pill bg-{{ $driver->status->isBookable() ? 'success' : ($driver->status === \App\Enums\DriverStatus::Conge ? 'warning' : 'secondary') }}">
                                    {{ $driver->status->label() }}
                                </span>
                            </div>

                            <div class="admin-driver-meta">
                                <span><i class="bi bi-star-fill text-warning"></i> {{ $driver->rating ?? '—' }}</span>
                                <span><i class="bi bi-check2-circle text-success"></i> {{ $driver->completed_rentals }} {{ __('admin.rentals_done') }}</span>
                                <span><i class="bi bi-calendar-check text-primary"></i> {{ $driver->active_bookings_count }} {{ __('admin.upcoming_count') }}</span>
                            </div>

                            <div class="mt-2">
                                @if ($driver->current_bookings_count > 0)
                                    <span class="admin-availability admin-availability--busy">
                                        <span class="admin-availability__dot"></span>{{ __('admin.driver_on_trip') }}
                                    </span>
                                @elseif ($driver->status->isBookable())
                                    <span class="admin-availability admin-availability--free">
                                        <span class="admin-availability__dot"></span>{{ __('admin.free_now') }}
                                    </span>
                                @else
                                    <span class="admin-availability admin-availability--off">
                                        <span class="admin-availability__dot"></span>{{ __('admin.driver_off') }}
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.drivers.edit', $driver) }}">
                                    <i class="bi bi-pencil me-1"></i>{{ __('admin.edit') }}
                                </a>
                                <a class="btn btn-sm btn-light border"
                                   href="{{ route('admin.bookings.index', ['chauffeur' => $driver->id]) }}">
                                    <i class="bi bi-clock-history me-1"></i>{{ __('admin.history') }}
                                </a>
                                @if ($driver->status->isBookable())
                                    <form method="POST" action="{{ route('admin.drivers.destroy', $driver) }}"
                                          onsubmit="return confirm('{{ __('admin.retire_driver_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">{{ __('admin.retire') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="pdj-card">
                    <div class="admin-empty-state">
                        <i class="bi bi-person-badge"></i>
                        <span>{{ __('admin.no_driver') }}</span>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
