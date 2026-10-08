<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Presensi GTK Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Tanggal</th><th>Masuk</th><th>Status</th><th class="text-center">Terlambat</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ $item->jam_masuk ? substr((string) $item->jam_masuk, 0, 5) : '—' }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'hadir' => 'success', 'izin' => 'info', 'sakit' => 'warning', 'alpa' => 'danger', 'cuti' => 'primary', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                            <td class="text-center fs-13">
                                @if (($item->terlambat_menit ?? 0) > 0)
                                    <span class="badge bg-warning-subtle text-warning">{{ $item->terlambat_menit }} mnt</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-fingerprint-line', 'title' => 'Belum ada presensi GTK'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
