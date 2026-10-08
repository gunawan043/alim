<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Permintaan Mutasi GTK' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Dari</th><th>Ke</th><th>Jabatan</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->dari ?? '—' }}</td>
                            <td class="fs-13">{{ $item->ke ?? '—' }}</td>
                            <td class="fs-13">{{ $item->jabatan ?? '—' }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-exchange-line', 'title' => 'Tidak ada permintaan mutasi'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
