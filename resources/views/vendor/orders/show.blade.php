@extends('vendor.layouts.app')
@section('title', 'Detail Pesanan')
@section('content')
<h4 class="mb-4">{{ $order->request_number }}</h4>
<div class="alert alert-info">
    <i class="ri-information-line me-2"></i>Status: <strong>{{ ucfirst($order->status) }}</strong>
    @if ($order->delivery_date)
    <br>Pengiriman: {{ $order->delivery_date->format('d M Y') }}
    @endif
    @if ($order->tracking_number)
    <br>Tracking: {{ $order->tracking_number }}
    @endif
</div>
<a href="{{ route('vendor.orders.index') }}" class="btn btn-outline-secondary">Kembali</a>
@endsection
