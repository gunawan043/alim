<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Arsip GTK Belum Lengkap' }}</h4>
        <span class="badge bg-danger-subtle text-danger">{{ number_format($data['total'] ?? 0) }} GTK</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Field Kosong</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td><span class="badge bg-danger-subtle text-danger">{{ $item->missing }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-folder-check-line', 'title' => 'Semua arsip GTK lengkap'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
