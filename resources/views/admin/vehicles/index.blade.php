@extends('layouts.panel')

@section('title', 'VÃ©hicules')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.vehicles') }} <span class="text-muted fs-6">{{ $vehicles->total() }}</span></h1>
        <a class="btn btn-primary" href="{{ route('admin.vehicles.create') }}">
            <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_vehicle') }}
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Marque, modÃ¨le, plaque">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="statut">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">{{ __('admin.filter') }}</button>
        </div>
    </form>

    <div class="pdj-card p-0 overflow-hidden">
        <table class="table admin-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('admin.vehicle') }}</th>
                    <th>{{ __('admin.category') }}</th>
                    <th>{{ __('admin.plate') }}</th>
                    <th>{{ __('admin.transmission') }}</th>
                    <th class="text-end">{{ __('admin.mileage') }}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.vehicles.show', $vehicle) }}">
                                {{ $vehicle->fullName() }}
                            </a>
                            @if (! $vehicle->is_active)
                                <span class="badge bg-secondary ms-1">{{ __('admin.inactive') }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $vehicle->category->name('fr') }}</td>
                        <td class="small font-monospace">{{ $vehicle->plate }}</td>
                        <td class="small">{{ $vehicle->transmissionLabel() }} w {{ $vehicle->fuelLabel() }}</td>
                        <td class="text-end small">{{ number_format($vehicle->mileage, 0, ' ', ' ') }} km</td>
                        <td>
                            <form method="POST" action="{{ route('admin.vehicles.setStatus', $vehicle) }}">
                                @csrf
                                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                    @foreach ($statuses as $item)
                                        <option value="{{ $item->value }}" @selected($vehicle->status === $item)>{{ $item->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vehicles.edit', $vehicle) }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('admin.no_vehicle') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $vehicles->links() }}</div>
@endsection