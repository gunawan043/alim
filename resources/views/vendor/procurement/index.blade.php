@extends('vendor.layouts.app')
@section('title', 'Daftar Pengadaan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Daftar Permintaan Pengadaan</h4>
    <a href="{{ route('vendor.procurement.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>Buat Baru</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($orders->isEmpty())
            <div class="text-center py-5 text-muted">Tidak ada permintaan pengadaan.</div>
        @else
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. PR</th><th>Tanggal</th><th>Tujuan</th><th>Metode</th>
                        <th>Estimasi</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $pr)
                    <tr>
                        <td><strong>{{ $pr->request_number }}</strong></td>
                        <td>{{ $pr->request_date?->format('d M Y') }}</td>
                        <td>{{ Str::limit($pr->purpose, 40) }}</td>
                        <td>{{ Str::replace('_', ' ', $pr->procurement_method) }}</td>
                        <td>Rp {{ number_format($pr->total_estimated_budget ?? 0, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ match($pr->status) {
                                'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'info',
                                'ordered' => 'primary', 'delivered' => 'success',
                                'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
                                default => 'secondary'
                            } }}">{{ ucfirst($pr->status) }}</span>
                        </td>
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
