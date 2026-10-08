<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Cuti Menunggu Persetujuan' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>Nama</th><th>Jenis</th><th>Tanggal</th><th class="text-center">Hari</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name }}@if ($item->alasan)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->alasan, 40) }}</small>@endif</td>
                            <td class="fs-13">{{ $item->jenis ?? '—' }}</td>
                            <td class="fs-13">
                                {{ $item->tanggal_mulai ? \Carbon\Carbon::parse($item->tanggal_mulai)->translatedFormat('d M') : '—' }}
                                – {{ $item->tanggal_selesai ? \Carbon\Carbon::parse($item->tanggal_selesai)->translatedFormat('d M') : '—' }}
                            </td>
                            <td class="text-center fs-13">{{ $item->jumlah_hari ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-check-line', 'title' => 'Tidak ada cuti menunggu'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
