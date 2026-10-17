<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Perizinan Santri Terbaru' }}</h4>
        <span class="badge bg-warning-subtle text-warning">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Jenis</th>
                        <th>Keluar</th>
                        <th>Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">{{ $item->permit_type ?? '—' }}</td>
                            <td class="fs-13">
                                {{ $item->departure_datetime ? \Carbon\Carbon::parse($item->departure_datetime)->translatedFormat('d M H:i') : '—' }}
                            </td>
                            <td class="fs-13">
                                @if ($item->actual_return_datetime)
                                    {{ \Carbon\Carbon::parse($item->actual_return_datetime)->translatedFormat('d M H:i') }}
                                @else
                                    <span class="text-muted">Rencana:
                                        {{ $item->expected_return_datetime ? \Carbon\Carbon::parse($item->expected_return_datetime)->translatedFormat('d M H:i') : '—' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'approved' => 'info',
                                        'returned' => 'success',
                                        'overdue'  => 'danger',
                                        'rejected' => 'secondary',
                                        default    => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-walk-line', 'title' => 'Belum ada data perizinan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
