<div class="card card-height-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $data['label'] ?? 'Jadwal Supervisi' }}</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Mapel</th>
                        <th>Pengawas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fs-13">
                                {{ $item->tanggal_supervisi ? \Carbon\Carbon::parse($item->tanggal_supervisi)->translatedFormat('d M') : '—' }}
                                <br><small class="text-muted">{{ substr((string) $item->jam_mulai, 0, 5) }}</small>
                            </td>
                            <td class="fw-medium">{{ $item->gtk_name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->mata_pelajaran ?? '—' }}</td>
                            <td class="fs-13">{{ $item->observer_name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $item->status === 'berlangsung' ? 'success' : 'info' }}-subtle text-{{ $item->status === 'berlangsung' ? 'success' : 'info' }}">{{ $item->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-presentation-line', 'title' => 'Belum ada jadwal supervisi'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
