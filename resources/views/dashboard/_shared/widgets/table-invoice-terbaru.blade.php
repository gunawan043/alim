<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Invoice Terbaru' }}</h4>
        <span class="badge bg-info-subtle text-info">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>No. Invoice</th>
                        <th>Vendor</th>
                        <th class="text-end">Nilai</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->invoice_number ?? $item->approval_number ?? '—' }}
                                @if ($item->approval_number && $item->invoice_number)
                                    <br><small class="text-muted">{{ $item->approval_number }}</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->vendor ?? '—' }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) ($item->total_amount ?? 0), 0, ',', '.') }}</td>
                            <td class="fs-13">{{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->translatedFormat('d M Y') : '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'paid'                  => 'success',
                                        'rejected', 'cancelled' => 'danger',
                                        'approved', 'verified'  => 'info',
                                        default                 => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-file-list-2-line', 'title' => 'Belum ada invoice masuk'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
