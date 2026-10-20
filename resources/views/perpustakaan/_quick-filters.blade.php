@if(!empty($quickFilters))
    <div class="card-header py-2 bg-light border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-1">
            <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
            @foreach($quickFilters as $filter)
                <a href="{{ $filter['url'] }}" class="filter-badge {{ !empty($filter['active']) ? 'active' : '' }}">
                    <i class="{{ $filter['icon'] ?? 'ri-filter-line' }}"></i>{{ $filter['label'] }}
                </a>
            @endforeach
            @if(!empty($quickResetUrl))
                <a href="{{ $quickResetUrl }}" class="btn btn-sm btn-link text-danger ms-auto"><i class="ri-close-circle-line me-1"></i>Reset</a>
            @endif
        </div>
    </div>
@endif
