<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Agenda Mendatang' }}</h4>
        <span class="badge bg-primary-subtle text-primary">14 hari</span>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['items'] ?? [] as $item)
                    <li class="list-group-item d-flex align-items-start gap-3">
                        <div class="text-center flex-shrink-0" style="width:46px;">
                            <div class="fw-bold fs-5 lh-1">{{ \Carbon\Carbon::parse($item->start_datetime)->format('d') }}</div>
                            <small class="text-muted text-uppercase">{{ \Carbon\Carbon::parse($item->start_datetime)->translatedFormat('M') }}</small>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-medium">
                                {{ $item->title }}
                                @if (! empty($item->is_mandatory))
                                    <span class="badge bg-danger-subtle text-danger ms-1">Wajib</span>
                                @endif
                            </p>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($item->start_datetime)->format('H:i') }}
                                @if ($item->location_name) · {{ $item->location_name }} @endif
                                @if ($item->scope) · {{ $item->scope }} @endif
                            </small>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-event-line', 'title' => 'Tidak ada agenda 14 hari ke depan'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
