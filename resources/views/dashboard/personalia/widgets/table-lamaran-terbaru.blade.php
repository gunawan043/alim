<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Lamaran Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13"><th>No. Lamaran</th><th>Lowongan</th><th>Status</th><th class="text-center">Nilai</th><th>Tanggal</th></tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->no_lamaran ?? '—' }}</td>
                            <td class="fs-13">{{ \Illuminate\Support\Str::limit($item->lowongan ?? '—', 30) }}</td>
                            <td class="fs-13">{{ ucfirst($item->status_akhir ?? $item->status ?? '—') }}</td>
                            <td class="text-center fs-13">
                                {{ $item->nilai_akhir ?? '—' }}
                                @if ($item->ranking)
                                    <br><small class="text-muted">Rank {{ $item->ranking }}</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->tanggal_melamar ? \Carbon\Carbon::parse($item->tanggal_melamar)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">@include('dashboard._shared.widgets._empty', ['icon' => 'ri-file-user-line', 'title' => 'Belum ada lamaran'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
