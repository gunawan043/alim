<div class="card card-height-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $data['label'] ?? 'Rekap Rombel & Santri per Tingkat' }}</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Tingkat</th>
                        <th class="text-center">Rombel</th>
                        <th class="text-center">Santri</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}</td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $item->rombel }}</span></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $item->santri }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-layout-grid-line', 'title' => 'Belum ada data tingkat/rombel'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
