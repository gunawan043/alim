<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Penghuni Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Kamar</th>
                        <th>Asrama</th>
                        <th class="text-center">Bed</th>
                        <th>Check-in</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->kamar ?? '—' }}</td>
                            <td class="fs-13">{{ $item->asrama ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->bed_number ?? '—' }}</td>
                            <td class="fs-13">{{ $item->check_in_date ? \Carbon\Carbon::parse($item->check_in_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}-subtle text-{{ $item->is_active ? 'success' : 'secondary' }}">
                                    {{ $item->is_active ? 'aktif' : 'keluar' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-3-line', 'title' => 'Belum ada data penghuni'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
