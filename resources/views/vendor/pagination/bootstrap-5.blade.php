@if ($paginator->hasPages())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        {{-- Result info --}}
        <div class="text-muted small">
            Menampilkan
            <span class="fw-semibold">{{ $paginator->firstItem() ?? 0 }}</span>
            –
            <span class="fw-semibold">{{ $paginator->lastItem() ?? 0 }}</span>
            dari
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            data
        </div>

        {{-- Pagination --}}
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-separated mb-0">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        <span class="page-link" aria-hidden="true">
                            <i class="ri-arrow-left-s-line"></i>
                            <span class="d-none d-sm-inline ms-1">Sebelumnya</span>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                            <i class="ri-arrow-left-s-line"></i>
                            <span class="d-none d-sm-inline ms-1">Sebelumnya</span>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link"><span class="d-none d-sm-inline">Halaman </span>{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                            <span class="d-none d-sm-inline me-1">Selanjutnya</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                        <span class="page-link" aria-hidden="true">
                            <span class="d-none d-sm-inline me-1">Selanjutnya</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif