@php
    $icon = $icon ?? 'ri-inbox-archive-line';
    $title = $title ?? 'Belum ada data';
    $hint = $hint ?? null;
@endphp

<div class="dash-empty">
    <i class="{{ $icon }}"></i>
    <p>{{ $title }}</p>
    @if ($hint)
        <small>{{ $hint }}</small>
    @endif
</div>
