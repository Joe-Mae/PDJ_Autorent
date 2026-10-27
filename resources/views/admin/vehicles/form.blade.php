@extends('layouts.panel')

@section('title', $vehicle->exists ? 'Modifier le véhicule' : 'Nouveau véhicule')

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
                                        {{ $category->name('fr') }} — {{ app(\App\Services\SettingsService::class)->money($category->daily_price) }}/j
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
                        <div class="col-md-6">
                            <label class="form-label" for="description_fr">{{ __('admin.description') }} — Français</label>
                            <textarea class="form-control" id="description_fr" name="description_fr" rows="4">{{ old('description_fr', $vehicle->description_fr) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="description_en">{{ __('admin.description') }} — English</label>
                            <textarea class="form-control" id="description_en" name="description_en" rows="4">{{ old('description_en', $vehicle->description_en) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.photos') }}</h2>
                    <input type="file" class="form-control" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple id="photosInput">
                    <div class="form-text">{{ __('admin.photos_hint') }}</div>
                    <div class="form-text text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('admin.photos_max_hint') }}</div>

                    <div id="photoPreview" class="row g-2 mt-2"></div>

                    @if ($vehicle->exists && $vehicle->getMedia(\App\Models\Vehicle::MEDIA_GALLERY)->isNotEmpty())
                        @php
                            $existingPhotos = $vehicle->getMedia(\App\Models\Vehicle::MEDIA_GALLERY)
                                ->sortBy([['order_column', 'desc'], ['id', 'desc']]);
                            $mainPhotoId = $existingPhotos->first()?->id;
                        @endphp
                        <div class="row g-2 mt-3">
                            @foreach ($existingPhotos as $photo)
                                <div class="col-6 col-md-3">
                                    <div class="admin-photo-thumb">
                                        <img src="{{ $photo->getUrl() }}" alt="" loading="lazy">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="radio" name="main_photo_id"
                                                   value="{{ $photo->id }}" id="main-photo-{{ $photo->id }}"
                                                   @checked($photo->id === $mainPhotoId)>
                                            <label class="form-check-label small" for="main-photo-{{ $photo->id }}">
                                                {{ __('admin.main_photo') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text mt-2">{{ __('admin.main_photo_hint') }}</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.availability') }}</h2>
                    @if ($vehicle->exists)
                        <p class="small mb-3">
                            {{ __('admin.status') }} :
                            <span class="badge bg-{{ $vehicle->status->color() }}">{{ $vehicle->status->label() }}</span>
                        </p>
                    @endif
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="is_active" @checked(old('is_active', $vehicle->exists ? $vehicle->is_active : true))>
                        <label class="form-check-label" for="is_active">{{ __('admin.visible_on_site') }}</label>
                    </div>
                    <p class="small text-muted">{{ __('admin.is_active_hint') }}</p>

                    <hr>

                    <div class="form-check form-switch mb-3">
                        {{-- Le champ caché porte « non » quand la case est décochée ; le navigateur envoie ensuite « 1 » si elle est cochée. --}}
                        <input type="hidden" name="deposit_required" value="0">
                        <input class="form-check-input" type="checkbox" name="deposit_required" value="1"
                               id="deposit_required" @checked(old('deposit_required', $vehicle->exists ? $vehicle->deposit_required : true))>
                        <label class="form-check-label" for="deposit_required">{{ __('admin.deposit_required') }}</label>
                    </div>
                    <p class="small text-muted">{{ __('admin.deposit_required_hint') }}</p>

                    <button class="btn btn-primary w-100">{{ $vehicle->exists ? __('admin.save') : __('admin.create') }}</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const input = document.getElementById('photosInput');
            const preview = document.getElementById('photoPreview');
            if (!input || !preview) return;

            input.addEventListener('change', function () {
                preview.innerHTML = '';
                const files = Array.from(input.files || []);
                files.forEach(function (file, index) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';

                    const thumb = document.createElement('div');
                    thumb.className = 'admin-photo-thumb';

                    const img = document.createElement('img');
                    img.alt = '';
                    img.loading = 'lazy';
                    const url = URL.createObjectURL(file);
                    img.src = url;
                    thumb.appendChild(img);

                    const check = document.createElement('div');
                    check.className = 'form-check mt-1';
                    const radio = document.createElement('input');
                    radio.className = 'form-check-input';
                    radio.type = 'radio';
                    radio.name = 'main_photo_index';
                    radio.value = String(index);
                    radio.id = 'main-photo-new-' + index;
                    if (index === 0) radio.checked = true;
                    const label = document.createElement('label');
                    label.className = 'form-check-label small';
                    label.htmlFor = radio.id;
                    label.textContent = '{{ __('admin.main_photo') }}';
                    check.appendChild(radio);
                    check.appendChild(label);
                    thumb.appendChild(check);

                    col.appendChild(thumb);
                    preview.appendChild(col);
                });
            });
        })();
    </script>
@endsection