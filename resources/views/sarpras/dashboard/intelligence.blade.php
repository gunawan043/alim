@extends('layouts.master')
@section('title', 'Intelligence Dashboard')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Intelligence Dashboard @endslot
@endcomponent

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">Total Aset</h6><h2>{{ $data['overview']['total_assets'] }}</h2><small>aktif: {{ $data['overview']['active_assets'] }}</small></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">TCO Total</h6><h2 class="text-primary">Rp {{ number_format($data['tco']['total'], 0, ',', '.') }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">Repair Open</h6><h2 class="text-warning">{{ $data['kpis']['open_repairs'] }}</h2><small>30d closed: {{ $data['kpis']['closed_repairs_30d'] }}</small></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">Spend 30d</h6><h2>Rp {{ number_format($data['kpis']['spend_30d'], 0, ',', '.') }}</h2></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Distribusi Rekomendasi Perbaikan/Ganti</h5></div>
            <div class="card-body">
                @foreach($data['rvr'] as $rec => $count)
                @php $color = match($rec){'GOOD'=>'success','MONITOR'=>'info','REPAIR'=>'warning','REPLACE'=>'danger',default=>'secondary'}; @endphp
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-{{ $color }}">{{ $rec }}</span>
                    <strong>{{ $count }}</strong>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Predictive Maintenance</h5></div>
            <div class="card-body">
                @php $r = $data['predictive']['risk_distribution']; @endphp
                <div class="row text-center g-2">
                    <div class="col"><div class="border rounded p-2"><h3 class="text-success mb-0">{{ $r['LOW'] }}</h3><small>LOW</small></div></div>
                    <div class="col"><div class="border rounded p-2"><h3 class="text-info mb-0">{{ $r['MEDIUM'] }}</h3><small>MED</small></div></div>
                    <div class="col"><div class="border rounded p-2"><h3 class="text-warning mb-0">{{ $r['HIGH'] }}</h3><small>HIGH</small></div></div>
                    <div class="col"><div class="border rounded p-2"><h3 class="text-danger mb-0">{{ $r['CRITICAL'] }}</h3><small>CRIT</small></div></div>
                </div>
                <hr>
                <div class="d-flex justify-content-between"><span>Avg MTBF</span><strong>{{ $data['predictive']['avg_mtbf_days'] }} hari</strong></div>
                <div class="d-flex justify-content-between"><span>Avg MTTR</span><strong>{{ $data['predictive']['avg_mttr_days'] }} hari</strong></div>
                <div class="d-flex justify-content-between"><span>Avg Resolution</span><strong>{{ $data['kpis']['avg_resolution_days'] }} hari</strong></div>
            </div>
        </div>
    </div>

    @if(!empty($data['high_risk_assets']))
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5>Top High-Risk Assets</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Risk</th><th>MTBF</th><th>Trend</th><th>Prediksi Gagal</th></tr></thead>
                    <tbody>
                    @foreach($data['high_risk_assets'] as $asset)
                        <tr>
                            <td><code>{{ $asset['asset_code'] }}</code></td>
                            <td>{{ $asset['asset_name'] }}</td>
                            <td><span class="badge bg-{{ $asset['risk_level'] === 'CRITICAL' ? 'danger' : 'warning' }}">{{ $asset['risk_level'] }}</span></td>
                            <td>{{ round($asset['mtbf_days']) }}d</td>
                            <td>{{ $asset['trend'] }}</td>
                            <td>{{ $asset['next_predicted_failure'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5>Spend Trend ({{ $months }} bulan)</h5></div>
            <div class="card-body">
                @if(count($data['spend_trend']) > 0)
                <table class="table table-sm">
                    <thead><tr><th>Bulan</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach($data['spend_trend'] as $row)
                        <tr><td>{{ $row['month'] }}</td><td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted mb-0">Belum ada data pengeluaran dalam {{ $months }} bulan terakhir.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection