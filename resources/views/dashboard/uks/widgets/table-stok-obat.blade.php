<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Stok Obat' }}</h4>
        <span class="badge bg-info-subtle text-info">{{ count($data['items'] ?? []) }} item</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Obat</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Min</th>
                        <th>Kadaluarsa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr class="{{ $item->current_stock <= $item->min_stock_alert ? 'table-warning' : '' }}">
                            <td class="fw-medium">
                                {{ $item->medicine_name }}
                                @if ($item->unit)
                                    <small class="text-muted">({{ $item->unit }})</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->category ?? '—' }}</td>
                            <td class="text-center fw-semibold">{{ $item->current_stock }}</td>
                            <td class="text-center fs-13">{{ $item->min_stock_alert }}</td>
                            <td class="fs-13">
                                {{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->translatedFormat('d M Y') : '—' }}
                                @if ($item->expiry_date && \Carbon\Carbon::parse($item->expiry_date)->isPast())
                                    <span class="badge bg-danger-subtle text-danger ms-1">lewat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-capsule-line', 'title' => 'Belum ada data stok obat'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
