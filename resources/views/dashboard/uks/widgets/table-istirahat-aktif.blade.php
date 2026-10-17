<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Izin Istirahat Aktif' }}</h4>
        <span class="badge bg-info-subtle text-info">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Jenis</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="text-center">Hari</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ str_replace('_', ' ', ucfirst($item->permit_type ?? '—')) }}</td>
                            <td class="fs-13">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->translatedFormat('d M') : '—' }}</td>
                            <td class="fs-13">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->translatedFormat('d M') : '—' }}</td>
                            <td class="text-center fs-13">{{ $item->rest_days ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-zzz-line', 'title' => 'Tidak ada izin istirahat aktif'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
