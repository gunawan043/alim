@props([
    'id' => 'notifyModal',
    'tone' => 'info',
    'title' => 'Informasi',
    'message' => '',
    'image' => null,
])

@php
    $palette = [
        'success' => ['icon' => 'https://cdn.lordicon.com/lupuorrc.json', 'primary' => '#08a88a', 'secondary' => '#121331', 'button' => 'btn-success', 'ri' => 'ri-checkbox-circle-line'],
        'danger' => ['icon' => 'https://cdn.lordicon.com/gsqxdxog.json', 'primary' => '#f06548', 'secondary' => '#f7b84b', 'button' => 'btn-danger', 'ri' => 'ri-error-warning-line'],
        'warning' => ['icon' => 'https://cdn.lordicon.com/kkvwdgfc.json', 'primary' => '#f7b84b', 'secondary' => '#f06548', 'button' => 'btn-warning', 'ri' => 'ri-alert-line'],
        'info' => ['icon' => 'https://cdn.lordicon.com/msoeawqm.json', 'primary' => '#405189', 'secondary' => '#08a88a', 'button' => 'btn-primary', 'ri' => 'ri-information-line'],
    ];
    $p = $palette[$tone] ?? $palette['info'];
@endphp

@once
    @push('scripts')
        <script src="https://cdn.lordicon.com/lordicon.js"></script>
    @endpush
@endonce

<div class="modal fade zoomIn" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center px-4 pb-4">
                @if($image)
                    <img src="{{ $image }}" alt=""
                         style="width:110px;height:110px;object-fit:contain;filter:brightness(0) saturate(100%) invert(23%) sepia(93%) saturate(3000%) hue-rotate(345deg) brightness(95%) contrast(100%);">
                @else
                    <lord-icon src="{{ $p['icon'] }}" trigger="loop"
                               colors="primary:{{ $p['primary'] }},secondary:{{ $p['secondary'] }}"
                               style="width:110px;height:110px;"></lord-icon>
                @endif
                <h4 class="mt-2 mb-2">{{ $title }}</h4>
                <div class="text-muted mb-4">{!! nl2br(e($message)) !!}</div>
                <button type="button" class="btn {{ $p['button'] }} px-4" data-bs-dismiss="modal">
                    <i class="{{ $p['ri'] }} me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>
