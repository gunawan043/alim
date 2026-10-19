@extends('layouts.master')
@section('title') Buku Peminjaman Barang @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
        @slot('title') Buku Peminjaman Barang
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Peminjaman','value'=>$summary['total'],'tone'=>'primary','icon'=>'ri-book-open-line'],
        ['label'=>'Dipinjam','value'=>$summary['dipinjam'],'tone'=>'info','icon'=>'ri-box-3-line'],
        ['label'=>'Kembali','value'=>$summary['kembali'],'tone'=>'success','icon'=>'ri-arrow-go-back-line'],
        ['label'=>'Terlambat','value'=>$summary['terlambat'],'tone'=>'danger','icon'=>'ri-alarm-warning-line'],
    ]" />

    <div class="card mb-3">
        <div class="card-body"><form method="GET" action="{{ route('sarpras.laporan.buku-peminjaman') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small fw-semibold">Dari Tanggal</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Sampai Tanggal</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['pending'=>'Menunggu','approved'=>'Disetujui','dipinjam'=>'Dipinjam','dikembalikan'=>'Dikembalikan','terlambat'=>'Terlambat','hilang'=>'Hilang','dibatalkan'=>'Dibatalkan'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
            <div class="col-md-2"><a href="{{ route('sarpras.laporan.buku-peminjaman.pdf', request()->query()) }}" class="btn btn-danger w-100"><i class="ri-file-pdf-line me-1"></i>PDF</a></div>
        </form></div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-book-open-line me-2 text-primary"></i>Buku Peminjaman Barang</h5></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>No</th><th>Nama Peminjam</th><th>Divisi</th><th>Nama Barang</th><th>Kode Barang</th><th>Kondisi Saat Peminjaman</th><th>Hari/Tanggal</th><th>Batas Peminjaman</th><th>Paraf Peminjaman</th><th>Kondisi Saat Kembali</th><th>Hari/Tanggal Kembali</th><th>Paraf Pengembalian</th></tr></thead>
            <tbody>
            @forelse($loans as $i => $loan)
                <tr>
                    <td>{{ $loans->firstItem() + $i }}</td>
                    <td class="small">{{ $loan->borrower?->name ?? '-' }}</td>
                    <td class="small">{{ $loan->borrower?->gtkWorkUnits?->first()?->workUnit?->name ?? '-' }}</td>
                    <td class="small">{{ $loan->asset?->asset_name ?? '-' }}</td>
                    <td><code class="small">{{ $loan->asset?->asset_code ?? '-' }}</code></td>
                    <td class="small">{{ ucfirst(str_replace('_',' ', $loan->condition_on_loan ?? '-')) }}</td>
                    <td class="small">{{ $loan->loan_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td>
                    <td class="small">{{ $loan->expected_return_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td>
                    <td class="small">{{ $loan->approver?->name ?? '-' }}</td>
                    <td class="small">{{ ucfirst(str_replace('_',' ', $loan->condition_on_return ?? '-')) }}</td>
                    <td class="small">{{ $loan->actual_return_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td>
                    <td class="small">{{ $loan->returnedToUser?->name ?? '-' }}</td>
                </tr>
            @empty
                <x-empty-state colspan="12" title="Belum ada data peminjaman" message="Data peminjaman barang akan tampil di sini." />
            @endforelse
            </tbody>
        </table></div></div>
        @if($loans->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $loans])</div>@endif
    </div>
@endsection
