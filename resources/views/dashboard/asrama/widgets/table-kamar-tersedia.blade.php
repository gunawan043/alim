<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kamar Masih Tersedia' }}</h4>
        <span class="badge bg-success-subtle text-success">{{ count($data['items'] ?? []) }} kamar</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Kamar</th>
                        <th>Asrama</th>
                        <th class="text-center">Kapasitas</th>
                        <th class="text-center">Terisi</th>
                        <th class="text-center">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->kamar }}</td>
                            <td class="fs-13">{{ $item->asrama ?? '—' }}</td>
                            <td class="text-center">{{ $item->kapasitas }}</td>
                            <td class="text-center">{{ $item->terisi }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $item->sisa }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-door-open-line', 'title' => 'Semua kamar penuh'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
