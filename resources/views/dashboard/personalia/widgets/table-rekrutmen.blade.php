<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Permintaan Rekrutmen' }}</h4>
        <span class="badge bg-info-subtle text-info">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Jabatan</th><th>Unit</th><th class="text-center">Kebutuhan</th><th>Dibutuhkan</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->jabatan ?? '—' }}</td>
                            <td class="fs-13">{{ $item->unit ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->kebutuhan ?? '—' }}</td>
                            <td class="fs-13">{{ $item->tanggal_dibutuhkan ? \Carbon\Carbon::parse($item->tanggal_dibutuhkan)->translatedFormat('d M Y') : '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-search-line', 'title' => 'Belum ada permintaan rekrutmen'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
