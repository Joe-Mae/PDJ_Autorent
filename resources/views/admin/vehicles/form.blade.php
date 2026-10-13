@extends('layouts.panel')

@section('title', $vehicle->exists ? 'Modifier le vÃ©hicule' : 'Nouveau vÃ©hicule')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $vehicle->exists ? $vehicle->fullName() : __('admin.add_vehicle') }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vehicles.index') }}">{{ __('admin.back') }}</a>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}">
        @csrf
        @if ($vehicle->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.identity') }}</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="brand">{{ __('admin.brand') }}</label>
                            <input type="text" class="form-control" id="brand" name="brand" value="{{ old('brand', $vehicle->brand) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="model">{{ __('admin.model') }}</label>
                            <input type="text" class="form-control" id="model" name="model" value="{{ old('model', $vehicle->model) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="plate">{{ __('admin.plate') }}</label>
                            <input type="text" class="form-control font-monospace" id="plate" name="plate"
                                   value="{{ old('plate', $vehicle->plate) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="year">{{ __('vehicle.year') }}</label>
                            <input type="number" class="form-control" id="year" name="year" value="{{ old('year', $vehicle->year) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="mileage">{{ __('admin.mileage') }}</label>
                            <input type="number" class="form-control" id="mileage" name="mileage" value="{{ old('mileage', $vehicle->mileage) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">{{ __('admin.category') }}</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $vehicle->category_id) === $category->id)>
                                        {{ $category->name('fr') }} â€” {{ number_format($category->daily_price, 0, ' ', ' ') }} F CFA/j
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('admin.category_hint') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="next_technical_control_at">{{ __('admin.next_control') }}</label>
                            <input type="date" class="form-control" id="next_technical_control_at" name="next_technical_control_at"
                                   value="{{ old('next_technical_control_at', $vehicle->next_technical_control_at?->toDateString()) }}">
                        </div>
                    </div>
                </div>

                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.specs') }}</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="transmission">{{ __('admin.transmission') }}</label>
                            <select class="form-select" id="transmission" name="transmission" required>
                                <option value="manuelle" @selected(old('transmission', $vehicle->transmission) === 'manuelle')>Manuelle</option>
                                <option value="automatique" @selected(old('transmission', $vehicle->transmission) === 'automatique')>Automatique</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fuel">{{ __('admin.fuel') }}</label>
                            <select class="form-select" id="fuel" name="fuel" required>
                                @foreach (['essence', 'diesel', 'hybride', 'electrique'] as $fuel)
                                    <option value="{{ $fuel }}" @selected(old('fuel', $vehicle->fuel) === $fuel)>{{ __('vehicle.fuel.'.$fuel) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="seats">{{ __('admin.seats') }}</label>
                            <input type="number" class="form-control" id="seats" name="seats" value="{{ old('seats', $vehicle->seats) }}" min="2" max="9" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="doors">{{ __('admin.doors') }}</label>
                            <input type="number" class="form-control" id="doors" name="doors" value="{{ old('doors', $vehicle->doors) }}" min="2" max="6" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bags">{{ __('admin.bags') }}</label>
                            <input type="number" class="form-control" id="bags" name="bags" value="{{ old('bags', $vehicle->bags) }}" min="0" max="9" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="air_conditioning" value="1"
                                       id="air_conditioning" @checked(old('air_conditioning', $vehicle->air_conditioning))>
                                <label class="form-check-label" for="air_conditioning">{{ __('vehicle.air_con') }}</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description_fr">{{ __('admin.description') }}</label>
                            <textarea class="form-control" id="description_fr" name="description_fr" rows="3">{{ old('description_fr', $vehicle->description_fr) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.photos') }}</h2>
                    <input type="file" class="form-control" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <div class="form-text">{{ __('admin.photos_hint') }}</div>
                    <div class="form-text text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('admin.photos_max_hint') }}</div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.availability') }}</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="is_active" @checked(old('is_active', $vehicle->exists ? $vehicle->is_active : true))>
                        <label class="form-check-label" for="is_active">{{ __('admin.visible_on_site') }}</label>
                    </div>
                    <p class="small text-muted">{{ __('admin.is_active_hint') }}</p>
                    <button class="btn btn-primary w-100">{{ $vehicle->exists ? __('admin.save') : __('admin.create') }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection