@extends('layouts.master')
@section('title') Asset Passport 2.0 — {{ $asset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.assets.passport', $asset->id) }}">Passport</a> @endslot
    @slot('title') Passport 2.0 @endslot
@endcomponent

<div class="alert alert-info d-flex justify-content-between align-items-center">
    <div>
        <i class="ri-rocket-2-line"></i>
        <strong>Passport 2.0</strong> — menambahkan Total Cost of Ownership, Rekomendasi Perbaikan/Ganti, dan Predictive Maintenance.
    </div>
    <span class="badge bg-info">v2</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">TCO Total</h6>
                <h4 class="mb-0 text-primary">Rp {{ number_format($passport['tco']['tco_total'] ?? 0, 0, ',', '.') }}</h4>
                <small class="text-muted">umur {{ $passport['tco']['useful_life_months'] ?? 0 }} bulan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Cost / Month</h6>
                <h4 class="mb-0 text-primary">Rp {{ number_format($passport['tco']['tco_per_month'] ?? 0, 0, ',', '.') }}</h4>
                <small class="text-muted">avg lifecycle</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @php
            $r = $passport['repair_vs_replace']['recommendation'] ?? 'UNKNOWN';
            $color = match($r) { 'GOOD'=>'success','MONITOR'=>'info','REPAIR'=>'warning','REPLACE'=>'danger',default=>'secondary' };
        @endphp
        <div class="card text-center border-{{ $color }}">
            <div class="card-body">
                <h6 class="text-muted">Recommendation</h6>
                <h4 class="mb-0 text-{{ $color }}">{{ $r }}</h4>
                <small class="text-muted">Skor: {{ $passport['repair_vs_replace']['score'] ?? '—' }}/100</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Health Score</h6>
                <h4 class="mb-0">{{ $passport['health']['score'] ?? '—' }}</h4>
                <small class="text-muted">{{ $passport['health']['status'] ?? '' }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Total Cost of Ownership</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    @foreach(['acquisition_price','acquisition_cost_total','maintenance_cost_total','repair_cost_total','downtime_cost_total','energy_cost_total','operational_cost_total','tco_total'] as $k)
                    <tr>
                        <th>{{ ucwords(str_replace('_',' ',$k)) }}</th>
                        <td class="text-end">Rp {{ number_format($passport['tco'][$k] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </table>
                @if(!empty($passport['tco']['breakeven']))
                <div class="alert alert-light">
                    <strong>Break-even:</strong> {{ $passport['tco']['breakeven'] }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Repair vs Replace Detail</h5></div>
            <div class="card-body">
                <h6 class="text-muted mb-2">Faktor</h6>
                <ul class="list-group list-group-flush mb-3">
                    @foreach($passport['repair_vs_replace']['factors'] ?? [] as $key => $val)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ ucwords(str_replace('_',' ',$key)) }}</span>
                        <code>{{ is_array($val) ? json_encode($val) : $val }}</code>
                    </li>
                    @endforeach
                </ul>

                @if(!empty($passport['repair_vs_replace']['rationale']))
                <h6 class="text-muted">Rasional</h6>
                <ul class="small">
                    @foreach($passport['repair_vs_replace']['rationale'] as $r)
                    <li>{{ $r }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($passport['predictive']))
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5>Predictive Maintenance</h5></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col"><strong>MTBF</strong><p class="mb-0 text-muted">{{ $passport['predictive']['mtbf_days'] ?? '—' }} hari</p></div>
                    <div class="col"><strong>Repairs/Month</strong><p class="mb-0 text-muted">{{ $passport['predictive']['repairs_per_month'] ?? '—' }}</p></div>
                    <div class="col"><strong>Trend</strong><p class="mb-0 text-muted">{{ $passport['predictive']['health_trend'] ?? '—' }}</p></div>
                </div>
                @if(!empty($passport['predictive']['recommendation']))
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="ri-alert-line"></i> {{ $passport['predictive']['recommendation'] }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<div class="text-center mt-4">
    <a href="{{ route('sarpras.assets.passport', $asset->id) }}" class="btn btn-outline-secondary">
        <i class="ri-arrow-left-line"></i> Kembali ke Passport Klasik
    </a>
</div>
@endsection