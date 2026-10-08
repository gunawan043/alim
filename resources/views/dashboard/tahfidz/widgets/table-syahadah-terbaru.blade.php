<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Syahadah Terbaru' }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Jenis</th>
                        <th>Nomor</th>
                        <th class="text-center">Juz</th>
                        <th>Terbit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ str_replace('_', ' ', ucfirst($item->certificate_type ?? '—')) }}</td>
                            <td class="fs-13">{{ $item->certificate_number ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->total_juz_completed ?? '—' }}</td>
                            <td class="fs-13">{{ $item->issued_date ? \Carbon\Carbon::parse($item->issued_date)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-medal-line', 'title' => 'Belum ada syahadah diterbitkan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
