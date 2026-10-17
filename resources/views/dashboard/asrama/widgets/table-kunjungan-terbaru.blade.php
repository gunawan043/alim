<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kunjungan Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Tamu</th>
                        <th>Hubungan</th>
                        <th>Tujuan</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri ?? '—' }}</td>
                            <td class="fs-13">{{ $item->visitor_name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->visitor_relationship ?? '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->purpose ?? '—', 25) }}</td>
                            <td class="fs-13">{{ $item->expected_arrival_datetime ? \Carbon\Carbon::parse($item->expected_arrival_datetime)->translatedFormat('d M H:i') : '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'checked_out' => 'success',
                                        'arrived'     => 'info',
                                        'rejected', 'cancelled', 'no_show' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-shared-line', 'title' => 'Belum ada kunjungan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
