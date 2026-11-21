{{--
    Kop laporan: periode di kiri, catatan metode di kanan, garis emas di bawah.
    Parameter: $period (string), $note (string|null)
--}}
@if (! empty($period) || ! empty($note))
    <div class="lap-period">
        @if (! empty($period))
            <div class="lap-period-main"><span>Periode</span>{{ $period }}</div>
        @endif
        @if (! empty($note))
            <div class="lap-period-note">{{ $note }}</div>
        @endif
    </div>
@endif
