@extends('vendor.layouts.app')
@section('title', 'Performa Vendor')
@section('content')
<h4 class="mb-4">Performa Vendor</h4>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <h3 class="text-primary">{{ $performance['total_orders'] }}</h3>
            <small class="text-muted">Total Pesanan</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <h3 class="text-success">{{ $performance['completed_orders'] }}</h3>
            <small class="text-muted">Selesai</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <h3 class="text-info">{{ $performance['on_time_rate'] }}%</h3>
            <small class="text-muted">Tepat Waktu</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <h3 class="text-warning">Rp {{ number_format($performance['total_revenue'] ?? 0, 0, ',', '.') }}</h3>
            <small class="text-muted">Total Omzet</small>
        </div>
    </div>
</div>
@endsection
