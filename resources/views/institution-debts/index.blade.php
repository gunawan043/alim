@extends('layouts.master')
@section('title', 'Hutang Lembaga')

@section('css')
    <link href="{{ URL::asset('build/libs/apexcharts/apexcharts.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .debt-kpi{transition:all .3s ease}.debt-kpi:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.10)}
        .debt-kpi .avatar-sm{width:40px;height:40px}.debt-kpi .avatar-title{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:18px}
        .debt-chart{min-height:320px}.table-container{position:relative;width:100%;overflow-x:auto}
        .table-freeze{table-layout:auto;min-width:max-content;margin-bottom:0;width:100%}
        .table-freeze th,.table-freeze td{white-space:normal;vertical-align:middle;padding:12px 16px;word-break:break-word}
        .debt-progress{height:8px;border-radius:999px;background:#eef2f7;overflow:hidden}.debt-progress span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#0a5f9e,#38bdf8)}
        .debt-insight{border-left:4px solid #0a5f9e;background:#f8fafc}
        .debt-table-scroll{max-height:520px;overflow:auto}
        .debt-table-scroll thead th{position:sticky;top:0;z-index:2}
    </style>
@endsection

@section('content')
    @php
        $summary = $debt['summary'] ?? [];
        $categories = $debt['categories'] ?? [];
        $bump = collect($categories)->firstWhere('key', 'bump') ?? [];
        $siplah = collect($categories)->firstWhere('key', 'siplah') ?? [];
        $totalOutstanding = (float) ($summary['total_outstanding'] ?? 0);
        $bumpShare = $totalOutstanding > 0 ? ((float) ($summary['bump_outstanding'] ?? 0) / $totalOutstanding) * 100 : 0;
        $siplahShare = $totalOutstanding > 0 ? ((float) ($summary['siplah_outstanding'] ?? 0) / $totalOutstanding) * 100 : 0;
        $lastMonth = collect($debt['monthly'] ?? [])->last();
        $collectionRate = (float) ($summary['collection_rate'] ?? 0);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Sarpras @endslot
        @slot('title') Hutang Lembaga @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($error)<div class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>{{ $error }}</div>@endif
    <div class="alert alert-info border-0 py-2 small mb-3">
        <i class="ri-information-line me-1"></i><strong>Hutang BUMP</strong> adalah tagihan reguler lembaga di BUMP. <strong>Hutang SIPLah</strong> adalah tagihan hasil pemenuhan pengadaan SIPLah. Pelunasan dan perubahan status dilakukan di BUMP.
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('user.institution-debts.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-lg-4 col-md-5">
                    <label class="form-label small mb-1">Satuan Kerja</label>
                    <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua satuan kerja</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->code }}" @selected($selectedUnit?->code === $unit->code)>{{ $unit->name }} ({{ $unit->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small mb-1">Periode Tren</label>
                    <select name="months" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach([3 => '3 bulan', 6 => '6 bulan', 12 => '12 bulan', 24 => '24 bulan'] as $value => $label)
                            <option value="{{ $value }}" @selected((int) $months === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <a href="{{ route('user.institution-debts.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line me-1"></i>Reset</a>
                </div>
                <div class="col text-md-end small text-muted">
                    @if($debt['generated_at'] ?? null)
                        <i class="ri-time-line me-1"></i>Data BUMP: {{ \Illuminate\Support\Carbon::parse($debt['generated_at'])->locale('id')->translatedFormat('d M Y H:i') }}
                    @else
                        Data belum tersinkron
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Sisa Hutang', 'Rp '.number_format($totalOutstanding, 0, ',', '.'), 'ri-money-dollar-circle-line', 'primary', 'Seluruh hutang aktif'],
            ['Hutang BUMP', 'Rp '.number_format((float) ($summary['bump_outstanding'] ?? 0), 0, ',', '.'), 'ri-store-3-line', 'info', number_format($bumpShare, 1, ',', '.').'% dari total'],
            ['Hutang SIPLah', 'Rp '.number_format((float) ($summary['siplah_outstanding'] ?? 0), 0, ',', '.'), 'ri-file-list-3-line', 'purple', number_format($siplahShare, 1, ',', '.').'% dari total'],
            ['Lewat Jatuh Tempo', 'Rp '.number_format((float) ($summary['overdue_amount'] ?? 0), 0, ',', '.'), 'ri-alarm-warning-line', 'danger', ($summary['overdue_items'] ?? 0).' item'],
            ['Item Belum Lunas', number_format((int) ($summary['open_items'] ?? 0), 0, ',', '.'), 'ri-file-list-line', 'warning', 'Rata-rata Rp '.number_format((float) ($summary['average_outstanding'] ?? 0), 0, ',', '.')],
            ['Satuan Kerja', number_format((int) ($summary['units_count'] ?? 0), 0, ',', '.'), 'ri-building-line', 'success', 'Memiliki hutang aktif'],
        ] as [$label, $value, $icon, $color, $hint])
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card debt-kpi h-100 mb-0"><div class="card-body py-3">
                    <div class="d-flex align-items-start gap-2">
                        <span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }}"><i class="{{ $icon }}"></i></span></span>
                        <div class="min-w-0">
                            <div class="text-muted small">{{ $label }}</div>
                            <h5 class="fw-semibold mb-1 text-truncate" title="{{ $value }}">{{ $value }}</h5>
                            <div class="text-muted" style="font-size:.7rem">{{ $hint }}</div>
                        </div>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-pie-chart-2-line me-1 text-primary"></i>Komposisi Hutang</h5></div>
                <div class="card-body"><div id="debtCompositionChart" class="debt-chart"></div></div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><h5 class="card-title mb-0"><i class="ri-line-chart-line me-1 text-primary"></i>Tren Hutang & Pembayaran</h5><p class="text-muted small mb-0">Pembentukan hutang baru dan pembayaran per bulan.</p></div>
                    <span class="badge bg-primary-subtle text-primary">{{ $months }} bulan terakhir</span>
                </div>
                <div class="card-body"><div id="debtTrendChart" class="debt-chart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-bar-chart-horizontal-line me-1 text-primary"></i>Analisis Umur Hutang</h5></div>
                <div class="card-body"><div id="debtAgingChart" class="debt-chart"></div></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-building-4-line me-1 text-primary"></i>Hutang per Satuan Kerja</h5></div>
                <div class="card-body"><div id="debtUnitChart" class="debt-chart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-lightbulb-flash-line me-1 text-warning"></i>Insight & Kolektibilitas</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2"><span class="text-muted small">Rasio pembayaran hutang aktif</span><strong>{{ number_format($collectionRate, 1, ',', '.') }}%</strong></div>
                    <div class="debt-progress mb-3"><span style="width: {{ min(max($collectionRate, 0), 100) }}%"></span></div>
                    <div class="row g-2 mb-3">
                        @foreach($debt['statuses'] ?? [] as $status)
                            <div class="col-sm-6"><div class="border rounded p-2 h-100"><div class="small text-muted">{{ $status['label'] ?? 'Status' }}</div><div class="fw-semibold">Rp {{ number_format((float) ($status['total'] ?? 0), 0, ',', '.') }}</div><div class="text-muted" style="font-size:.7rem">{{ number_format((int) ($status['items'] ?? 0), 0, ',', '.') }} item · BUMP Rp {{ number_format((float) ($status['bump'] ?? 0), 0, ',', '.') }} · SIPLah Rp {{ number_format((float) ($status['siplah'] ?? 0), 0, ',', '.') }}</div></div></div>
                        @endforeach
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6"><div class="p-3 rounded bg-light"><div class="text-muted small">Nilai tercatat (aktif)</div><div class="fw-semibold">Rp {{ number_format((float) ($summary['total_amount'] ?? 0), 0, ',', '.') }}</div></div></div>
                        <div class="col-sm-6"><div class="p-3 rounded bg-light"><div class="text-muted small">Sudah dibayar (aktif)</div><div class="fw-semibold">Rp {{ number_format((float) ($summary['total_paid'] ?? 0), 0, ',', '.') }}</div></div></div>
                    </div>
                    <div class="debt-insight p-3 rounded">
                        <div class="mb-2"><i class="ri-checkbox-circle-line text-primary me-1"></i>Hutang BUMP <strong>{{ number_format($bumpShare, 1, ',', '.') }}%</strong>, SIPLah <strong>{{ number_format($siplahShare, 1, ',', '.') }}%</strong> dari total sisa hutang.</div>
                        <div class="mb-2"><i class="ri-alarm-warning-line text-danger me-1"></i><strong>{{ number_format((int) ($summary['overdue_items'] ?? 0), 0, ',', '.') }}</strong> item lewat jatuh tempo senilai <strong>Rp {{ number_format((float) ($summary['overdue_amount'] ?? 0), 0, ',', '.') }}</strong>.</div>
                        <div><i class="ri-calendar-check-line text-success me-1"></i>Pembayaran bulan ini <strong>Rp {{ number_format((float) ($lastMonth['total_paid'] ?? 0), 0, ',', '.') }}</strong>; {{ number_format((int) ($summary['due_soon_items'] ?? 0), 0, ',', '.') }} item jatuh tempo dalam 7 hari.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-calendar-todo-line me-1 text-primary"></i>Jatuh Tempo Terdekat</h5><p class="text-muted small mb-0">Hutang yang jatuh tempo dalam 14 hari.</p></div>
                <div class="card-body p-0">
                    <div class="table-responsive debt-table-scroll">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Hutang</th><th>Jenis</th><th class="text-end">Sisa</th></tr></thead>
                            <tbody>
                            @forelse($debt['due_soon'] ?? [] as $due)
                                <tr>
                                    <td><div class="fw-medium">{{ \Illuminate\Support\Str::limit($due['description'] ?? 'Hutang lembaga', 38) }}</div><div class="small text-muted">{{ $due['unit'] ?? '—' }} · {{ isset($due['due_date']) ? \Illuminate\Support\Carbon::parse($due['due_date'])->locale('id')->translatedFormat('d M Y') : '—' }}</div></td>
                                    <td><span class="badge bg-{{ ($due['category_label'] ?? '') === 'SIPLah' ? 'purple' : 'info' }}-subtle text-{{ ($due['category_label'] ?? '') === 'SIPLah' ? 'purple' : 'info' }}">{{ $due['category_label'] ?? '—' }}</span></td>
                                    <td class="text-end fw-semibold">Rp {{ number_format((float) ($due['outstanding'] ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada hutang yang jatuh tempo dalam 14 hari.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1 text-primary"></i>Rincian Hutang Aktif</h5><p class="text-muted small mb-0">Rincian per item hutang lembaga dari BUMP. Maksimal 250 item terdekat.</p></div>
            <div class="d-flex gap-2">
                <input id="debtSearch" class="form-control form-control-sm" style="min-width:220px" placeholder="Cari nomor, unit, keterangan...">
                <select id="debtCategoryFilter" class="form-select form-select-sm" style="min-width:140px">
                    <option value="">Semua jenis</option>
                    <option value="bump">BUMP</option>
                    <option value="siplah">SIPLah</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <div class="debt-table-scroll">
                    <table class="table table-hover table-freeze align-middle" id="debtTable">
                        <thead class="table-light"><tr><th>Nomor</th><th>Jenis</th><th>Satuan Kerja</th><th>Keterangan</th><th>Jatuh Tempo</th><th class="text-end">Nilai</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
                        <tbody id="debtTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted small d-flex justify-content-between">
            <span id="debtTableInfo">Menampilkan 0 item</span>
            <span>Data resmi dan pelunasan diproses di BUMP.</span>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const debt = @json($debt);
    const monthly = debt.monthly || [];
    const aging = debt.aging || [];
    const units = debt.units || [];
    const items = debt.items || [];
    const rupiah = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const shortRupiah = (value) => {
        const number = Number(value || 0);
        if (number >= 1000000000) return 'Rp ' + (number / 1000000000).toFixed(1) + ' M';
        if (number >= 1000000) return 'Rp ' + (number / 1000000).toFixed(1) + ' jt';
        if (number >= 1000) return 'Rp ' + (number / 1000).toFixed(0) + ' rb';
        return rupiah(number);
    };

    if (window.ApexCharts) {
        const composition = document.querySelector('#debtCompositionChart');
        if (composition) {
            const categoryData = debt.categories || [];
            new ApexCharts(composition, {
                chart: { type: 'donut', height: 330 },
                series: categoryData.map((row) => Number(row.outstanding || 0)),
                labels: categoryData.map((row) => row.label),
                colors: ['#0a5f9e', '#7c3aed'],
                legend: { position: 'bottom' },
                dataLabels: { enabled: true, formatter: (value) => Number(value).toFixed(1) + '%' },
                tooltip: { y: { formatter: rupiah } },
                plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => shortRupiah(debt.summary?.total_outstanding || 0) } } } } },
                noData: { text: 'Belum ada hutang aktif' },
            }).render();
        }

        const trend = document.querySelector('#debtTrendChart');
        if (trend) {
            new ApexCharts(trend, {
                chart: { type: 'bar', stacked: true, height: 330, toolbar: { show: false } },
                series: [
                    { name: 'Hutang BUMP', type: 'column', data: monthly.map((row) => Number(row.bump_new || 0)) },
                    { name: 'Hutang SIPLah', type: 'column', data: monthly.map((row) => Number(row.siplah_new || 0)) },
                    { name: 'Pembayaran', type: 'line', data: monthly.map((row) => Number(row.total_paid || 0)) },
                ],
                colors: ['#0a5f9e', '#7c3aed', '#16a34a'],
                stroke: { width: [0, 0, 3], curve: 'smooth' },
                plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
                xaxis: { categories: monthly.map((row) => row.label), labels: { rotate: -30, style: { fontSize: '11px' } } },
                yaxis: { labels: { formatter: shortRupiah } },
                legend: { position: 'top' },
                tooltip: { shared: true, intersect: false, y: { formatter: rupiah } },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
            }).render();
        }

        const agingChart = document.querySelector('#debtAgingChart');
        if (agingChart) {
            new ApexCharts(agingChart, {
                chart: { type: 'bar', stacked: true, height: 320, toolbar: { show: false } },
                series: [
                    { name: 'BUMP', data: aging.map((row) => Number(row.bump || 0)) },
                    { name: 'SIPLah', data: aging.map((row) => Number(row.siplah || 0)) },
                ],
                colors: ['#0a5f9e', '#7c3aed'],
                plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
                xaxis: { categories: aging.map((row) => row.label), labels: { style: { fontSize: '11px' } } },
                yaxis: { labels: { formatter: shortRupiah } },
                legend: { position: 'top' },
                tooltip: { y: { formatter: rupiah } },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
            }).render();
        }

        const unitChart = document.querySelector('#debtUnitChart');
        if (unitChart) {
            const topUnits = units.slice(0, 8);
            new ApexCharts(unitChart, {
                chart: { type: 'bar', stacked: true, height: 320, toolbar: { show: false } },
                series: [
                    { name: 'BUMP', data: topUnits.map((row) => Number(row.bump || 0)) },
                    { name: 'SIPLah', data: topUnits.map((row) => Number(row.siplah || 0)) },
                ],
                colors: ['#0a5f9e', '#7c3aed'],
                plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4 } },
                xaxis: { categories: topUnits.map((row) => row.name), labels: { formatter: shortRupiah, style: { fontSize: '10px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                legend: { position: 'top' },
                tooltip: { y: { formatter: rupiah } },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
                noData: { text: 'Belum ada data satuan kerja' },
            }).render();
        }
    }

    const body = document.getElementById('debtTableBody');
    const search = document.getElementById('debtSearch');
    const category = document.getElementById('debtCategoryFilter');
    const info = document.getElementById('debtTableInfo');
    const statusBadge = (row) => {
        if (Number(row.overdue_days || 0) > 0) return '<span class="badge bg-danger-subtle text-danger">Lewat ' + Number(row.overdue_days) + ' hari</span>';
        return '<span class="badge bg-' + (row.status === 'partial' ? 'warning' : 'info') + '-subtle text-' + (row.status === 'partial' ? 'warning' : 'info') + '">' + escapeHtml(row.status_label || 'Terbuka') + '</span>';
    };
    const renderRows = () => {
        const query = (search?.value || '').toLowerCase().trim();
        const selectedCategory = category?.value || '';
        const rows = items.filter((row) => {
            const matchesCategory = !selectedCategory || row.category === selectedCategory;
            const haystack = [row.number, row.unit, row.unit_code, row.description, row.category_label].join(' ').toLowerCase();
            return matchesCategory && (!query || haystack.includes(query));
        });

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5">Tidak ada rincian hutang yang sesuai.</td></tr>';
        } else {
            body.innerHTML = rows.map((row) => '<tr>'
                + '<td class="font-monospace small">' + escapeHtml(row.number || '—') + '</td>'
                + '<td><span class="badge bg-' + (row.category === 'siplah' ? 'purple' : 'info') + '-subtle text-' + (row.category === 'siplah' ? 'purple' : 'info') + '">' + escapeHtml(row.category_label || '—') + '</span></td>'
                + '<td class="small"><div>' + escapeHtml(row.unit || '—') + '</div><div class="text-muted" style="font-size:.7rem">' + escapeHtml(row.unit_code || '') + '</div></td>'
                + '<td class="small">' + escapeHtml(row.description || '—') + '</td>'
                + '<td class="small text-muted">' + escapeHtml(row.due_date || '—') + '</td>'
                + '<td class="text-end small">' + rupiah(row.amount) + '</td>'
                + '<td class="text-end small">' + rupiah(row.paid) + '</td>'
                + '<td class="text-end fw-semibold">' + rupiah(row.outstanding) + '</td>'
                + '<td>' + statusBadge(row) + '</td>'
                + '</tr>').join('');
        }
        if (info) info.textContent = 'Menampilkan ' + rows.length + ' dari ' + items.length + ' item';
    };
    search?.addEventListener('input', renderRows);
    category?.addEventListener('change', renderRows);
    renderRows();
});
</script>
@endsection
