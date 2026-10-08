<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Pengajuan Cuti / Izin GTK' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->name }}
                                @if ($item->alasan)
                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->alasan, 40) }}</small>
                                @endif
                            </td>
                            <td class="fs-13">
                                {{ $item->tanggal_mulai ? \Carbon\Carbon::parse($item->tanggal_mulai)->translatedFormat('d M') : '—' }}
                                –
                                {{ $item->tanggal_selesai ? \Carbon\Carbon::parse($item->tanggal_selesai)->translatedFormat('d M') : '—' }}
                            </td>
                            <td class="fs-13">{{ $item->jumlah_hari ?? '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default    => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-check-line', 'title' => 'Belum ada pengajuan cuti/izin'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
