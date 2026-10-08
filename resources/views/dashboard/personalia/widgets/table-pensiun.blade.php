<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Rencana Pensiun' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Tanggal Pensiun</th><th>Jenis</th><th>No. SK</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">{{ $item->planned_pension_date ? \Carbon\Carbon::parse($item->planned_pension_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ $item->pension_type ?? '—' }}</td>
                            <td class="fs-13">{{ $item->pension_letter_no ?? '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->pension_status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-logout-circle-line', 'title' => 'Belum ada rencana pensiun'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
