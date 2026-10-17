<div class="card card-height-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $data['label'] ?? 'Jadwal Ekstrakurikuler' }}</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Ekskul</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Pembina</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="fs-13">
                                @php $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Ahad']; @endphp
                                {{ $days[$item->schedule_day] ?? '—' }}
                            </td>
                            <td class="fs-13">
                                {{ $item->schedule_time_start ? substr((string) $item->schedule_time_start, 0, 5) : '—' }}
                            </td>
                            <td class="fs-13">{{ $item->pembina ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-trophy-line', 'title' => 'Belum ada jadwal ekstrakurikuler'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
