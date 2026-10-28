@props(['booking'])

@php
    // Le même rendez-vous peut apparaître dans le pop-up et sur sa fiche :
    // un suffixe unique évite deux champs portant le même identifiant.
    $uid = $booking->id.'-'.substr(md5(uniqid('', true)), 0, 6);
@endphp

{{-- Les trois gestes d'une absence au retrait : joindre le client (à côté),
     décaler l'heure convenue, ou annuler avec un motif. Partagé par le pop-up
     d'absence et la fiche de la réservation pour qu'ils ne divergent jamais. --}}
<div class="row g-2">
    <div class="col-12 col-lg-6">
        <div class="d-flex flex-wrap gap-1">
            @foreach ([1, 2, 3] as $hours)
                <form method="POST" action="{{ route('admin.bookings.extend', $booking) }}">
                    @csrf
                    <input type="hidden" name="heures" value="{{ $hours }}">
                    <button class="btn btn-sm btn-outline-primary">+{{ $hours }} h</button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <form method="POST" action="{{ route('admin.bookings.extend', $booking) }}"
              class="d-flex gap-1 align-items-end">
            @csrf
            <div class="flex-grow-1">
                <label class="form-label small mb-0" for="pickup-{{ $uid }}">
                    {{ __('admin.no_show_new_time') }}
                </label>
                <input type="datetime-local" class="form-control form-control-sm"
                       id="pickup-{{ $uid }}" name="pickup_at" required>
            </div>
            <button class="btn btn-sm btn-primary">{{ __('admin.no_show_move') }}</button>
        </form>
    </div>

    <div class="col-12">
        <form method="POST" action="{{ route('admin.bookings.no-show.cancel', $booking) }}"
              class="d-flex flex-wrap gap-1 align-items-end">
            @csrf
            <div class="flex-grow-1">
                <label class="form-label small mb-0" for="motif-{{ $uid }}">
                    {{ __('admin.reason') }}
                </label>
                <input type="text" class="form-control form-control-sm"
                       id="motif-{{ $uid }}" name="motif" required maxlength="255"
                       placeholder="{{ __('admin.no_show_reason_hint') }}">
            </div>
            <button class="btn btn-sm btn-outline-danger">{{ __('admin.no_show_cancel') }}</button>
        </form>
    </div>
</div>
