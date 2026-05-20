@extends('layouts.master')
@section('title') Pengadaan Barang @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Pengadaan Barang @endslot
@endcomponent

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm"><h5 class="card-title mb-0">Daftar Pengadaan</h5></div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.pengadaan.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i> Request Pengadaan</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach(['draft','pending','approved','rejected','ordered','delivered','completed','cancelled'] as $s)
                                <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>No. Request</th><th>Tanggal</th><th>Tujuan</th><th>Item</th><th>Total Estimasi</th><th>Status</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($procurements as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><code>{{ $p->request_number }}</code></td>
                                <td>{{ $p->request_date?->format('d/m/Y') }}</td>
                                <td>{{ Str::limit($p->purpose, 50) }}</td>
                                <td><span class="badge bg-secondary">{{ $p->items->count() }} item</span></td>
                                <td>{{ $p->total_estimated_budget ? 'Rp '.number_format($p->total_estimated_budget,0,',','.') : '-' }}</td>
                                <td>
                                    @php $c=['draft'=>'secondary','pending'=>'warning','approved'=>'success','rejected'=>'danger','ordered'=>'info','delivered'=>'primary','completed'=>'success','cancelled'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $c[$p->status] ?? 'secondary' }}-subtle text-{{ $c[$p->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$p->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('sarpras.pengadaan.show', ['id' => $p->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4"><p class="text-muted mb-0">Belum ada request pengadaan.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $procurements])
            </div>
        </div>
    </div>
</div>
@endsection
