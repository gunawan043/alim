@extends('layouts.master')
@section('title', 'Predictive — ' . $asset->asset_name)

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.predictive.index') }}">Predictive</a> @endslot
    @slot('title') {{ $asset->asset_code }} @endslot
@endcomponent

@php
    $color = match($prediction['risk_level']) { 'CRITICAL'=>'danger','HIGH'=>'warning','MEDIUM'=>'info',default=>'success' };
@endphp

<div class="alert alert-{{ $color }}">
    <h4 class="mb-1">Risk: {{ $prediction['risk_level'] }}</h4>
    <p class="mb-0">{{ $prediction['recommendation'] }}</p>
</div>

<div class="row g-3">
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">MTBF</h6><h4>{{ round($prediction['mtbf_days']) }} hari</h4></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">MTTR</h6><h4>{{ round($prediction['mttr_days']) }} hari</h4></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">Repairs</h6><h4>{{ $prediction['repair_count'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6 class="text-muted">Trend</h6><h4>{{ $prediction['trend'] }}</h4></div></div></div>
</div>

@if($prediction['next_predicted_failure'])
<div class="card mt-4">
    <div class="card-body">
        <h5>Prediksi Kerusakan Berikutnya</h5>
        <p class="mb-0">{{ $prediction['next_predicted_failure'] }} ({{ $prediction['days_until_failure'] }} hari dari sekarang)</p>
    </div>
</div>
@endif
@endsection