<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Pasien Aktif' }}</h4>
        <span class="badge bg-danger-subtle text-danger">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Keluhan</th>
                        <th>Diagnosa</th>
                        <th class="text-center">Bed</th>
                        <th>Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->santri }}
                                @if ($item->in_bed)
                                    <span class="badge bg-warning-subtle text-warning ms-1">rawat inap</span>
                                @endif
                            </td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->chief_complaint ?? '—', 35) }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->diagnosis ?? '—', 35) }}</td>
                            <td class="text-center fs-13">{{ $item->bed_number ?? '—' }}</td>
                            <td class="fs-13">{{ $item->admitted_at ? \Carbon\Carbon::parse($item->admitted_at)->translatedFormat('d M H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-heart-line', 'title' => 'Tidak ada pasien aktif'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
