<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Target Hafalan' }}</h4>
        <span class="badge bg-warning-subtle text-warning">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Halaqah</th>
                        <th class="text-center">Semester</th>
                        <th class="text-center">Target Halaman</th>
                        <th class="text-center">Hadits</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->halaqah ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->semester ?? '—' }}</td>
                            <td class="text-center fw-semibold">{{ $item->target_halaman ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->target_hadits ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-focus-3-line', 'title' => 'Belum ada target hafalan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
