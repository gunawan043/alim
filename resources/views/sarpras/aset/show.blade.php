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
                    <a href="{{ route('sarpras.aset.timeline', ['id' => $aset->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-calendar-2-line me-1"></i> Timeline</a>
                    <a href="{{ route('sarpras.qr.pdf', ['asset_id' => $aset->id]) }}" class="btn btn-sm btn-info"><i class="ri-printer-line me-1"></i> Cetak QR</a>
                    @if($aset->condition !== 'dihapus')
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disposalModal"><i class="ri-delete-bin-6-line me-1"></i> Usulkan Penghapusan</button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr><td class="text-muted fw-medium" style="width:200px">Nama Aset</td><td>{{ $aset->asset_name }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kode Aset</td><td><code>{{ $aset->asset_code ?? '-' }}</code> <small class="text-muted">(QR unit)</small></td></tr>
                            <tr><td class="text-muted fw-medium">No Urut Satker</td><td>{{ $aset->work_unit_sequence ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kategori</td><td>{{ $aset->category?->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kuantitas</td><td>{{ number_format($aset->quantity ?? 1) }} {{ $aset->unit ?? 'unit' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Satuan Kerja</td><td>{{ $aset->workUnit?->name ?? ($aset->room?->workUnit?->name ?? '-') }}</td></tr>
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
                            <tr><td class="text-muted fw-medium">Tanggal Perolehan</td><td>{{ $aset->acquisition_date?->format('d/m/Y') ?? $aset->acquisition_year ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Harga Perolehan</td><td>{{ $aset->acquisition_price ? 'Rp '.number_format($aset->acquisition_price,0,',','.') : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Masa Pakai</td><td>{{ $aset->masa_pakai ? $aset->masa_pakai.' tahun' : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Harga Penyusutan / Tahun</td><td>{{ $aset->harga_penyusutan ? 'Rp '.number_format($aset->harga_penyusutan,0,',','.') : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kondisi KIR</td><td><span class="badge bg-light text-dark border">{{ $aset->kir_condition }}</span></td></tr>
                            <tr><td class="text-muted fw-medium">Sumber Dana</td><td>{{ $aset->funding_source ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Nilai Saat Ini</td><td>{{ $aset->current_value ? 'Rp '.number_format($aset->current_value,0,',','.') : '-' }}</td></tr>
                            @if($aset->notes)<tr><td class="text-muted fw-medium">Catatan</td><td>{{ $aset->notes }}</td></tr>@endif
                            <tr><td class="text-muted fw-medium">Aktif</td><td>@if($aset->is_active)<span class="badge bg-success-subtle text-success">Ya</span>@else<span class="badge bg-secondary-subtle text-secondary">Tidak</span>@endif</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">QR Code</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">{{ $aset->qrModeLabel() }}</span>
                                    @if($aset->isGroupQr())
                                        <span class="text-muted small ms-1">{{ $qrGroupMembers->count() }} unit berbagi label</span>
                                    @endif
                                    <div class="text-muted small mt-1">Dibuat {{ $aset->qr_generated_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-qr-code-line me-1"></i> QR Code Aset</h5>
                <span class="badge bg-primary-subtle text-primary">{{ $aset->qrModeLabel() }}</span>
            </div>
            <div class="card-body text-center">
                <img src="{{ route('sarpras.qr.image', ['id' => $aset->id, 'size' => 220]) }}"
                     alt="QR {{ $aset->asset_code }}" class="img-fluid border rounded p-2" style="max-width:220px">

                <div class="small text-muted text-break mt-2">{{ $aset->qrScanUrl() }}</div>

                @if($aset->isGroupQr())
                    <div class="alert alert-info small text-start mt-2 mb-0">
                        <strong>QR seragam.</strong> {{ $qrGroupMembers->count() }} unit sejenis di
                        {{ $aset->room?->room_name ?? 'unit ini' }} berbagi satu label. Pindai untuk melihat daftar unitnya.
                    </div>
                @else
                    <div class="alert alert-light border small text-start mt-2 mb-0">
                        <strong>QR per unit.</strong> Riwayat perawatan & perbaikan unit ini tercatat terpisah.
                    </div>
                @endif

                <div class="d-grid gap-2 mt-2">
                    <a href="{{ route('sarpras.qr.pdf', ['asset_id' => $aset->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-printer-line me-1"></i> Cetak Label QR
                    </a>
                    @if($aset->isGroupQr())
                        <a href="{{ route('sarpras.assets.scan.group', ['key' => $aset->qr_group_key]) }}" class="btn btn-sm btn-light">
                            <i class="ri-group-line me-1"></i> Lihat Daftar Unit
                        </a>
                    @endif
                </div>

                @if($canManageQr)
                    <hr>
                    <form action="{{ route('sarpras.qr.mode', ['id' => $aset->id]) }}" method="POST" class="text-start">
                        @csrf
                        <label class="form-label small fw-semibold">Mode QR</label>
                        <select name="qr_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($qrModeOptions as $value => $label)
                                <option value="{{ $value }}" @selected($aset->qr_mode === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Pilih <em>seragam</em> untuk barang banyak &amp; sejenis (kursi, meja). Pilih
                            <em>per unit</em> untuk elektronik/aset besar agar riwayat tiap unit terpisah.
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Foto Aset</h5></div>
            <div class="card-body">
                @if($aset->photos->isNotEmpty())
                    <div class="row g-2">
                        @foreach($aset->photos as $photo)
                        <div class="col-6">
                            @php $photoSrc = $photo->photo_url ?: ($photo->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($photo->photo_path) : null); @endphp
                            @if($photoSrc)<img src="{{ $photoSrc }}" class="img-fluid rounded" alt="{{ $photo->caption }}">@endif
                            <form action="{{ route('sarpras.aset.photo.delete', ['id' => $aset->id, 'photoId' => $photo->id]) }}" method="POST" class="mt-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" data-confirm="Hapus?"><i class="ri-delete-bin-line"></i></button>
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

@if($aset->condition !== 'dihapus')
<div class="modal fade" id="disposalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('sarpras.disposal.propose', ['asset' => $aset->id]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Usulkan Penghapusan Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        Usulan akan masuk antrean <a href="{{ route('sarpras.disposal.pending') }}">Penghapusan Aset</a> dan menunggu persetujuan Kepala URT. Aset belum dihapus sampai disetujui.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metode yang Diusulkan <span class="text-danger">*</span></label>
                        <select name="disposal_method" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="sell">Jual</option>
                            <option value="scrap">Scrap</option>
                            <option value="transfer">Transfer ke unit lain</option>
                            <option value="donate">Hibah / donasi</option>
                            <option value="destroy">Dimusnahkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan <span class="text-danger">*</span></label>
                        <textarea name="disposal_reason" class="form-control" rows="3" required placeholder="Contoh: rusak berat dan tidak ekonomis diperbaiki"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Perkiraan Nilai (opsional)</label>
                        <input type="number" name="disposal_value" class="form-control" min="0" step="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Usulan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Riwayat Perawatan</h5>
        <a href="{{ route('sarpras.aset.timeline', ['id' => $aset->id]) }}" class="btn btn-sm btn-outline-primary">
            <i class="ri-edit-2-line me-1"></i> Kelola Timeline
        </a>
    </div>
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