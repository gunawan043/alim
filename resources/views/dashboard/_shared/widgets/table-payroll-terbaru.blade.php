<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Payroll Terbaru' }}</h4>
        <span class="badge bg-success-subtle text-success">10 terbaru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Nama GTK</th>
                        <th>Periode</th>
                        <th class="text-end">Gaji Bersih</th>
                        <th>Status</th>
                        <th>Tgl Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->name ?? '—' }}</td>
                            <td class="fs-13">
                                @php
                                    $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                                @endphp
                                {{ $months[$item->bulan] ?? $item->bulan }} {{ $item->tahun }}
                            </td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) ($item->gaji_bersih ?? 0), 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'dibayar', 'paid', 'selesai' => 'success',
                                        'draft'                      => 'secondary',
                                        default                      => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status ?? '—' }}</span>
                            </td>
                            <td class="fs-13">{{ $item->tanggal_bayar ? \Carbon\Carbon::parse($item->tanggal_bayar)->translatedFormat('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-money-dollar-circle-line', 'title' => 'Belum ada data payroll'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
