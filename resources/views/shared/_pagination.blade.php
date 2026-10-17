{{-- Reusable pagination partial — preserve all query strings --}}
@php
    $paginator ??= $gtkList ?? null;
    if (!$paginator) { return; }
@endphp

@if($paginator->hasPages())
    @php
        $appendParams = request()->except(['page']);
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $from = $paginator->firstItem() ?? 0;
        $to = $paginator->lastItem() ?? 0;
        $total = $paginator->total();
    @endphp
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted" style="font-size:0.75rem;">
            Menampilkan {{ $from }} - {{ $to }} dari {{ $total }} data
        </div>
        <nav>
            <ul class="pagination pagination-rounded mb-0">
                {{-- First --}}
                @if($currentPage > 3)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url(1) . ($appendParams ? '&' . http_build_query($appendParams) : '') }}">«</a>
                    </li>
                @endif

                {{-- Previous --}}
                @if($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">‹</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() . ($appendParams ? '&' . http_build_query($appendParams) : '') }}">‹</a>
                    </li>
                @endif

                {{-- Page numbers --}}
                @foreach($paginator->getUrlRange(max(1, $currentPage - 2), min($lastPage, $currentPage + 2)) as $page => $url)
                    <li class="page-item {{ $page == $currentPage ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url . ($appendParams ? '&' . http_build_query($appendParams) : '') }}">{{ $page }}</a>
                    </li>
                @endforeach

                {{-- Next --}}
                @if($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() . ($appendParams ? '&' . http_build_query($appendParams) : '') }}">›</a>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link">›</span></li>
                @endif

                {{-- Last --}}
                @if($currentPage < $lastPage - 2)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($lastPage) . ($appendParams ? '&' . http_build_query($appendParams) : '') }}">»</a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
