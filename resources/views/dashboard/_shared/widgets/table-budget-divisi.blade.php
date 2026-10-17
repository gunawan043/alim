<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Realisasi Anggaran per Divisi' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ now()->year }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Divisi</th>
                        <th class="text-end">Alokasi</th>
                        <th class="text-end">Terpakai</th>
                        <th class="text-end">Sisa</th>
                        <th style="min-width: 120px;">Realisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->nama }}</td>
                            <td class="text-end fs-13">Rp {{ number_format($item->alokasi, 0, ',', '.') }}</td>
                            <td class="text-end fs-13">Rp {{ number_format($item->terpakai, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($item->sisa, 0, ',', '.') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $item->persen > 90 ? 'danger' : ($item->persen > 70 ? 'warning' : 'success') }}"
                                             style="width: {{ min(100, $item->persen) }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $item->persen }}%</small>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-pie-chart-2-line', 'title' => 'Belum ada anggaran divisi'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
