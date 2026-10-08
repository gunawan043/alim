<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kehadiran Halaqah Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Halaqah</th>
                        <th>Tanggal</th>
                        <th>Sesi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->halaqah ?? '—' }}</td>
                            <td class="fs-13">{{ $item->attendance_date ? \Carbon\Carbon::parse($item->attendance_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="fs-13">{{ ucfirst($item->session_type ?? '—') }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'hadir' => 'success', 'izin' => 'info', 'sakit' => 'warning', 'alpa' => 'danger', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-check-line', 'title' => 'Belum ada kehadiran halaqah'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
