<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? "Hasil Tasmi' Terbaru" }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Santri</th>
                        <th>Sesi</th>
                        <th class="text-center">Nilai Akhir</th>
                        <th>Predikat</th>
                        <th>Penguji</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->santri }}</td>
                            <td class="fs-13">
                                {{ $item->session_name ?? '—' }}
                                @if ($item->session_date)
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($item->session_date)->translatedFormat('d M Y') }}</small>
                                @endif
                            </td>
                            <td class="text-center fw-semibold">{{ $item->nilai_akhir ?? '—' }}</td>
                            <td>
                                @php $badge = match ($item->predikat) { 'mumtaz' => 'success', 'jayyid_jiddan' => 'primary', 'jayyid' => 'info', 'maqbul' => 'warning', 'rasib' => 'danger', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ str_replace('_', ' ', ucfirst((string) $item->predikat)) ?: '—' }}</span>
                            </td>
                            <td class="fs-13">{{ $item->penguji ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-medal-line', 'title' => "Belum ada hasil tasmi'"])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
