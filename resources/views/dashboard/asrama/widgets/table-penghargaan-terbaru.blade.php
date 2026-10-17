<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Penghargaan Terbaru' }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Penghargaan</th>
                        <th>Kategori</th>
                        <th>Level</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->title ?? '—' }}</td>
                            <td class="fs-13">{{ ucfirst($item->category ?? '—') }}</td>
                            <td><span class="badge bg-success-subtle text-success">{{ ucfirst($item->level ?? '—') }}</span></td>
                            <td class="fs-13">{{ $item->awarded_date ? \Carbon\Carbon::parse($item->awarded_date)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-medal-line', 'title' => 'Belum ada penghargaan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
