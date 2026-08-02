@extends('vendor.layouts.app')
@section('title', 'Pesanan & Pengiriman')
@section('content')
<h4 class="mb-4">Pesanan & Pengiriman</h4>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($orders->isEmpty())
            <div class="text-center py-5 text-muted">Tidak ada pesanan.</div>
        @else
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No. PR</th><th>Status</th><th>Pengiriman</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($orders as $pr)
                    <tr>
                        <td><strong>{{ $pr->request_number }}</strong></td>
                        <td><span class="badge bg-{{ match($pr->status) {
                            'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'info',
                            'ordered' => 'primary', 'delivered' => 'success',
                            'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
                            default => 'secondary'
                        } }}">{{ ucfirst($pr->status) }}</span></td>
                        <td>{{ $pr->delivery_date?->format('d M Y') ?? '-' }}</td>
                        <td><a href="{{ route('vendor.procurement.show', $pr->id) }}" class="btn btn-sm btn-link">Detail</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
