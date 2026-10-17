<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Rekap Absensi Bulanan per Kamar' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ now()->translatedFormat('M Y') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Kamar</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Alpa</th>
                        <th class="text-center">Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->kamar }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $item->hadir }}</span></td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $item->izin }}</span></td>
                            <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $item->sakit }}</span></td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $item->alpa }}</span></td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $item->pulang }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-file-chart-line', 'title' => 'Belum ada rekap absensi bulan ini'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
