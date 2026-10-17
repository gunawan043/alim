<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pelatihan GTK Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Pelatihan</th><th>Penyelenggara</th><th class="text-center">Tahun</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->nama_pelatihan ?? '—', 35) }}<br><small class="text-muted">{{ $item->bidang_pelatihan ?? '' }}</small></td>
                            <td class="fs-13">{{ $item->penyelenggara ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->tahun ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-presentation-line', 'title' => 'Belum ada pelatihan'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
