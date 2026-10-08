<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pengajuan Pengadaan Menunggu' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>No. Pengajuan</th>
                        <th>Tanggal</th>
                        <th>Pemohon</th>
                        <th class="text-end">Estimasi</th>
                        <th>Urgensi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->request_number ?? '—' }}</td>
                            <td class="fs-13">{{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ $item->pemohon ?? '—' }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) ($item->total_estimated_price ?? 0), 0, ',', '.') }}</td>
                            <td class="fs-13">{{ ucfirst($item->urgency ?? '—') }}</td>
                            <td>
                                <span class="badge bg-warning-subtle text-warning">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-shopping-cart-2-line', 'title' => 'Tidak ada pengajuan menunggu'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
