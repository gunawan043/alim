@extends('layouts.master')
@section('title', 'Predictive Maintenance')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Predictive Maintenance @endslot
@endcomponent

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fs-4 mb-1">Prediksi Kerusakan</h4>
        <p class="text-muted mb-0">MTBF, MTTR, dan tren untuk mencegah downtime.</p>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="level" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Risiko</option>
            @foreach(['LOW','MEDIUM','HIGH','CRITICAL'] as $lvl)
            <option value="{{ $lvl }}" @selected($filter === $lvl)>{{ $lvl }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Risk</th>
                    <th class="text-center">MTBF</th>
                    <th class="text-center">MTTR</th>
                    <th class="text-center">Repairs</th>
                    <th class="text-center">Trend</th>
                    <th class="text-center">Prediksi Gagal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($predictions as $p)
                @php
                    $color = match($p['risk_level']) { 'CRITICAL'=>'danger','HIGH'=>'warning','MEDIUM'=>'info',default=>'success' };
                    $trendIcon = match($p['trend']) { 'deteriorating'=>'📉','improving'=>'📈','stable'=>'➡️',default=>'❔' };
                @endphp
                <tr>
                    <td><code>{{ $p['asset_code'] }}</code></td>
                    <td><a href="{{ route('sarpras.predictive.show', $p['asset_id']) }}">{{ $p['asset_name'] }}</a></td>
                    <td><span class="badge bg-{{ $color }}">{{ $p['risk_level'] }}</span></td>
                    <td class="text-center">{{ round($p['mtbf_days']) }} hari</td>
                    <td class="text-center">{{ round($p['mttr_days']) }} hari</td>
                    <td class="text-center">{{ $p['repair_count'] }}</td>
                    <td class="text-center">{{ $trendIcon }} {{ $p['trend'] }}</td>
                    <td class="text-center">
                        {{ $p['next_predicted_failure'] ?? '—' }}
                        @if($p['days_until_failure'] !== null && $p['days_until_failure'] >= 0)
                            <small class="d-block text-muted">dalam {{ $p['days_until_failure'] }} hari</small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data prediksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection