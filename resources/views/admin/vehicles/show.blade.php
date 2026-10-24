@extends('layouts.panel')

@section('title', $vehicle->fullName())

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $vehicle->fullName() }}</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.vehicles.edit', $vehicle) }}">{{ __('admin.edit') }}</a>
            <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}"
                  onsubmit="return confirm('{{ __('admin.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('admin.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            @if ($vehicle->media->isNotEmpty())
                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.photos') }}</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($vehicle->media->sortByDesc('order_column') as $media)
                            <div class="position-relative d-inline-block">
                                <img src="{{ $media->getUrl() }}" class="rounded" style="width: 110px; height: 80px; object-fit: cover;" alt="">
                                @if ($media->id === $vehicle->media->sortByDesc('order_column')->first()?->id)
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-1">{{ __('admin.main_photo') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.last_bookings') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                                <td class="small">{{ $booking->starts_at->translatedFormat('d/m/Y') }}</td>
                                <td class="small">{{ $booking->user?->displayName() }}</td>
                                <td class="text-end"><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="text-muted small">{{ __('admin.no_booking_short') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.specs') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        <tr><td>{{ __('admin.plate') }}</td><td class="text-end font-monospace">{{ $vehicle->plate }}</td></tr>
                        <tr><td>{{ __('admin.category') }}</td><td class="text-end">{{ $vehicle->category->name('fr') }}</td></tr>
                        <tr><td>{{ __('admin.transmission') }}</td><td class="text-end">{{ $vehicle->transmissionLabel() }}</td></tr>
                        <tr><td>{{ __('admin.fuel') }}</td><td class="text-end">{{ $vehicle->fuelLabel() }}</td></tr>
                        <tr><td>{{ __('admin.seats') }}</td><td class="text-end">{{ $vehicle->seats }}</td></tr>
                        <tr><td>{{ __('admin.mileage') }}</td><td class="text-end">{{ number_format($vehicle->mileage, 0, ' ', ' ') }} km</td></tr>
                        <tr><td>{{ __('admin.next_control') }}</td><td class="text-end">{{ $vehicle->next_technical_control_at?->format('d/m/Y') ?? '—' }}</td></tr>
                        <tr>
                            <td>{{ __('admin.status') }}</td>
                            <td class="text-end"><span class="badge bg-{{ $vehicle->status->color() }}">{{ $vehicle->status->label() }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.blocks') }}</h2>
                @forelse ($blocks as $block)
                    <div class="border-bottom py-2 small">
                        {{ $block->starts_at->translatedFormat('d/m') }} → {{ $block->ends_at->translatedFormat('d/m/Y') }}
                        <span class="badge bg-secondary ms-1">{{ $block->reason->label() }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">{{ __('admin.no_block') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection