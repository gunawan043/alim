@extends('layouts.master')
@section('title', 'Detail Rekomendasi')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.rvr.index') }}">Repair vs Replace</a> @endslot
    @slot('title') {{ $asset->asset_code }} @endslot
@endcomponent

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5>{{ $asset->asset_name }}</h5>
                <p class="text-muted">Kode: <code>{{ $asset->asset_code }}</code></p>
                <table class="table table-borderless table-sm">
                    <tr><th>Kategori</th><td>{{ $asset->category->name ?? '—' }}</td></tr>
                    <tr><th>Lokasi</th><td>{{ $asset->room?->building?->building_name ?? '—' }} / {{ $asset->room?->room_name ?? '—' }}</td></tr>
                    <tr><th>Kondisi</th><td>{{ ucfirst(str_replace('_', ' ', $asset->condition ?? '—')) }}</td></tr>
                    <tr><th>Tgl Perolehan</th><td>{{ optional($asset->acquisition_date)->format('Y-m-d') ?? '—' }}</td></tr>
                    <tr><th>Harga Perolehan</th><td>Rp {{ number_format((float) $asset->acquisition_price, 0, ',', '.') }}</td></tr>
                </table>

                @php
                    $r = $evaluation['recommendation'];
                    $color = match($r) { 'GOOD'=>'success','MONITOR'=>'info','REPAIR'=>'warning','REPLACE'=>'danger',default=>'dark' };
                @endphp
                <div class="alert alert-{{ $color }} mt-3">
                    <h4 class="mb-1">{{ $r }}</h4>
                    <p class="mb-0">Skor kesehatan: <strong>{{ $evaluation['score'] }}/100</strong></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6>Faktor Penilaian</h6></div>
            <div class="card-body">
                <canvas id="factorChart" height="160"></canvas>
                <hr>
                <div class="row text-center">
                    @foreach([
                        'Kondisi' => 35, 'Frekuensi' => 20,
                        'Rasio Biaya' => 20, 'Usia' => 15, 'Ketersediaan' => 10
                    ] as $label => $weight)
                    <div class="col">
                        <strong>{{ $label }}</strong>
                        <p class="text-muted mb-0">{{ $weight }}%</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6>Detail Faktor</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    @php
                        $f = $evaluation['factors'];
                    @endphp
                    <tr><th>Usia</th><td>{{ $f['age_years'] }} tahun (estimasi masa pakai {{ $f['expected_life_years'] }} tahun)</td></tr>
                    <tr><th>Rasio Usia</th><td>{{ round($f['age_ratio'] * 100, 1) }}%</td></tr>
                    <tr><th>Jumlah Perbaikan</th><td>{{ $f['repair_count'] }} ({{ $f['repairs_per_year'] }}/tahun)</td></tr>
                    <tr><th>Total Biaya Perbaikan</th><td>Rp {{ number_format($f['historical_repair_cost'], 0, ',', '.') }}</td></tr>
                    <tr><th>Nilai Penggantian</th><td>Rp {{ number_format($f['replacement_value'], 0, ',', '.') }}</td></tr>
                    <tr><th>Rasio Biaya</th><td>{{ round($f['cost_ratio'] * 100, 1) }}%</td></tr>
                    <tr><th>Ketersediaan</th><td>{{ $f['availability_pct'] }}%</td></tr>
                    <tr><th>Kritisitas</th><td>{{ $f['criticality'] }}/5</td></tr>
                </table>
            </div>
        </div>

        @if($recommendation && $recommendation->rationale)
        <div class="card mt-3">
            <div class="card-header"><h6>Rasional</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($recommendation->rationale as $r)
                <li class="list-group-item">{{ $r }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection