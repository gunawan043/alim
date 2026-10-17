@extends('layouts.master')
@section('title') Detail Aset — {{ $aset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('title') {{ $aset->asset_name }} @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Detail Aset</h5>
                <div>
                    <a href="{{ route('sarpras.aset.edit', ['id' => $aset->id]) }}" class="btn btn-sm btn-warning"><i class="ri-pencil-line me-1"></i> Edit</a>
                    <a href="{{ route('sarpras.qr.generate', ['id' => $aset->id]) }}" class="btn btn-sm btn-info"><i class="ri-qr-code-line me-1"></i> QR</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr><td class="text-muted fw-medium" style="width:200px">Nama Aset</td><td>{{ $aset->asset_name }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kode Aset</td><td><code>{{ $aset->asset_code ?? '-' }}</code></td></tr>
                            <tr><td class="text-muted fw-medium">Kategori</td><td>{{ $aset->category?->name ?? '-' }}</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">Ruang</td>
                                <td>
                                    @if($aset->room)
                                        <a href="{{ route('sarpras.ruang.show', ['id' => $aset->room_id]) }}">{{ $aset->room->room_name }}</a>
                                        @if($aset->room->building)<span class="text-muted"> — {{ $aset->room->building->building_name }}</span>@endif
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Satuan Pendidikan</td><td>{{ $aset->room?->school?->name ?? ($aset->school?->name ?? '-') }}</td></tr>
                            <tr><td class="text-muted fw-medium">Merk / Model</td><td>{{ $aset->brand ? "{$aset->brand} {$aset->model}" : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Nomor Seri</td><td><code>{{ $aset->serial_number ?? '-' }}</code></td></tr>
                            <tr><td class="text-muted fw-medium">Spesifikasi</td><td>{{ $aset->specification ?? '-' }}</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">Kondisi</td>
                                <td>
                                    @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary','dihapus'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $kc[$aset->condition] ?? 'secondary' }}-subtle text-{{ $kc[$aset->condition] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$aset->condition)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Status</td>
                                <td>
                                    @php $sc=['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $sc[$aset->status] ?? 'secondary' }}-subtle text-{{ $sc[$aset->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$aset->status)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Harga Perolehan</td><td>{{ $aset->acquisition_price ? 'Rp '.number_format($aset->acquisition_price,0,',','.') : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Sumber Dana</td><td>{{ $aset->funding_source ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Nilai Saat Ini</td><td>{{ $aset->current_value ? 'Rp '.number_format($aset->current_value,0,',','.') : '-' }}</td></tr>
                            @if($aset->notes)<tr><td class="text-muted fw-medium">Catatan</td><td>{{ $aset->notes }}</td></tr>@endif
                            <tr><td class="text-muted fw-medium">Aktif</td><td>@if($aset->is_active)<span class="badge bg-success-subtle text-success">Ya</span>@else<span class="badge bg-secondary-subtle text-secondary">Tidak</span>@endif</td></tr>
                            <tr><td class="text-muted fw-medium">QR Generated</td><td>{{ $aset->qr_generated_at ? $aset->qr_generated_at->format('d/m/Y H:i') : '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Foto Aset</h5></div>
            <div class="card-body">
                @if($aset->photos->isNotEmpty())
                    <div class="row g-2">
                        @foreach($aset->photos as $photo)
                        <div class="col-6">
                            <img src="{{ asset('storage/'.$photo->photo_path) }}" class="img-fluid rounded" alt="{{ $photo->caption }}">
                            <form action="{{ route('sarpras.aset.photo.delete', ['id' => $aset->id, 'photoId' => $photo->id]) }}" method="POST" class="mt-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Hapus?')"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">Belum ada foto.</p>
                @endif
                <hr>
                <form action="{{ route('sarpras.aset.photo.add', ['id' => $aset->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label small">Tambah Foto</label>
                    <input type="file" name="photo" class="form-control mb-2" accept="image/*">
                    <input type="text" name="caption" class="form-control mb-2" placeholder="Keterangan">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-upload-2-line me-1"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($riwayatPinjaman->isNotEmpty())
<div class="card mt-3">
    <div class="card-header"><h5 class="card-title mb-0">Riwayat Peminjaman</h5></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>#</th><th>Peminjam</th><th>Pinjam</th><th>Kembali</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($riwayatPinjaman as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $p->borrower?->name ?? '-' }}</td>
                    <td>{{ $p->loan_date?->format('d/m/Y') }}</td>
                    <td>{{ $p->actual_return_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_',' ',$p->status)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($riwayatMaintenance->isNotEmpty())
<div class="card mt-3">
    <div class="card-header"><h5 class="card-title mb-0">Riwayat Perawatan</h5></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>#</th><th>Jenis</th><th>Tanggal</th><th>Petugas</th><th>Biaya</th></tr></thead>
            <tbody>
                @foreach($riwayatMaintenance as $m)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $m->maintenance_type }}</td>
                    <td>{{ $m->maintenance_date?->format('d/m/Y') }}</td>
                    <td>{{ $m->performer?->name ?? '-' }}</td>
                    <td>{{ $m->actual_cost ? 'Rp '.number_format($m->actual_cost,0,',','.') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection