@extends('layouts.panel')

@section('title', 'CatÃ©gories et tarifs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.categories') }}</h1>
        <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">
            <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_category') }}
        </a>
    </div>

    <div class="pdj-card p-0 overflow-hidden">
        <table class="table admin-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('admin.category') }}</th>
                    <th class="text-end">{{ __('admin.daily_price') }}</th>
                    <th class="text-end">{{ __('admin.deposit') }}</th>
                    <th class="text-end">{{ __('admin.seats') }}</th>
                    <th class="text-end">{{ __('admin.vehicles_count') }}</th>
                    <th>{{ __('admin.rules') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr class="{{ $category->is_active ? '' : 'opacity-50' }}">
                        <td class="fw-semibold">{{ $category->name('fr') }} <span class="text-muted small">{{ $category->slug }}</span></td>
                        <td class="text-end">{{ number_format($category->daily_price, 0, ' ', ' ') }}</td>
                        <td class="text-end">{{ number_format($category->deposit_amount, 0, ' ', ' ') }}</td>
                        <td class="text-end">{{ $category->seats }}</td>
                        <td class="text-end">{{ $category->vehicles_count }}</td>
                        <td class="small text-muted">{{ $category->pricingRules->count() ?: 'â€”' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.categories.edit', $category) }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3">{{ __('admin.price_frozen_hint') }}</p>
@endsection