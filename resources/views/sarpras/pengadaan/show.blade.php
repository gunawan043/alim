@extends('layouts.master')
@section('title') Detail Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pengadaan.index') }}">Pengadaan</a> @endslot
    @slot('title') {{ $procurement->request_number }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Detail Request: {{ $procurement->request_number }}</h5>
                <div class="d-flex gap-1">
                    @if(!in_array($procurement->status, ['delivered','completed','cancelled']))
                        <a href="{{ route('sarpras.pengadaan.edit', ['id' => $procurement->id]) }}" class="btn btn-outline-warning btn-sm"><i class="ri-pencil-line me-1"></i> Edit</a>
                    @endif
                @php $c=['draft'=>'secondary','pending'=>'warning','approved'=>'success','rejected'=>'danger','ordered'=>'info','delivered'=>'primary','completed'=>'success','cancelled'=>'secondary']; @endphp
                <span class="badge bg-{{ $c[$procurement->status] ?? 'secondary' }}-subtle text-{{ $c[$procurement->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$procurement->status)) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="text-muted small">Request Date</label><p class="mb-1">{{ $procurement->request_date?->format('d/m/Y') }}</p></div>
                    <div class="col-md-4"><label class="text-muted small">Requester</label><p class="mb-1">{{ $procurement->requester?->name ?? '-' }}</p></div>
                    <div class="col-md-4"><label class="text-muted small">Urgensi</label><p class="mb-1"><span class="badge bg-{{ $procurement->urgency=='mendesak'?'danger':($procurement->urgency=='tinggi'?'warning':'info') }}-subtle">{{ ucfirst($procurement->urgensi) }}</span></p></div>
                    <div class="col-12"><label class="text-muted small">Tujuan</label><p class="mb-1">{{ $procurement->purpose }}</p></div>
                    <div class="col-md-6"><label class="text-muted small">Sumber Dana</label><p class="mb-1">{{ $procurement->budget_source ?? '-' }}</p></div>
                    <div class="col-md-6"><label class="text-muted small">Total Estimasi</label><p class="mb-1">{{ $procurement->total_estimated_budget ? 'Rp '.number_format($procurement->total_estimated_budget,0,',','.') : '-' }}</p></div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0">Item Pengadaan</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>#</th><th>Nama Item</th><th>Jumlah</th><th>Satuan</th><th>Harga/Unit</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach($procurement->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ $item->estimated_price_per_unit ? 'Rp '.number_format($item->estimated_price_per_unit,0,',','.') : '-' }}</td>
                            <td>{{ $item->total_estimated_price ? 'Rp '.number_format($item->total_estimated_price,0,',','.') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Aksi</h5></div>
            <div class="card-body">
                @if($procurement->status === 'pending')
                <a href="{{ route('sarpras.pengadaan.approve', ['id' => $procurement->id]) }}" class="btn btn-success w-100 mb-2" onclick="return confirm('Setuju?')"><i class="ri-check-line me-1"></i> Approve</a>
                <a href="{{ route('sarpras.pengadaan.reject', ['id' => $procurement->id]) }}" class="btn btn-danger w-100 mb-2"><i class="ri-close-line me-1"></i> Tolak</a>
                @endif
                @if($procurement->status === 'approved')
                <a href="{{ route('sarpras.pengadaan.receive-form', ['id' => $procurement->id]) }}" class="btn btn-primary w-100 mb-2"><i class="ri-truck-line me-1"></i> Terima Barang</a>
                @endif
                @if($procurement->status === 'delivered')
                <a href="{{ route('sarpras.pengadaan.convert-form', ['id' => $procurement->id]) }}" class="btn btn-success w-100 mb-2"><i class="ri-arrow-left-right-line me-1"></i> Konversi ke Aset</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
