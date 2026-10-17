<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Promosi & Demosi Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Jenis</th><th>Jabatan</th><th>SK</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">{{ ucfirst($item->jenis ?? '—') }}</td>
                            <td class="fs-13">{{ $item->jabatan_lama ?? '—' }} → {{ $item->jabatan_baru ?? '—' }}</td>
                            <td class="fs-13">{{ $item->nomor_sk ?? '—' }}<br><small class="text-muted">{{ $item->tanggal_sk ? \Carbon\Carbon::parse($item->tanggal_sk)->translatedFormat('d M Y') : '' }}</small></td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-arrow-up-circle-line', 'title' => 'Belum ada promosi/demosi'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
