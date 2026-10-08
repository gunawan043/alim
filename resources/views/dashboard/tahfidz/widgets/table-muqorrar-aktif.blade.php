<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Muqorrar Aktif' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Paket</th>
                        <th>Kelas</th>
                        <th>Bulan KBM</th>
                        <th class="text-center">Target Halaman</th>
                        <th class="text-center">Hari Aktif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->package_name ?? '—' }}</td>
                            <td class="fs-13">{{ $item->grade_class ?? '—' }}</td>
                            <td class="fs-13">{{ $item->bulan_kbm ?? '—' }} {{ $item->bulan_kbm_tahun ?? '' }}</td>
                            <td class="text-center fw-semibold">{{ $item->target_bulanan_halaman ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->jumlah_hari_aktif ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-book-2-line', 'title' => 'Belum ada muqorrar'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
