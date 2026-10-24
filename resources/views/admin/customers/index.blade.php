@extends('layouts.panel')

@section('title', __('admin.customers'))
@section('breadcrumb', __('admin.customers'))

@section('content')
    <x-admin.page-header
        :title="__('admin.customers')"
        :subtitle="__('admin.customers_count', ['count' => $guestCustomers->total()])">
        <a class="btn btn-outline-primary" href="{{ route('admin.documents.index') }}">
            <i class="bi bi-file-earmark-check me-1"></i>{{ __('admin.documents') }}
        </a>
    </x-admin.page-header>

    <form method="GET" class="pdj-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label" for="q">{{ __('admin.search') }}</label>
                <div class="admin-input-icon">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}"
                           placeholder="{{ __('admin.customer_search_hint') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end gap-2">
                <a class="btn btn-light border" href="{{ route('admin.customers.index') }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.reset_filters') }}
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('admin.filter') }}
                </button>
            </div>
        </div>
    </form>

    <div class="admin-section-heading mt-2 mb-3">
        <div>
            <span class="admin-eyebrow">{{ __('admin.customers') }}</span>
            <h2 class="h5 fw-bold mb-0">{{ __('admin.guest_customers') }}</h2>
        </div>
        <span class="badge rounded-pill text-bg-light border">{{ $guestCustomers->total() }}</span>
    </div>

    <div class="pdj-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin.client') }}</th>
                        <th>{{ __('booking.field_phone') }}</th>
                        <th>{{ __('booking.field_email') }}</th>
                        <th class="text-end">{{ __('admin.bookings') }}</th>
                        <th>{{ __('admin.last_booking') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guestCustomers as $guest)
                        <tr>
                            <td class="fw-semibold">{{ $guest->customer_name }}</td>
                            <td class="small">{{ $guest->customer_phone ?: '—' }}</td>
                            <td class="small">{{ $guest->customer_email ?: '—' }}</td>
                            <td class="text-end">{{ $guest->bookings_count }}</td>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($guest->last_booking_at)->translatedFormat('d/m/Y') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-light border"
                                   href="{{ route('admin.customers.guest.show', ['bookingId' => $guest->id]) }}"
                                   aria-label="{{ __('admin.view_details') }}">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="admin-empty-state">
                                    <i class="bi bi-person-plus"></i>
                                    <span>{{ __('admin.no_guest_customer') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $guestCustomers->links() }}</div>
@endsection
