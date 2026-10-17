<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Reward & Punishment Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th>Tanggal</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ strtolower($item->jenis ?? '') === 'punishment' ? 'danger' : 'success' }}-subtle text-{{ strtolower($item->jenis ?? '') === 'punishment' ? 'danger' : 'success' }}">{{ ucfirst($item->jenis ?? '—') }}</span>
                            </td>
                            <td class="fs-13">{{ $item->kategori ?? '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->nama ?? '—', 40) }}</td>
                            <td class="fs-13">{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-award-line', 'title' => 'Belum ada reward/punishment'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
