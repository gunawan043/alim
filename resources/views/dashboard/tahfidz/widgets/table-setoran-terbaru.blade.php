<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Setoran Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Halaqah</th>
                        <th>Materi</th>
                        <th class="text-center">Nilai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">{{ $item->halaqah ?? '—' }}</td>
                            <td class="fs-13">
                                @if ($item->surah)
                                    {{ $item->surah }}: {{ $item->ayat_start }}–{{ $item->ayat_end }}
                                @elseif ($item->juz)
                                    Juz {{ $item->juz }}
                                @else
                                    —
                                @endif
                                <br><small class="text-muted">{{ ucfirst($item->setoran_type) }} · {{ $item->setoran_date ? \Carbon\Carbon::parse($item->setoran_date)->translatedFormat('d M') : '' }}</small>
                            </td>
                            <td class="text-center fw-semibold">{{ $item->nilai_setoran ?? '—' }}</td>
                            <td>
                                @php $badge = match ($item->status) { 'lulus' => 'success', 'ulang' => 'warning', 'ditunda' => 'danger', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-book-mark-line', 'title' => 'Belum ada setoran'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
