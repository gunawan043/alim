<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Tindakan Terbaru' }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Diagnosa</th>
                        <th>Tindakan</th>
                        <th>Petugas</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->diagnosis ?? '—', 30) }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->treatment ?? '—', 30) }}</td>
                            <td class="fs-13">{{ $item->petugas ?? '—' }}</td>
                            <td class="fs-13">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-first-aid-kit-line', 'title' => 'Belum ada tindakan tercatat'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
