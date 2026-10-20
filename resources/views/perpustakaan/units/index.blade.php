@extends('layouts.master')
@section('title', 'Unit & Pengaturan Perpustakaan')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Pengaturan @endslot @slot('title') Unit & Pengaturan @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-settings-4-line text-primary me-1"></i>Unit Perpustakaan</h5><p class="text-muted mb-0 small">Pisahkan koleksi dan transaksi berdasarkan unit. Pengaturan mengikuti tahun ajaran {{ $year?->name ?? 'aktif' }}.</p></div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#unitModal" onclick="openUnitCreate()"><i class="ri-add-line me-1"></i>Tambah Unit</button>
        </div>
        <div class="table-responsive"><table class="table table-hover library-table align-middle mb-0">
            <thead class="table-light"><tr><th>Unit</th><th>Sekolah</th><th>Gender / Jenjang</th><th>Lokasi</th><th>Pengaturan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($units as $unit)
                @php $s = $settings[$unit->id] ?? null; @endphp
                <tr>
                    <td><div class="fw-semibold">{{ $unit->name }}</div><div class="text-muted small">{{ $unit->code }}</div></td>
                    <td>{{ $unit->school?->name ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $unit->gender_scope }}</span> <span class="badge bg-info-subtle text-info">{{ strtoupper($unit->level_scope) }}</span></td>
                    <td>{{ $unit->location ?? '—' }}</td>
                    <td class="small">
                        @if($s)
                            <div>Pinjam {{ $s->loan_days }} hari · maks {{ $s->max_active_loans }} buku</div>
                            <div class="text-muted">Denda: {{ $s->allow_fine ? 'Rp '.number_format((float)$s->fine_per_day,0,',','.') : 'nonaktif' }} · Kartu: Rp {{ number_format((float)$s->replacement_card_fee,0,',','.') }}</div>
                        @else
                            <span class="text-warning">Belum diatur</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-soft-warning" data-unit="{{ json_encode($unit->only(['id','school_id','code','name','gender_scope','level_scope','location','is_active']), JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openUnitEdit(this)" title="Edit"><i class="ri-pencil-line"></i></button>
                            <button class="btn btn-soft-primary" data-settings="{{ json_encode(['id'=>$unit->id,'academic_year_id'=>$year?->id,'loan_days'=>$s?->loan_days ?? 7,'max_active_loans'=>$s?->max_active_loans ?? 3,'allow_renewal'=>(bool)($s?->allow_renewal ?? true),'max_renewals'=>$s?->max_renewals ?? 1,'renewal_days'=>$s?->renewal_days ?? 7,'allow_fine'=>(bool)($s?->allow_fine ?? false),'fine_per_day'=>$s?->fine_per_day ?? 0,'max_fine'=>$s?->max_fine,'replacement_card_fee'=>$s?->replacement_card_fee ?? 0], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openSettings(this)" title="Pengaturan"><i class="ri-settings-4-line"></i></button>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada unit. Tambahkan Perpustakaan Putra, Putri, atau SD.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>

    <div class="modal fade library-modal" id="unitModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="POST" id="unitForm" action="{{ route('user.perpustakaan.units.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="_method" id="unitMethod" value="POST"><div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="unitTitle">Tambah Unit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Unit</label><input name="name" id="unitName" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Kode</label><input name="code" id="unitCode" class="form-control" required maxlength="30" placeholder="PERPUS-PUTRA"></div>
        <div class="col-md-6"><label class="form-label">Sekolah</label><select name="school_id" id="unitSchool" class="form-select"><option value="">—</option>@foreach($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Gender</label><select name="gender_scope" id="unitGender" class="form-select"><option value="mixed">Campuran</option><option value="L">Putra</option><option value="P">Putri</option></select></div>
        <div class="col-md-3"><label class="form-label">Jenjang</label><select name="level_scope" id="unitLevel" class="form-select"><option value="sd_smp">SD–SMP</option><option value="sd">SD</option><option value="smp">SMP</option></select></div>
        <div class="col-md-8"><label class="form-label">Lokasi</label><input name="location" id="unitLocation" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="is_active" id="unitActive" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>

    <div class="modal fade library-modal" id="settingsModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="POST" id="settingsForm" action="">@csrf<div class="modal-header bg-primary-subtle"><h5 class="modal-title">Pengaturan Sirkulasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="academic_year_id" id="settingYear" value="{{ $year?->id }}"><div class="row g-3">
        <div class="col-md-3"><label class="form-label">Masa Pinjam (hari)</label><input type="number" name="loan_days" id="settingDays" class="form-control" min="1" required></div>
        <div class="col-md-3"><label class="form-label">Maks. Buku</label><input type="number" name="max_active_loans" id="settingMax" class="form-control" min="1" required></div>
        <div class="col-md-3"><label class="form-label">Perpanjangan</label><select name="allow_renewal" id="settingRenew" class="form-select"><option value="1">Diizinkan</option><option value="0">Tidak</option></select></div>
        <div class="col-md-3"><label class="form-label">Maks. Perpanjangan</label><input type="number" name="max_renewals" id="settingMaxRenew" class="form-control" min="0"></div>
        <div class="col-md-3"><label class="form-label">Hari Perpanjangan</label><input type="number" name="renewal_days" id="settingRenewDays" class="form-control" min="1"></div>
        <div class="col-md-3"><label class="form-label">Denda</label><select name="allow_fine" id="settingFine" class="form-select"><option value="0">Nonaktif</option><option value="1">Aktif</option></select></div>
        <div class="col-md-3"><label class="form-label">Denda / Hari</label><input type="number" name="fine_per_day" id="settingFineDay" class="form-control" min="0" step="0.01"></div>
        <div class="col-md-3"><label class="form-label">Maks. Denda</label><input type="number" name="max_fine" id="settingFineMax" class="form-control" min="0" step="0.01"></div>
        <div class="col-md-4"><label class="form-label">Biaya Kartu Pengganti</label><input type="number" name="replacement_card_fee" id="settingCardFee" class="form-control" min="0" step="0.01"></div>
        <div class="col-12"><div class="alert alert-info py-2 mb-0 small">Biaya kartu default gratis (0). Ubah hanya sesuai kebijakan yang disetujui.</div></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Pengaturan</button></div></form></div></div></div>
@endsection
@section('script')
<script>
const unitStoreUrl=@json(route('user.perpustakaan.units.store',['userId'=>$userId]));
const unitUpdateUrl=@json(route('user.perpustakaan.units.update',['userId'=>$userId,'id'=>'__ID__']));
const settingsUrl=@json(route('user.perpustakaan.units.settings',['userId'=>$userId,'id'=>'__ID__']));
function openUnitCreate(){document.getElementById('unitTitle').textContent='Tambah Unit';document.getElementById('unitForm').action=unitStoreUrl;document.getElementById('unitMethod').value='POST';['unitName','unitCode','unitLocation'].forEach(id=>document.getElementById(id).value='');document.getElementById('unitSchool').value='';document.getElementById('unitGender').value='mixed';document.getElementById('unitLevel').value='sd_smp';document.getElementById('unitActive').value='1';}
function openUnitEdit(btn){const d=JSON.parse(btn.dataset.unit);document.getElementById('unitTitle').textContent='Edit Unit';document.getElementById('unitForm').action=unitUpdateUrl.replace('__ID__',d.id);document.getElementById('unitMethod').value='PUT';document.getElementById('unitName').value=d.name||'';document.getElementById('unitCode').value=d.code||'';document.getElementById('unitSchool').value=d.school_id||'';document.getElementById('unitGender').value=d.gender_scope||'mixed';document.getElementById('unitLevel').value=d.level_scope||'sd_smp';document.getElementById('unitLocation').value=d.location||'';document.getElementById('unitActive').value=d.is_active?'1':'0';new bootstrap.Modal(document.getElementById('unitModal')).show();}
function openSettings(btn){const d=JSON.parse(btn.dataset.settings);document.getElementById('settingsForm').action=settingsUrl.replace('__ID__',d.id);document.getElementById('settingYear').value=d.academic_year_id||'';document.getElementById('settingDays').value=d.loan_days;document.getElementById('settingMax').value=d.max_active_loans;document.getElementById('settingRenew').value=d.allow_renewal?'1':'0';document.getElementById('settingMaxRenew').value=d.max_renewals;document.getElementById('settingRenewDays').value=d.renewal_days;document.getElementById('settingFine').value=d.allow_fine?'1':'0';document.getElementById('settingFineDay').value=d.fine_per_day;document.getElementById('settingFineMax').value=d.max_fine||'';document.getElementById('settingCardFee').value=d.replacement_card_fee;new bootstrap.Modal(document.getElementById('settingsModal')).show();}
</script>
@endsection
