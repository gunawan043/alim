<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pindah Kamar Menunggu' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Dari</th>
                        <th>Ke</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->dari ?? '—' }}</td>
                            <td class="fs-13">{{ $item->ke ?? '—' }}</td>
                            <td class="fs-13">{{ $item->move_date ? \Carbon\Carbon::parse($item->move_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->reason ?? '—', 40) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-logout-box-r-line', 'title' => 'Tidak ada pindah kamar menunggu'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
