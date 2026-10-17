@extends('vendor.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    {{-- Welcome --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-1">Selamat Datang, {{ $vendorName }}!</h4>
                    <p class="text-muted mb-0">Kelola pesanan, pengiriman, dan faktur Anda dari portal ini.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ri-shopping-bag-line text-primary fs-3"></i>
                    <h3 class="mb-1">{{ $totalOrders }}</h3>
                    <small class="text-muted">Total Pesanan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ri-truck-line text-warning fs-3"></i>
                    <h3 class="mb-1">{{ $pendingDeliveries }}</h3>
                    <small class="text-muted">Pengiriman Aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ri-calendar-check-line text-info fs-3"></i>
                    <h3 class="mb-1">{{ $upcomingDeliveries }}</h3>
                    <small class="text-muted">Akan Datang</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ri-file-text-line text-success fs-3"></i>
                    <h3 class="mb-1">{{ $repairOrders }}</h3>
                    <small class="text-muted">Order Service</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pesanan Terbaru</h5>
                <a href="{{ route('vendor.procurement.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($recentOrders->isEmpty())
                <div class="text-center py-5 text-muted">Belum ada pesanan.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                            <tr>
                                <td><strong>{{ $order->request_number }}</strong></td>
                                <td>{{ $order->request_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ match($order->status) {
                                        'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'info',
                                        'ordered' => 'primary', 'delivered' => 'success',
                                        'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
                                        default => 'secondary'
                                    } }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>Rp {{ number_format($order->total_estimated_budget ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('vendor.procurement.show', $order->id) }}" class="btn btn-sm btn-link">Detail</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('vendor.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Keluar</button>
        </form>
    </div>
</div>
@endsection
