{{--
    Keadaan kosong: satu baris tenang, tanpa ilustrasi.
    Parameter: $title, $desc (opsional), $height (opsional, px)
--}}
<div class="lap-empty" @if (! empty($height)) style="min-height: {{ (int) $height }}px;" @endif>
    <div class="lap-empty-title">{{ $title ?? 'Belum ada data' }}</div>
    @if (! empty($desc))
        <div class="lap-empty-desc">{{ $desc }}</div>
    @endif
</div>
