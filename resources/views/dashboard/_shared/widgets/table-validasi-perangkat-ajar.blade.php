<div class="card card-height-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $data['label'] ?? 'Perangkat Ajar Perlu Validasi' }}</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Guru</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->guru ?? '—' }}</td>
                            <td class="fs-13">{{ $item->mapel ?? '—' }}</td>
                            <td class="fs-13">{{ $item->kelas ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-check-double-line', 'title' => 'Semua perangkat ajar valid'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
