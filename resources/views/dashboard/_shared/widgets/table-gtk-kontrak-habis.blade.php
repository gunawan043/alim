<div class="card card-height-100">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'SK / Kontrak Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Nama</th>
                        <th>Status</th>
                        <th class="text-end">Tgl SK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $item->status_kepegawaian ?? '—' }}
                                </span>
                            </td>
                            <td class="text-end text-muted fs-13">
                                {{ $item->decree_date ? \Carbon\Carbon::parse($item->decree_date)->translatedFormat('d M Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-file-text-line', 'title' => 'Belum ada data SK/kontrak'])
                        </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
