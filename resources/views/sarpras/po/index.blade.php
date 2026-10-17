@extends('layouts.sarpras')

@section('title', 'Purchase Orders')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-success text-white d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Daftar Purchase Order</h5>
        <a href="{{ route('sarpras.po.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Buat PO Baru
        </a>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Vendor</th>
                    <th>Tgl Order</th>
                    <th>Tgl Diharapkan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pos as $po)
                    <tr>
                        <td><code>{{ $po->po_number }}</code></td>
                        <td>{{ $po->vendor?->name ?? '-' }}</td>
                        <td>{{ $po->order_date?->format('d M Y') }}</td>
                        <td>{{ $po->expected_date?->format('d M Y') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $po->total, 0, ',', '.') }}</td>
                        <td>
                            @switch($po->status)
                                @case('draft')
                                    <span class="badge bg-secondary">Draft</span>
                                    @break
                                @case('submitted')
                                    <span class="badge bg-primary">Submitted</span>
                                    @break
                                @case('partial')
                                    <span class="badge bg-warning">Partial</span>
                                    @break
                                @case('received')
                                    <span class="badge bg-success">Received</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-info">{{ $po->status }}</span>
                            @endswitch
                        </td>
                        <td>{{ $po->payment_term_days ?? '-' }} hari</td>
                        <td class="text-end">
                            <a href="{{ route('sarpras.po.show', $po) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4">Tidak ada PO</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $pos->links() }}
        </div>
    </div>
</div>
@endsection