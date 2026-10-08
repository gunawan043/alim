<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Saldo Cuti GTK' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Jenis</th><th class="text-center">Jatah</th><th class="text-center">Terpakai</th><th class="text-center">Sisa</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->jenis ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->jumlah_hari ?? 0 }}</td>
                            <td class="text-center fs-13">{{ $item->digunakan ?? 0 }}</td>
                            <td class="text-center"><span class="badge bg-{{ ($item->tersisa ?? 0) > 0 ? 'success' : 'danger' }}-subtle text-{{ ($item->tersisa ?? 0) > 0 ? 'success' : 'danger' }}">{{ $item->tersisa ?? 0 }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-line', 'title' => 'Belum ada saldo cuti'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
