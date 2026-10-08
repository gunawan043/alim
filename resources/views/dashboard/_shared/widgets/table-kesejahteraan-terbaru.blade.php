<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Klaim Kesejahteraan Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>No. Klaim</th>
                        <th>Nama</th>
                        <th class="text-end">Nilai Diminta</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->nomor_klaim ?? '—' }}</td>
                            <td class="fs-13">{{ $item->name ?? '—' }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) ($item->nilai_diminta ?? 0), 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'approved', 'disetujui' => 'success',
                                        'rejected', 'ditolak'   => 'danger',
                                        'diproses', 'processing' => 'info',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                            <td class="fs-13">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-hand-heart-line', 'title' => 'Belum ada klaim kesejahteraan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
