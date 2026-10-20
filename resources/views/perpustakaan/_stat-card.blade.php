@php
    $color = $color ?? 'primary';
    $hint = $hint ?? null;
    $badge = $badge ?? null;
    $icon = $icon ?? 'ri-information-line';
@endphp
<div class="col-xl-3 col-md-6">
    <div class="card card-animate h-90">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-{{ $color }}-subtle rounded fs-2">
                        <i class="{{ $icon }} text-{{ $color }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">{{ $label }}</p>
                    <h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3>
                </div>
            </div>
            @if($hint)
                <p class="text-muted mb-0" style="font-size:11px;"><i class="ri-information-line me-1"></i>{{ $hint }}</p>
            @endif
            @if($badge)
                <span class="badge bg-{{ $badge[1] ?? 'primary' }}-subtle text-{{ $badge[1] ?? 'primary' }}" style="font-size:10px;">{{ $badge[0] }}</span>
            @endif
        </div>
    </div>
</div>
