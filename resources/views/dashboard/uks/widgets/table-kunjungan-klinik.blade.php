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
                        <th>Keluhan</th>
                        <th>Diagnosa</th>
                        <th>Rujukan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->complaint ?? '—', 30) }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->diagnosis ?? '—', 30) }}</td>
                            <td class="fs-13">
                                @if ($item->is_referred)
                                    <span class="badge bg-danger-subtle text-danger">{{ $item->referral_hospital ?? 'dirujuk' }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->visit_date ? \Carbon\Carbon::parse($item->visit_date)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-stethoscope-line', 'title' => 'Belum ada kunjungan klinik'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
