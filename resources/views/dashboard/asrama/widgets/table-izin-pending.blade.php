<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Izin Menunggu Persetujuan' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Kamar</th>
                        <th>Jenis</th>
                        <th>Keluar</th>
                        <th>Rencana Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->santri }}
                                @if ($item->purpose)
                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->purpose, 40) }}</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->kamar ?? '—' }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $item->jenis }}</span></td>
                            <td class="fs-13">{{ $item->departure_datetime ? \Carbon\Carbon::parse($item->departure_datetime)->translatedFormat('d M H:i') : '—' }}</td>
                            <td class="fs-13">{{ $item->expected_return_datetime ? \Carbon\Carbon::parse($item->expected_return_datetime)->translatedFormat('d M H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-time-line', 'title' => 'Tidak ada izin menunggu'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
