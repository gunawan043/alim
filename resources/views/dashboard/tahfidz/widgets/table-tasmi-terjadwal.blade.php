<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? "Tasmi' Terjadwal" }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Sesi</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Lokasi</th>
                        <th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->session_name }}</td>
                            <td class="fs-13">{{ $item->session_date ? \Carbon\Carbon::parse($item->session_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ $item->session_time_start ? substr((string) $item->session_time_start, 0, 5) : '—' }}</td>
                            <td class="fs-13">{{ $item->location ?? '—' }}</td>
                            <td class="fs-13">{{ str_replace('_', ' ', ucfirst($item->session_type ?? '—')) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-award-line', 'title' => "Tidak ada tasmi' terjadwal"])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
