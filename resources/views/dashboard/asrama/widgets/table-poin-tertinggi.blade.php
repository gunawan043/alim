<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Poin Pelanggaran Tertinggi' }}</h4>
        <span class="badge bg-danger-subtle text-danger">{{ now()->translatedFormat('M Y') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>#</th>
                        <th>Santri</th>
                        <th class="text-center">Kasus</th>
                        <th class="text-center">Total Poin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $i => $item)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="text-center fs-13">{{ $item->jumlah }}</td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $item->poin }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-trophy-line', 'title' => 'Tidak ada catatan poin bulan ini'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
