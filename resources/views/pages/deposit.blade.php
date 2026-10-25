@extends('pages.layout')

@section('title', __('page.deposit_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.deposit_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.deposit_intro') }}</p>

    <div class="section-title">{{ __('page.deposit_how') }}</div>
    <ol class="mb-4">
        <li class="mb-2">{{ __('page.deposit_step1') }}</li>
        <li class="mb-2">{{ __('page.deposit_step2') }}</li>
        <li>{{ __('page.deposit_step3') }}</li>
    </ol>

    <div class="section-title">{{ __('page.deposit_amounts') }}</div>
    <table class="table admin-table mb-4">
        <thead>
            <tr>
                <th>{{ __('nav.category') }}</th>
                <th class="text-end">{{ __('page.deposit_title') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->name($locale) }}</td>
                    <td class="text-end fw-semibold">{{ app(\App\Services\SettingsService::class)->money($category->deposit_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="text-muted small">{{ __('page.deposit_vehicle_exception') }}</p>

    <div class="section-title">{{ __('page.deposit_deductions') }}</div>
    <ul class="mb-3">
        <li>{{ __('page.deposit_deduct_fuel') }}</li>
        <li>{{ __('page.deposit_deduct_damage') }}</li>
        <li>{{ __('page.deposit_deduct_late') }}</li>
    </ul>
    <p class="text-muted small">{{ __('page.deposit_restocked') }}</p>
@endsection
