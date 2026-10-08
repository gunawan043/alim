<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pengumuman Asrama' }}</h4>
        <span class="badge bg-secondary-subtle text-secondary">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['items'] ?? [] as $item)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-medium">{{ $item->title }}</p>
                                <small class="text-muted">
                                    {{ ucfirst($item->category ?? 'umum') }}
                                    · {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') : '—' }}
                                </small>
                            </div>
                            @if (! empty($item->needs_response))
                                <span class="badge bg-warning-subtle text-warning">butuh respons</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-megaphone-line', 'title' => 'Belum ada pengumuman'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
