<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Penilaian Kinerja Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Nama</th>
                        <th>Periode</th>
                        <th>Skor</th>
                        <th>Predikat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">{{ $item->periode ?? '—' }}</td>
                            <td class="fw-semibold">{{ $item->total_skor !== null ? number_format((float) $item->total_skor, 1) : '—' }}</td>
                            <td class="fs-13">{{ $item->nilai_huruf ?? $item->kategori_hasil ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $item->status === 'final' ? 'success' : 'secondary' }}-subtle text-{{ $item->status === 'final' ? 'success' : 'secondary' }}">
                                    {{ $item->status ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-award-line', 'title' => 'Belum ada penilaian kinerja'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
