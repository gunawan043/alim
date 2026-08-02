@extends('layouts.master')

@section('title', 'Edit Kebijakan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit: {{ $policy->name }}</h4>
                <a href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('user.boarding-policies.update', ['userId' => $userId, 'id' => $policy->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Informasi Dasar</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $policy->code) }}" readonly>
                                <small class="text-muted">Kode tidak dapat diubah</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $policy->name) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $policy->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Kebijakan Izin</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Strategi <span class="text-danger">*</span></label>
                            <select name="leave_strategy" class="form-select" id="leave_strategy" required>
                                <option value="quota" {{ $policy->leave_strategy === 'quota' ? 'selected' : '' }}>Dengan Kuota</option>
                                <option value="unrestricted" {{ $policy->leave_strategy === 'unrestricted' ? 'selected' : '' }}>Tanpa Batasan</option>
                                <option value="banned" {{ $policy->leave_strategy === 'banned' ? 'selected' : '' }}>Dilarang</option>
                            </select>
                        </div>
                        <div id="leave_quota_fields" style="display: {{ $policy->leave_strategy === 'quota' ? '' : 'none' }}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kuota</label>
                                    <input type="number" name="leave_quota" class="form-control" min="0" value="{{ old('leave_quota', $policy->leave_quota) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Periode</label>
                                    <select name="leave_quota_period" class="form-select">
                                        <option value="weekly" {{ $policy->leave_quota_period === 'weekly' ? 'selected' : '' }}>Per Minggu</option>
                                        <option value="monthly" {{ $policy->leave_quota_period === 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                                        <option value="semester" {{ $policy->leave_quota_period === 'semester' ? 'selected' : '' }}>Per Semester</option>
                                        <option value="yearly" {{ $policy->leave_quota_period === 'yearly' ? 'selected' : '' }}>Per Tahun</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jam Pulang Maks (Curfew)</label>
                                <input type="number" name="curfew_hour" class="form-control" min="0" max="23" value="{{ old('curfew_hour', $policy->curfew_hour) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="special_permission_allowed" value="0">
                                    <input type="checkbox" name="special_permission_allowed" value="1" class="form-check-input" id="sp_allowed" {{ $policy->special_permission_allowed ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sp_allowed">Izinkan Izin Khusus</label>
                                </div>
                            </div>
                        </div>
                        <div id="special_types" class="mb-3" style="display: {{ $policy->special_permission_allowed ? '' : 'none' }}">
                            <label class="form-label">Jenis</label>
                            <div class="d-flex gap-3 flex-wrap">
                                @foreach(['medical' => 'Medis', 'emergency' => 'Darurat', 'family' => 'Keluarga', 'competition' => 'Lomba', 'school_activity' => 'Kegiatan Sekolah'] as $val => $label)
                                <div class="form-check">
                                    <input type="checkbox" name="special_permission_types[]" value="{{ $val }}" class="form-check-input" id="spt_{{ $val }}" {{ in_array($val, old('special_permission_types', json_decode($policy->special_permission_types, true) ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="spt_{{ $val }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Kebijakan Kunjungan</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Strategi <span class="text-danger">*</span></label>
                            <select name="visit_strategy" class="form-select" id="visit_strategy" required>
                                <option value="quota" {{ $policy->visit_strategy === 'quota' ? 'selected' : '' }}>Dengan Kuota</option>
                                <option value="unrestricted" {{ $policy->visit_strategy === 'unrestricted' ? 'selected' : '' }}>Bebas</option>
                                <option value="banned" {{ $policy->visit_strategy === 'banned' ? 'selected' : '' }}>Dilarang</option>
                            </select>
                        </div>
                        <div id="visit_quota_fields" style="display: {{ $policy->visit_strategy === 'quota' ? '' : 'none' }}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kuota</label>
                                    <input type="number" name="visit_quota" class="form-control" min="0" value="{{ old('visit_quota', $policy->visit_quota) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Periode</label>
                                    <select name="visit_quota_period" class="form-select">
                                        <option value="daily" {{ $policy->visit_quota_period === 'daily' ? 'selected' : '' }}>Harian</option>
                                        <option value="weekly" {{ $policy->visit_quota_period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                        <option value="monthly" {{ $policy->visit_quota_period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maks Pengunjung</label>
                                <input type="number" name="max_visitors_per_visit" class="form-control" min="1" value="{{ old('max_visitors_per_visit', $policy->max_visitors_per_visit) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Terapkan ke Asrama</h5></div>
                    <div class="card-body">
                        @forelse($dormitories as $dorm)
                        <div class="form-check">
                            <input type="checkbox" name="dormitory_ids[]" value="{{ $dorm->id }}" class="form-check-input" id="dorm_{{ $dorm->id }}" {{ in_array($dorm->id, $assignedDormIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="dorm_{{ $dorm->id }}">{{ $dorm->name }}</label>
                        </div>
                        @empty
                        <p class="text-muted">Belum ada asrama aktif.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Status</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $policy->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                    <i class="ri-save-line me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
function toggleQuota(strategyId, fieldsId) {
    const sel = document.getElementById(strategyId);
    const fields = document.getElementById(fieldsId);
    function update() { fields.style.display = sel.value === 'quota' ? '' : 'none'; }
    sel.addEventListener('change', update);
    update();
}
toggleQuota('leave_strategy', 'leave_quota_fields');
toggleQuota('visit_strategy', 'visit_quota_fields');

document.getElementById('sp_allowed').addEventListener('change', function() {
    document.getElementById('special_types').style.display = this.checked ? '' : 'none';
});
document.getElementById('special_types').style.display = document.getElementById('sp_allowed').checked ? '' : 'none';
</script>
@endsection