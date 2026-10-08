<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'SK Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nomor SK</th><th>Judul</th><th>Berlaku</th><th>Berakhir</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->decree_number ?? '—' }}<br><small class="text-muted">{{ $item->decree_type ?? '' }}</small></td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->title ?? '—', 40) }}</td>
                            <td class="fs-13">{{ $item->effective_date ? \Carbon\Carbon::parse($item->effective_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-file-text-line', 'title' => 'Belum ada SK'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
