<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Event UTHQ Aktif' }}</h4>
        <span class="badge bg-info-subtle text-info">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Event</th>
                        <th>Periode</th>
                        <th>Lokasi</th>
                        <th class="text-center">Peserta</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">
                                {{ $item->event_date_start ? \Carbon\Carbon::parse($item->event_date_start)->translatedFormat('d M') : '—' }}
                                –
                                {{ $item->event_date_end ? \Carbon\Carbon::parse($item->event_date_end)->translatedFormat('d M') : '—' }}
                            </td>
                            <td class="fs-13">{{ $item->location ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $item->peserta }}</span></td>
                            <td class="fs-13">{{ ucfirst($item->status ?? '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-trophy-line', 'title' => 'Tidak ada event UTHQ aktif'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
