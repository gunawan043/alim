<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Rujukan Terbaru' }}</h4>
        <span class="badge bg-danger-subtle text-danger">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Diagnosa</th>
                        <th>Alasan Rujukan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->diagnosis ?? '—', 30) }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->referral_reason ?? '—', 40) }}</td>
                            <td class="fs-13">{{ $item->admitted_at ? \Carbon\Carbon::parse($item->admitted_at)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-hospital-line', 'title' => 'Belum ada rujukan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
