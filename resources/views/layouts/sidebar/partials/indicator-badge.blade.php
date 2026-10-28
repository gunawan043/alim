@php
    $indicatorCount = (int) (($sidebarIndicators ?? [])[$key ?? ''] ?? 0);
@endphp
@if($indicatorCount > 0)
    <span class="badge bg-danger rounded-pill ms-auto sidebar-indicator"
          title="{{ $title ?? 'Ada pekerjaan yang menunggu' }}">
        {{ $indicatorCount > 99 ? '99+' : $indicatorCount }}
    </span>
@endif
