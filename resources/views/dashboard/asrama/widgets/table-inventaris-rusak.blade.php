<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Inventaris Rusak / Hilang' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Kamar</th>
                        <th>Item</th>
                        <th class="text-center">Jumlah</th>
                        <th>Kondisi</th>
                        <th>Terakhir Dicek</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->kamar ?? '—' }}</td>
                            <td class="fs-13">{{ $item->item_name ?? '—' }}</td>
                            <td class="text-center fs-13">{{ $item->quantity ?? 0 }}</td>
                            <td>
                                <span class="badge bg-{{ $item->condition === 'hilang' ? 'danger' : 'warning' }}-subtle text-{{ $item->condition === 'hilang' ? 'danger' : 'warning' }}">
                                    {{ $item->condition }}
                                </span>
                            </td>
                            <td class="fs-13">{{ $item->last_checked_at ? \Carbon\Carbon::parse($item->last_checked_at)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-tools-line', 'title' => 'Semua inventaris dalam kondisi baik'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
