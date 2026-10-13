@extends('layouts.panel')

@section('title', $driver->exists ? 'Modifier le chauffeur' : 'Nouveau chauffeur')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $driver->exists ? $driver->displayName() : __('admin.add_driver') }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.drivers.index') }}">{{ __('admin.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ $driver->exists ? route('admin.drivers.update', $driver) : route('admin.drivers.store') }}">
                @csrf
                @if ($driver->exists) @method('PUT') @endif

                <div class="pdj-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="first_name">{{ __('booking.field_first_name') }}</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="{{ old('first_name', $driver->first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="last_name">{{ __('booking.field_last_name') }}</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="{{ old('last_name', $driver->last_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">{{ __('booking.field_phone') }}</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="{{ old('phone', $driver->phone) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp"
                                   value="{{ old('whatsapp', $driver->whatsapp) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status">{{ __('admin.status') }}</label>
                            <select class="form-select" id="status" name="status">
                                @foreach (\App\Enums\DriverStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $driver->status) === $status)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bio">{{ __('admin.bio') }}</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3">{{ old('bio', $driver->bio) }}</textarea>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary mt-3">{{ $driver->exists ? __('admin.save') : __('admin.create') }}</button>
            </form>
        </div>
    </div>
@endsection