@extends('pages.layout')

@section('title', __('page.legal_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.legal_title') }}</h1>

    <table class="table admin-table mb-4">
        <tbody>
            <tr><td style="width: 30%;">{{ __('page.legal_title') }}</td><td class="fw-semibold">{{ $company }}</td></tr>
            <tr><td>{{ __('page.agency_address') }}</td><td>{{ $address }}</td></tr>
            <tr><td>{{ __('booking.field_phone') }}</td><td>{{ $phone }}</td></tr>
            <tr><td>{{ __('booking.field_email') }}</td><td>{{ $email }}</td></tr>
        </tbody>
    </table>

    <div class="pdj-card">
        <h2 class="h6 fw-bold">{{ __('page.legal_publisher') }}</h2>
        <p class="small text-muted mb-0">{{ __('page.legal_publisher_text') }}</p>
    </div>

    <div class="pdj-card mt-3">
        <h2 class="h6 fw-bold">{{ __('page.legal_hosting') }}</h2>
        <p class="small text-muted mb-0">{{ __('page.legal_hosting_text') }}</p>
    </div>

    <div class="pdj-card mt-3">
        <h2 class="h6 fw-bold">{{ __('page.legal_property') }}</h2>
        <p class="small text-muted mb-0">{{ __('page.legal_property_text') }}</p>
    </div>
@endsection
