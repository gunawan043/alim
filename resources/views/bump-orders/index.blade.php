@extends('layouts.master')
@section('title', 'Pemesanan Barang BUMP')
@section('css')
<style>
.table-container{position:relative;width:100%;overflow-x:auto}.table-freeze{table-layout:auto;min-width:max-content;margin-bottom:0;width:100%}.table-freeze th,.table-freeze td{white-space:normal;vertical-align:middle;padding:12px 16px;word-break:break-word}.card-animate{transition:all .3s ease}.card-animate:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.1)}
</style>
@endsection
@section('content')
    @component('components.breadcrumb') @slot('li_1') Sarpras @endslot @slot('title') Pemesanan Barang BUMP @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row mb-3">
        @foreach([['Total Pesanan',$stats['total'],'ri-shopping-bag-3-line','primary'],['Masih Berjalan',$stats['pending'],'ri-time-line','warning'],['Selesai',$stats['completed'],'ri-checkbox-circle-line','success'],['Belum Dibayar',$stats['unpaid'],'ri-wallet-3-line','danger'],['Nilai Pesanan','Rp '.number_format($stats['value'],0,',','.'),'ri-money-dollar-circle-line','info']] as [$label,$value,$icon,$color])
            <div class="col-xl col-md-4 mb-2"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><h4 class="fw-semibold mb-0">{{ $value }}</h4></div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0">Riwayat Pemesanan BUMP</h5><p class="text-muted small mb-0">Pesanan dari ALIM; status dan pembayaran mengikuti BUMP.</p></div>
            <a href="{{ route('user.bump-orders.create',['userId'=>$userId]) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Pesan Barang</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3"><div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Semua status</option>@foreach(['pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','processing'=>'Diproses','completed'=>'Selesai','cancelled'=>'Dibatalkan','rejected'=>'Ditolak'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div></form>
            <div class="table-container"><table class="table table-hover table-freeze align-middle"><thead class="table-light"><tr><th>Nomor BUMP</th><th>Unit</th><th>Metode</th><th>Status</th><th>Pembayaran</th><th class="text-end">Total</th><th>Tanggal</th><th></th></tr></thead><tbody>
                @forelse($orders as $order)<tr><td class="font-monospace small">{{ $order->pos_order_number }}</td><td>{{ $order->workUnit?->name ?? '—' }}</td><td>{{ $order->payment_method }}</td><td><span class="badge bg-info-subtle text-info">{{ ucfirst($order->status) }}</span></td><td><span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'secondary' }}-subtle text-{{ $order->payment_status === 'paid' ? 'success' : 'secondary' }}">{{ ucfirst($order->payment_status) }}</span></td><td class="text-end">Rp {{ number_format((float)$order->total,0,',','.') }}</td><td class="small text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td><td class="text-end"><a href="{{ route('user.bump-orders.show',['userId'=>$userId,'number'=>$order->pos_order_number]) }}" class="btn btn-sm btn-soft-primary">Detail</a></td></tr>
                @empty<tr><td colspan="8" class="text-center text-muted py-5">Belum ada pemesanan BUMP.</td></tr>@endforelse
            </tbody></table></div>
            {{ $orders->links() }}
        </div>
    </div>
@endsection
