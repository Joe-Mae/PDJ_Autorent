@extends('layouts.panel')

@section('title', $category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $category->exists ? $category->name('fr') : __('admin.add_category') }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.categories.index') }}">{{ __('admin.back') }}</a>
    </div>

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.identity') }}</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name_fr">Nom (FR)</label>
                            <input type="text" class="form-control" id="name_fr" name="name_fr" value="{{ old('name_fr', $category->name_fr) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="name_en">Name (EN)</label>
                            <input type="text" class="form-control" id="name_en" name="name_en" value="{{ old('name_en', $category->name_en) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description_fr">Description (FR)</label>
                            <textarea class="form-control" id="description_fr" name="description_fr" rows="2">{{ old('description_fr', $category->description_fr) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.tariffs') }}</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="daily_price">{{ __('admin.daily_price') }} ({{ app(\App\Services\SettingsService::class)->currency() }})</label>
                            <input type="number" class="form-control" id="daily_price" name="daily_price"
                                   value="{{ old('daily_price', $category->daily_price) }}" min="1000" step="500" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="deposit_amount">{{ __('admin.deposit') }} ({{ app(\App\Services\SettingsService::class)->currency() }})</label>
                            <input type="number" class="form-control" id="deposit_amount" name="deposit_amount"
                                   value="{{ old('deposit_amount', $category->deposit_amount) }}" min="0" step="10000" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="daily_price_holiday">{{ __('admin.holiday_price') }}</label>
                            <input type="number" class="form-control" id="daily_price_holiday" name="daily_price_holiday"
                                   value="{{ old('daily_price_holiday', $category->daily_price_holiday) }}" min="1000" step="500">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="seats">{{ __('admin.seats') }}</label>
                            <input type="number" class="form-control" id="seats" name="seats" value="{{ old('seats', $category->seats) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="doors">{{ __('admin.doors') }}</label>
                            <input type="number" class="form-control" id="doors" name="doors" value="{{ old('doors', $category->doors) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="bags">{{ __('admin.bags') }}</label>
                            <input type="number" class="form-control" id="bags" name="bags" value="{{ old('bags', $category->bags) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="sort_order">Ordre</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.availability') }}</h2>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                               @checked(old('is_active', $category->exists ? $category->is_active : true))>
                        <label class="form-check-label" for="is_active">{{ __('admin.visible_on_site') }}</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                               @checked(old('is_featured', $category->is_featured))>
                        <label class="form-check-label" for="is_featured">{{ __('admin.featured') }}</label>
                    </div>
                </div>

                <button class="btn btn-primary w-100">{{ $category->exists ? __('admin.save') : __('admin.create') }}</button>
            </div>
        </div>
    </form>
@endsection