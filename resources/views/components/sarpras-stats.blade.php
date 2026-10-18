@props(['items' => []])
<div class="row g-3 mb-3">
    @foreach($items as $item)
        @php
            $label = $item['label'] ?? '-';
            $value = $item['value'] ?? 0;
            $tone = $item['tone'] ?? 'primary';
            $icon = $item['icon'] ?? 'ri-bar-chart-box-line';
            $href = $item['href'] ?? null;
        @endphp
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span>
                    </span>
                    <div class="min-w-0">
                        <div class="text-muted small">{{ $label }}</div>
                        @if($href)
                            <a href="{{ $href }}" class="fs-4 fw-bold text-reset text-decoration-none">{{ is_numeric($value) ? number_format($value) : $value }}</a>
                        @else
                            <div class="fs-4 fw-bold">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
