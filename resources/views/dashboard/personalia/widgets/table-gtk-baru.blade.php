<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'GTK Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>NIK</th><th>Jabatan</th><th>Terdaftar</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">{{ $item->nik ?? '—' }}</td>
                            <td class="fs-13">{{ $item->jabatan ?? '—' }}</td>
                            <td class="fs-13">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-id-card-line', 'title' => 'Belum ada profil GTK'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
