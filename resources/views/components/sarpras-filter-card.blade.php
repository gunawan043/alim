@props(['action' => null, 'reset' => null])
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $action ?: url()->current() }}" class="row g-2 align-items-end">
            {{ $slot }}
            <div class="col-md-auto d-flex gap-2">
                <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Filter</button>
                @if($reset)
                    <a href="{{ $reset }}" class="btn btn-light"><i class="ri-refresh-line"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>
