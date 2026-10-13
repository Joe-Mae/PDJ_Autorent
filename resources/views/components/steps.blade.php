@php
    /**
     * File d'étapes du tunnel. On montre toujours où le client en est, parce
     * qu'un formulaire en quatre étapes fait peur s'il n'est pas balisé.
     *
     * @var int $current 1 à 5
     */
    $steps = [
        1 => __('booking.step.summary'),
        2 => __('booking.step.contact'),
        3 => __('booking.step.verify'),
        4 => __('booking.step.documents'),
        5 => __('booking.step.review'),
    ];
@endphp

<nav class="steps mb-4" aria-label="{{ __('booking.step.summary') }}">
    @foreach ($steps as $number => $label)
        @php
            $isDone = $number < $current;
            $isCurrent = $number === $current;
        @endphp

        <div class="step {{ $isDone ? 'is-done' : '' }} {{ $isCurrent ? 'is-current' : '' }}"
             @if ($isCurrent) aria-current="step" @endif>
            <span class="num">
                @if ($isDone)
                    <i class="bi bi-check" aria-hidden="true"></i>
                @else
                    {{ $number }}
                @endif
            </span>
            <span>{{ $label }}</span>
            @if ($isCurrent)
                <span class="visually-hidden">({{ __('general.current_step') }})</span>
            @endif
        </div>
    @endforeach
</nav>
