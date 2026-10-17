@props(['label', 'value', 'icon', 'color' => 'primary'])

<div class="col-xl-3 col-md-6">
    <div class="card stat-card h-100" style="border-left-color: var(--bs-{{ $color }}-text-emphasis);">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-{{ $color }}-subtle">
                    <i class="{{ $icon }} text-{{ $color }} fs-4"></i>
                </div>
                <div>
                    <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">{{ $label }}</p>
                    <h2 class="fw-bold ff-secondary mb-0">{{ $value }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
