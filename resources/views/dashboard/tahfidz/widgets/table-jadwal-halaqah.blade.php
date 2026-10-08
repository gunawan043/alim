<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Jadwal Halaqah' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ count($data['items'] ?? []) }} jadwal</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Halaqah</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Ruang</th>
                        <th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->halaqah }}</td>
                            <td class="fs-13">{{ $item->hari }}</td>
                            <td class="fs-13">{{ substr((string) $item->time_start, 0, 5) }}–{{ substr((string) $item->time_end, 0, 5) }}</td>
                            <td class="fs-13">{{ $item->room ?? '—' }}</td>
                            <td class="fs-13">{{ ucfirst($item->schedule_type ?? 'rutin') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-line', 'title' => 'Belum ada jadwal halaqah'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
