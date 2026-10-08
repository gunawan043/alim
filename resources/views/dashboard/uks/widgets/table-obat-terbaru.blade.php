<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pemberian Obat Terbaru' }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Obat</th>
                        <th>Dosis</th>
                        <th>Rute</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->medicine_name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->dosage ?? '—' }}</td>
                            <td class="fs-13">{{ $item->route ?? '—' }}</td>
                            <td class="fs-13">{{ $item->given_at ? \Carbon\Carbon::parse($item->given_at)->translatedFormat('d M H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-medicine-bottle-line', 'title' => 'Belum ada riwayat pemberian obat'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
