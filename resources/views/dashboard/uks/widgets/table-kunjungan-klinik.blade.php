<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kunjungan Klinik Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Antrean</th>
                        <th>Triase</th>
                        <th>Keluhan</th>
                        <th>Status</th>
                        <th>Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->queue_number ?? '—' }}</td>
                            <td class="fs-13">{{ $item->triage_level ?? '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->chief_complaint ?? '—', 30) }}</td>
                            <td class="fs-13">{{ str_replace('_', ' ', ucfirst($item->queue_status ?? '—')) }}</td>
                            <td class="fs-13">{{ $item->admitted_at ? \Carbon\Carbon::parse($item->admitted_at)->translatedFormat('d M Y H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-stethoscope-line', 'title' => 'Belum ada kunjungan klinik'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
