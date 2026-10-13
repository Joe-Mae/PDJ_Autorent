@extends('layouts.panel')

@section('title', 'Clients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.customers') }}</h1>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Nom, tÃ©lÃ©phone, e-mail">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">{{ __('admin.filter') }}</button>
        </div>
    </form>

    <div class="pdj-card p-0 overflow-hidden">
        <table class="table admin-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('admin.client') }}</th>
                    <th>{{ __('booking.field_phone') }}</th>
                    <th class="text-end">{{ __('admin.bookings') }}</th>
                    <th class="text-end">{{ __('admin.documents') }}</th>
                    <th class="text-end">{{ __('admin.absences') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.customers.show', $user) }}">
                                {{ $user->displayName() }}
                            </a>
                            @if ($user->isBlocked())
                                <span class="badge bg-danger ms-1">{{ __('admin.blocked') }}</span>
                            @endif
                            @if ($user->prepayment_required)
                                <span class="badge bg-warning text-dark ms-1">{{ __('admin.prepay') }}</span>
                            @endif
                        </td>
                        <td class="small"><a href="tel:{{ $user->phone }}">{{ $user->phone }}</a></td>
                        <td class="text-end">{{ $user->bookings_count }}</td>
                        <td class="text-end">{{ $user->documents_count }}</td>
                        <td class="text-end">
                            @if ($user->no_show_count > 0)
                                <span class="text-danger fw-bold">{{ $user->no_show_count }}</span>
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.customers.show', $user) }}">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('admin.no_customer') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
@endsection