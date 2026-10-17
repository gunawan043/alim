<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Kunjungan UKS Terbaru' }}</h4>
        <span class="badge bg-danger-subtle text-danger">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Keluhan</th>
                        <th>Diagnosa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->name }}
                                @if ($item->bed_number)
                                    <br><small class="text-muted">Bed {{ $item->bed_number }}</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->chief_complaint ?? '—', 40) }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->diagnosis ?? '—', 40) }}</td>
                            <td>
                                @if ($item->discharged_at)
                                    <span class="badge bg-success-subtle text-success">selesai</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">dirawat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-hospital-line', 'title' => 'Belum ada kunjungan UKS'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
