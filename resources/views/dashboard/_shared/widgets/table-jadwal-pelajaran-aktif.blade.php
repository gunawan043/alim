<div class="card card-height-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $data['label'] ?? 'Jadwal Pelajaran Aktif' }}</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Jam</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Guru</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fs-13">{{ substr((string) $item->start_time, 0, 5) }}–{{ substr((string) $item->end_time, 0, 5) }}</td>
                            <td class="fw-medium">{{ $item->kelas ?? '—' }}</td>
                            <td class="fs-13">{{ $item->mapel ?? '—' }}</td>
                            <td class="fs-13">{{ $item->guru ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-line', 'title' => 'Tidak ada jadwal aktif hari ini'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
