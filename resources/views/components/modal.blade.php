{{-- Reusable Bootstrap modal. --}}
{{-- Slots: id, title (HTML), size (sm|md|lg|xl), scrollable, footer, actions. Default modal-footer menyediakan tombol Batal. --}}
@props([
    'id'      => 'appModal',
    'size'    => 'md',
    'scrollable' => false,
])

@php
    $sizeClass = match($size) {
        'sm' => 'modal-sm',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        default => '',
    };
    $dialogClasses = 'modal-dialog modal-dialog-centered ' . trim($sizeClass . ' ' . ($scrollable ? 'modal-dialog-scrollable' : ''));
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="{{ trim($dialogClasses) }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">{!! $title ?? '' !!}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-start">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer">
                    {!! $footer !!}
                </div>
            @else
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    {{ $actions ?? '' }}
                </div>
            @endisset
        </div>
    </div>
</div>