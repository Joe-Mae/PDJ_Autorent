@extends('layouts.panel')

@section('title', 'Chauffeurs')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.drivers') }}</h1>
        <a class="btn btn-primary" href="{{ route('admin.drivers.create') }}">
            <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_driver') }}
        </a>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-center">
        <i class="bi bi-info-circle-fill"></i>
        <span class="small">
            {{ __('admin.free_slots', ['count' => $freeSlots, 'price' => number_format($vehicleDailyPrice, 0, ' ', ' ')]) }}
        </span>
    </div>

    <div class="row g-3">
        @forelse ($drivers as $driver)
            <div class="col-md-6 col-lg-4">
                <div class="pdj-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="h6 fw-bold mb-0">{{ $driver->displayName() }}</h2>
                            <div class="small text-muted">
                                <a href="tel:{{ $driver->phone }}">{{ $driver->phone }}</a>
                            </div>
                        </div>
                        <span class="badge bg-{{ $driver->status === \App\Enums\DriverStatus::Actif ? 'success' : 'secondary' }}">
                            {{ $driver->status->label() }}
                        </span>
                    </div>

                    <div class="small text-muted mt-2">
                        <span class="text-warning">â˜… {{ $driver->rating ?? 'â€”' }}</span>
                        w {{ $driver->completed_rentals }} {{ __('admin.rentals_done') }}
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.drivers.edit', $driver) }}">
                            {{ __('admin.edit') }}
                        </a>
                        @if ($driver->status === \App\Enums\DriverStatus::Actif)
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
        @empty
            <div class="col-12"><p class="text-muted">{{ __('admin.no_driver') }}</p></div>
        @endforelse
    </div>
@endsection