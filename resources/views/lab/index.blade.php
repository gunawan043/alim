@extends('layouts.master')
@section('title', 'Laboratorium IPA')
@section('css')
    @include('shared._styles')
    @include('lab._styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $labId = $lab?->id;
        $conditionLabel = ['baik' => 'B', 'rusak_ringan' => 'RR', 'rusak_sedang' => 'RR', 'rusak_berat' => 'RB', 'hilang' => '-', 'dihapus' => '-'];
    @endphp
    @component('components.breadcrumb')
        @slot('li_1') Satuan Pendidikan @endslot
        @slot('title') Laboratorium IPA @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @if(! $lab)
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-title bg-warning-subtle text-warning rounded fs-2"><i class="ri-flask-line"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Profil laboratorium belum dibuat</h5>
                        <p class="text-muted mb-0">Buat profil laboratorium IPA terlebih dahulu. Inventaris tetap memakai master aset Sarpras yang sudah ada.</p>
                    </div>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="ri-add-line me-1"></i>Buat Profil</button>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="lab-icon bg-primary-subtle text-primary"><i class="ri-archive-2-line fs-4"></i></span><div><p class="text-uppercase text-muted mb-0" style="font-size:11px">Item Inventaris</p><h3 class="mb-0 fw-bold">{{ number_format($stats['items']) }}</h3></div></div><small class="text-muted d-block mt-2">{{ number_format($stats['quantity']) }} unit/satuan tercatat</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="lab-icon bg-success-subtle text-success"><i class="ri-checkbox-circle-line fs-4"></i></span><div><p class="text-uppercase text-muted mb-0" style="font-size:11px">Kondisi Baik</p><h3 class="mb-0 fw-bold">{{ number_format($stats['good']) }}</h3></div></div><small class="text-danger d-block mt-2">{{ number_format($stats['damaged']) }} perlu perhatian</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="lab-icon bg-warning-subtle text-warning"><i class="ri-tools-line fs-4"></i></span><div><p class="text-uppercase text-muted mb-0" style="font-size:11px">Kalibrasi Overdue</p><h3 class="mb-0 fw-bold">{{ number_format($stats['due_calibration']) }}</h3></div></div><small class="text-muted d-block mt-2">{{ number_format($stats['due_inspection']) }} pemeriksaan terjadwal</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="lab-icon bg-info-subtle text-info"><i class="ri-arrow-right-up-line fs-4"></i></span><div><p class="text-uppercase text-muted mb-0" style="font-size:11px">Peminjaman Aktif</p><h3 class="mb-0 fw-bold">{{ number_format($stats['active_loans']) }}</h3></div></div><small class="text-muted d-block mt-2">{{ number_format($stats['upcoming_activities']) }} kegiatan mendatang</small></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card"><div class="card-body py-2 d-flex flex-wrap gap-2">
                <a href="{{ route('lab.inspection.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-search-eye-line me-1"></i>Pemeriksaan</a>
                <a href="{{ route('lab.calibration.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-timer-line me-1"></i>Kalibrasi</a>
                <a href="{{ route('lab.loan.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-info"><i class="ri-arrow-right-up-line me-1"></i>Peminjaman</a>
                <a href="{{ route('lab.activity.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-success"><i class="ri-flask-line me-1"></i>Kegiatan</a>
                <a href="{{ route('lab.usage.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-file-shield-2-line me-1"></i>Izin</a>
                <a href="{{ route('lab.schedule.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-dark"><i class="ri-calendar-schedule-line me-1"></i>Jadwal</a>
                <a href="{{ route('lab.safety.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-danger"><i class="ri-shield-check-line me-1"></i>Inspeksi K3</a>
                <a href="{{ route('lab.incident.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-danger"><i class="ri-alert-line me-1"></i>Insiden</a>
                <a href="{{ route('lab.disposal.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-delete-bin-6-line me-1"></i>Penghapusan</a>
                <a href="{{ route('lab.evaluation.index', ['userId' => $userId, 'lab_id' => $labId]) }}" class="btn btn-sm btn-outline-success"><i class="ri-file-chart-line me-1"></i>Evaluasi</a>
            </div></div>
        </div>
    </div>

    @if($lab)
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="labInventory">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Inventaris Alat dan Bahan Laboratorium IPA</h5>
                            <p class="text-muted mb-0">{{ $lab->name }} &middot; {{ $year?->name ?? '-' }} &middot; Semester {{ $semester }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('lab.inventory.print', ['userId' => $userId, 'lab_id' => $labId]) }}" target="_blank" class="btn btn-danger"><i class="ri-printer-line me-1"></i>Cetak Daftar</a>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal" @if($assets->isEmpty()) disabled @endif><i class="ri-add-line me-1"></i>Tambah Item</button>
                                <button class="btn btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="ri-settings-3-line me-1"></i>Profil Lab</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover table-freeze lab-table align-middle">
                            <thead class="table-light"><tr>
                                <th style="width:45px">NO</th><th>NAMA ALAT/BAHAN</th><th style="width:90px">JUMLAH</th><th style="width:80px">SATUAN</th><th style="width:110px">KONDISI</th><th>KET.</th><th style="width:140px">Aksi</th>
                            </tr></thead>
                            <tbody>
                            @forelse($inventory as $index => $asset)
                                @php $profile = $profiles->get($asset->id); $code = $conditionLabel[$asset->condition] ?? '-'; @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td><div class="fw-semibold">{{ $asset->asset_name }}</div><small class="text-muted">{{ $asset->asset_code }}{{ $profile ? ' · '.ucfirst($profile->item_kind) : '' }}{{ $profile?->requires_calibration ? ' · kalibrasi' : '' }}</small></td>
                                    <td class="text-center">{{ number_format($asset->quantity) }}</td>
                                    <td>{{ $asset->unit ?: 'unit' }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $code === 'B' ? 'success' : ($code === 'RR' ? 'warning' : 'danger') }}-subtle text-{{ $code === 'B' ? 'success' : ($code === 'RR' ? 'warning' : 'danger') }}">{{ $code }}</span></td>
                                    <td>{{ $asset->notes ?: '-' }}</td>
                                    <td><button class="btn btn-sm btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#itemModal" data-asset-id="{{ $asset->id }}" data-kind="{{ $profile?->item_kind ?? 'alat' }}" data-calibration="{{ $profile?->requires_calibration ? 1 : 0 }}" data-interval="{{ $profile?->calibration_interval_months ?? 12 }}" data-min="{{ $profile?->min_stock ?? 0 }}" data-storage="{{ $profile?->storage_location }}" data-hazard="{{ $profile?->hazard_class }}" data-consumable="{{ $profile?->is_consumable ? 1 : 0 }}"><i class="ri-settings-3-line"></i></button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-5"><i class="ri-archive-line fs-1 text-muted"></i><h5 class="mt-2">Belum ada item laboratorium</h5><p class="text-muted mb-0">Tambahkan aset laboratorium dari master aset terlebih dahulu.</p></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="card-title mb-0">Kalibrasi Terdekat</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Alat</th><th>Jadwal</th><th>Status</th></tr></thead><tbody>@forelse($calibrations as $row)<tr><td>{{ $row->asset?->asset_name ?? '-' }}</td><td>{{ $row->next_calibration_date?->format('d/m/Y') ?? '-' }}</td><td><span class="badge bg-{{ $row->status === 'OD' ? 'danger' : 'success' }}-subtle text-{{ $row->status === 'OD' ? 'danger' : 'success' }}">{{ $row->status }}</span></td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-3">Belum ada data kalibrasi.</td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="card-title mb-0">Peminjaman Terbaru</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Peminjam</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>@forelse($loans as $row)<tr><td>{{ $row->borrower_name }}</td><td>{{ $row->loan_date?->format('d/m/Y') }}</td><td><span class="badge bg-info-subtle text-info">{{ $row->status }}</span></td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-3">Belum ada peminjaman.</td></tr>@endforelse</tbody></table></div></div></div></div>
    </div>
    @endif

    <div class="modal fade" id="profileModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('lab.profile.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $labId }}"><div class="modal-header"><h5 class="modal-title"><i class="ri-flask-line me-1 text-primary"></i>Profil Laboratorium IPA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Nama Laboratorium</label><input name="name" class="form-control" value="{{ old('name', $lab?->name) }}" required placeholder="Laboratorium IPA"></div><div class="col-md-6"><label class="form-label">Kode</label><input name="code" class="form-control" value="{{ old('code', $lab?->code) }}" required placeholder="LAB-IPA"></div><div class="col-md-12"><label class="form-label">Ruang Laboratorium</label><select name="asset_room_id" class="form-select"><option value="">— Pilih ruang laboratorium —</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected($lab?->asset_room_id === $room->id)>{{ $room->room_name }} ({{ $room->room_code }})</option>@endforeach</select><small class="text-muted">Ruang harus bertipe laboratorium. Jika belum ada, buat di menu Sarpras → Ruang.</small></div><div class="col-md-6"><label class="form-label">Koordinator</label><select name="coordinator_user_id" class="form-select"><option value="{{ auth()->id() }}">{{ auth()->user()->name }}</option></select></div><div class="col-md-6"><label class="form-label">Kepala/Koordinator (penyetuju)</label><select name="head_user_id" class="form-select"><option value="{{ auth()->id() }}">{{ auth()->user()->name }}</option></select></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $lab?->notes) }}</textarea></div></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>

    <div class="modal fade" id="itemModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('lab.inventory.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $labId }}"><div class="modal-header"><h5 class="modal-title"><i class="ri-archive-2-line me-1 text-primary"></i>Pengaturan Item Laboratorium</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-12"><label class="form-label">Aset dari Inventaris Sarpras</label><select name="asset_id" id="labAssetId" class="form-select" required><option value="">— Pilih aset —</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_name }} · {{ $asset->asset_code }} · {{ $asset->quantity }} {{ $asset->unit }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Jenis</label><select name="item_kind" id="labItemKind" class="form-select"><option value="alat">Alat</option><option value="alat_ukur">Alat Ukur</option><option value="bahan">Bahan</option></select></div><div class="col-md-4"><label class="form-label">Interval Kalibrasi (bulan)</label><input type="number" name="calibration_interval_months" id="labInterval" class="form-control" min="1" max="120" value="12"></div><div class="col-md-4"><label class="form-label">Stok Minimum</label><input type="number" name="min_stock" id="labMin" class="form-control" min="0" value="0"></div><div class="col-md-6"><label class="form-label">Lokasi Penyimpanan</label><input name="storage_location" id="labStorage" class="form-control" maxlength="150"></div><div class="col-md-6"><label class="form-label">Kelas Bahaya</label><input name="hazard_class" id="labHazard" class="form-control" maxlength="100" placeholder="mis. korosif, mudah terbakar"></div><div class="col-12 d-flex gap-4"><div class="form-check"><input type="checkbox" name="requires_calibration" id="labCalibration" class="form-check-input" value="1"><label class="form-check-label" for="labCalibration">Membutuhkan kalibrasi</label></div><div class="form-check"><input type="checkbox" name="is_consumable" id="labConsumable" class="form-check-input" value="1"><label class="form-check-label" for="labConsumable">Bahan habis pakai</label></div></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('itemModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value || ''; };
        set('labAssetId', btn.dataset.assetId);
        set('labItemKind', btn.dataset.kind || 'alat');
        set('labInterval', btn.dataset.interval || 12);
        set('labMin', btn.dataset.min || 0);
        set('labStorage', btn.dataset.storage);
        set('labHazard', btn.dataset.hazard);
        document.getElementById('labCalibration').checked = btn.dataset.calibration === '1';
        document.getElementById('labConsumable').checked = btn.dataset.consumable === '1';
    });
});
</script>
@endsection
