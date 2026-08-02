@extends('vendor.layouts.app')
@section('title', 'Faktur')
@section('content')
<h4 class="mb-4">Faktur</h4>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($invoices->isEmpty())
            <div class="text-center py-5 text-muted">Tidak ada faktur.</div>
        @else
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No. Faktur</th><th>Tanggal</th><th>Jumlah</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $inv)
                    <tr>
                        <td><strong>{{ $inv->invoice_number }}</strong></td>
                        <td>{{ $inv->invoice_date?->format('d M Y') }}</td>
                        <td>Rp {{ number_format($inv->total_amount ?? 0, 0, ',', '.') }}</td>
                        <td><span class="badge bg-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($inv->status ?? '-') }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection

