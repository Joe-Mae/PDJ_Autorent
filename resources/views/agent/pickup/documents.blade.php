@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'ContrÃ´le des originaux')

@section('content')
    @php $required = $booking->with_driver ? ['piece_identite'] : ['piece_identite', 'permis'] @endphp

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 1/5</div>
                <h1 class="h4 fw-bold">{{ __('agent.check_originals') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->vehicle->displayName() }}</p>
            </div>

            <form method="POST" action="{{ route('agent.pickup.documents.store', $booking) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="with_driver" value="{{ $booking->with_driver ? 1 : 0 }}">

                <div class="pdj-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-bold">{{ $booking->user?->displayName() }}</div>
                            <div class="small text-muted">
                                {{ $booking->user?->birth_date?->format('d/m/Y') }} ({{ $booking->user?->age() }} ans)
                            </div>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="tel:{{ $booking->user?->phone }}">
                            <i class="bi bi-telephone"></i> {{ $booking->user?->phone }}
                        </a>
                    </div>

                    @if ($booking->with_driver)
                        <div class="alert alert-success small mb-0">
                            <i class="bi bi-person-check me-1"></i>{{ __('driver.no_license_needed') }}
                        </div>
                    @endif
                </div>

                @foreach ($required as $type)
                    <div class="pdj-card mb-3">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="originaux[]" value="{{ $type }}"
                                   id="orig-{{ $type }}" checked>
                            <label class="form-check-label fw-bold" for="orig-{{ $type }}">
                                {{ \App\Enums\DocumentType::from($type)->label() }} â€” {{ __('agent.original_seen') }}
                            </label>
                        </div>
                        <div class="small text-muted">
                            {{ __('agent.compare_name') }} : <strong>{{ $booking->user?->displayName() }}</strong>
                            @if ($type === 'permis')
                                w {{ __('agent.license_1_year') }} :
                                {{ $booking->user?->birth_date?->diffInYears(now()) >= 18 ? 'âœ“' : 'â€”' }}
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="pdj-card mb-3">
                    <label class="form-label" for="photo_origine">
                        <i class="bi bi-camera me-1"></i>{{ __('agent.photo_original') }}
                    </label>
                    <input type="file" class="form-control" id="photo_origine" name="photo_origine" accept=".jpg,.jpeg,.png">
                    <div class="form-text">{{ __('agent.photo_original_hint') }}</div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection