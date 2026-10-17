<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? "Mutaba'ah Terbaru" }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Halaqah</th>
                        <th>Tanggal</th>
                        <th class="text-center">Tilawah</th>
                        <th class="text-center">Tikror</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->halaqah ?? '—' }}</td>
                            <td class="fs-13">{{ $item->record_date ? \Carbon\Carbon::parse($item->record_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td class="text-center fs-13">{{ $item->tilawah_halaman ?? 0 }} hlm</td>
                            <td class="text-center fs-13">{{ $item->tikror_mandiri_halaman ?? 0 }} hlm</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-clipboard-line', 'title' => "Belum ada catatan mutaba'ah"])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
