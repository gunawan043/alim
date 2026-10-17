<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kunjungan Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Jenis</th>
                        <th>Keluhan</th>
                        <th>Masuk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ ucfirst($item->patient_type ?? '—') }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->chief_complaint ?? '—', 35) }}</td>
                            <td class="fs-13">{{ $item->admitted_at ? \Carbon\Carbon::parse($item->admitted_at)->translatedFormat('d M H:i') : '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'aktif'   => 'warning',
                                        'selesai' => 'success',
                                        'dirujuk' => 'danger',
                                        default   => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-check-line', 'title' => 'Belum ada kunjungan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
