<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Peserta UTHQ' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>No</th>
                        <th>Santri</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th class="text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fs-13">{{ $item->nomor_peserta ?? '—' }}</td>
                            <td class="fw-medium">
                                {{ $item->santri }}
                                <br><small class="text-muted">{{ $item->event ?? '—' }}</small>
                            </td>
                            <td class="fs-13">{{ $item->kategori ?? '—' }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'finalis' => 'success', 'lolos_audisi' => 'info', 'terdaftar' => 'warning', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ str_replace('_', ' ', ucfirst((string) $item->status)) }}</span>
                            </td>
                            <td class="text-center fs-13">
                                {{ $item->nilai_akhir ?? '—' }}
                                @if ($item->ranking)
                                    <br><small class="text-muted">Rank {{ $item->ranking }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-star-line', 'title' => 'Belum ada peserta UTHQ'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
