<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Usulan Jabatan' }}</h4>
        <span class="badge bg-warning-subtle text-warning">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Usulan</th><th>Diajukan</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">{{ $item->jabatan_usulan ?? $item->proposed_jabatan_text ?? '—' }}</td>
                            <td class="fs-13">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') : '—' }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'submitted' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-follow-line', 'title' => 'Belum ada usulan jabatan'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
