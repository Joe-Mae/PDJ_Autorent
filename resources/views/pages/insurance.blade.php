@extends('pages.layout')

@section('title', __('page.insurance_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.insurance_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.insurance_intro') }}</p>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="pdj-card h-100" style="border-left: 4px solid var(--pdj-green);">
                <div class="section-title">{{ __('page.insurance_covered') }}</div>
                <ul class="small mb-0">
                    <li>Responsabilité civile</li>
                    <li>Vol</li>
                    <li>Incendie</li>
                    <li>Bris de vitres</li>
                    <li>Assistance dépannage 24/7</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="pdj-card h-100">
                <div class="section-title">{{ __('page.insurance_not_covered') }}</div>
                <ul class="small mb-0">
                    <li>Conduite sans permis valide</li>
                    <li>Sous l\'influence de l\'alcool</li>
                    <li>Usage commercial non déclaré</li>
                    <li>Sortie du territoire sans autorisation</li>
                    <li>Dégâts dus à un mauvais Conducteur</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="pdj-card mb-4" style="background: var(--pdj-green-light); border-color: rgba(10,110,76,.2);">
        <div class="section-title">{{ __('page.insurance_excess') }}</div>
        <p class="small mb-0">{{ __('page.insurance_excess_text', ['amount' => app(\App\Services\SettingsService::class)->money(app(\App\Services\SettingsService::class)->int('booking.insurance_deductible'))]) }}</p>
    </div>

    <div class="pdj-card">
        <div class="section-title">{{ __('page.insurance_damage') }}</div>
        <p class="small mb-0">{{ __('page.insurance_damage_text') }}</p>
    </div>
@endsection
