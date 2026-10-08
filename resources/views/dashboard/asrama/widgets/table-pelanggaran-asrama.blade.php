<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pelanggaran Terbaru' }}</h4>
        <span class="badge bg-danger-subtle text-danger">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Tanggal</th>
                        <th>Santri</th>
                        <th>Kamar</th>
                        <th>Jenis</th>
                        <th class="text-center">Poin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fs-13">{{ $item->violation_date ? \Carbon\Carbon::parse($item->violation_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->kamar ?? '—' }}</td>
                            <td class="fs-13">{{ $item->violation_type ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $item->points }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-shield-check-line', 'title' => 'Tidak ada pelanggaran'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
