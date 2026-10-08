<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Progres Santri' }}</h4>
        <span class="badge bg-success-subtle text-success">Top 10</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th class="text-center">Juz</th>
                        <th class="text-center">Halaman</th>
                        <th class="text-center">Setoran</th>
                        <th class="text-center">Rata Nilai</th>
                        <th class="text-center">Target</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="text-center fw-semibold">{{ $item->total_juz_completed ?? 0 }}</td>
                            <td class="text-center fs-13">{{ $item->total_halaman_ziyadah ?? 0 }}</td>
                            <td class="text-center fs-13">{{ $item->total_setoran ?? 0 }}</td>
                            <td class="text-center fs-13">{{ $item->rata_rata_nilai ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $item->pencapaian_target_persen ?? 0 }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-line-chart-line', 'title' => 'Belum ada rekap progres'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
