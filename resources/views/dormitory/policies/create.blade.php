@extends('layouts.master')

@section('title', 'Buat Kebijakan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Buat Kebijakan Asrama Baru</h4>
                <a href="{{ route('user.boarding-policies.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('user.boarding-policies.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Informasi Dasar</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                                <small class="text-muted">Slug unik, mis. <code>santri-reguler</code></small>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Kebijakan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Kebijakan Izin (Leave)</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Strategi Izin <span class="text-danger">*</span></label>
                            <select name="leave_strategy" class="form-select" id="leave_strategy" required>
                                <option value="quota">Dengan Kuota</option>
                                <option value="unrestricted">Tanpa Batasan</option>
                                <option value="banned">Dilarang</option>
                            </select>
                        </div>
                        <div id="leave_quota_fields">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Kuota</label>
                                    <input type="number" name="leave_quota" class="form-control" min="0" value="{{ old('leave_quota') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Periode Kuota</label>
                                    <select name="leave_quota_period" class="form-select">
                                        <option value="weekly">Per Minggu</option>
                                        <option value="monthly">Per Bulan</option>
                                        <option value="semester">Per Semester</option>
                                        <option value="yearly">Per Tahun</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jam Pulang Maks (Curfew)</label>
                                <input type="number" name="curfew_hour" class="form-control" min="0" max="23" value="{{ old('curfew_hour') }}">
                                <small class="text-muted">0-23. Kosongkan jika tidak ada batas.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="special_permission_allowed" value="0">
                                    <input type="checkbox" name="special_permission_allowed" value="1" class="form-check-input" id="sp_allowed" {{ old('special_permission_allowed') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sp_allowed">Izinkan Izin Khusus (medis/darurat/dll)</label>
                                </div>
                            </div>
                        </div>
                        <div id="special_types" class="mb-3">
                            <label class="form-label">Jenis Izin Khusus yang Diizinkan</label>
                            <div class="d-flex gap-3 flex-wrap">
                                @foreach(['medical' => 'Medis', 'emergency' => 'Darurat', 'family' => 'Keluarga', 'competition' => 'Lomba', 'school_activity' => 'Kegiatan Sekolah'] as $val => $label)
                                <div class="form-check">
                                    <input type="checkbox" name="special_permission_types[]" value="{{ $val }}" class="form-check-input" id="spt_{{ $val }}" {{ in_array($val, old('special_permission_types', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="spt_{{ $val }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Kebijakan Kunjungan</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Strategi Kunjungan <span class="text-danger">*</span></label>
                            <select name="visit_strategy" class="form-select" id="visit_strategy" required>
                                <option value="quota">Dengan Kuota</option>
                                <option value="unrestricted">Bebas</option>
                                <option value="banned">Dilarang</option>
                            </select>
                        </div>
                        <div id="visit_quota_fields">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Kuota Kunjungan</label>
                                    <input type="number" name="visit_quota" class="form-control" min="0" value="{{ old('visit_quota') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Periode</label>
                                    <select name="visit_quota_period" class="form-select">
                                        <option value="daily">Harian</option>
                                        <option value="weekly">Mingguan</option>
                                        <option value="monthly">Bulanan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maks Pengunjung per Kunjungan</label>
                                <input type="number" name="max_visitors_per_visit" class="form-control" min="1" value="{{ old('max_visitors_per_visit') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Integrasi Akademik</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="hidden" name="auto_sync_academic_attendance" value="0">
                            <input type="checkbox" name="auto_sync_academic_attendance" value="1" class="form-check-input" id="auto_sync" {{ old('auto_sync_academic_attendance', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_sync">
                                Sinkronisasi otomatis absensi akademik saat izin disetujui
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Saat diaktifkan, izin yang disetujui akan otomatis menandai status "Izin" di absensi sekolah.
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Terapkan ke Asrama</h5></div>
                    <div class="card-body">
                        @forelse($dormitories as $dorm)
                        <div class="form-check">
                            <input type="checkbox" name="dormitory_ids[]" value="{{ $dorm->id }}" class="form-check-input" id="dorm_{{ $dorm->id }}">
                            <label class="form-check-label" for="dorm_{{ $dorm->id }}">{{ $dorm->name }}</label>
                        </div>
                        @empty
                        <p class="text-muted">Belum ada asrama aktif.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Status</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="ri-save-line me-1"></i> Simpan Kebijakan
                    </button>
                    <a href="{{ route('user.boarding-policies.index') }}" class="btn btn-light">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
function toggleQuotaFields(strategyId, fieldsId) {
    const sel = document.getElementById(strategyId);
    const fields = document.getElementById(fieldsId);
    function update() {
        fields.style.display = sel.value === 'quota' ? '' : 'none';
    }
    sel.addEventListener('change', update);
    update();
}
toggleQuotaFields('leave_strategy', 'leave_quota_fields');
toggleQuotaFields('visit_strategy', 'visit_quota_fields');

document.getElementById('sp_allowed').addEventListener('change', function() {
    document.getElementById('special_types').style.display = this.checked ? '' : 'none';
});
document.getElementById('special_types').style.display = document.getElementById('sp_allowed').checked ? '' : 'none';
</script>
@endsection