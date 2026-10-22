@props([
    'id' => 'filterModal',
    'title' => 'Filter Data',
    'icon' => 'ri-filter-3-line',
    'action' => null,
    'size' => 'modal-lg',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }} modal-dialog-centered">
        <form method="GET" action="{{ $action ?: url()->current() }}" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">
                    <i class="{{ $icon }} me-2 text-primary"></i>{{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <a href="{{ $action ?: url()->current() }}" class="btn btn-light">
                    <i class="ri-refresh-line me-1"></i>Reset
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-filter-3-line me-1"></i>Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>
