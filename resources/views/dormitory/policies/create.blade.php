@extends('layouts.master')

@section('title', 'Buat Kebijakan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Buat Kebijakan Asrama Baru</h4>
                <a href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('user.boarding-policies.store', ['userId' => $userId]) }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Informasi Dasar</h5></div>
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

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Kebijakan Izin (Leave)</h5></div>
                    <div class="card-body">

                        {{-- Quick Preset --}}
                        <div class="mb-3 p-3 rounded-3 bg-light border">
                            <small class="text-uppercase fw-bold text-muted d-block mb-2">⚡ Quick Preset</small>
                            <div class="btn-group btn-group-sm w-100 flex-wrap" role="group" id="leave_presets">
                                <button type="button" class="btn btn-outline-primary" data-q="{&quot;strategy&quot;:&quot;quota&quot;,&quot;quota&quot;:1,&quot;period&quot;:&quot;monthly&quot;}" onclick="applyPreset(this)">
                                    📅 1x / Bulan
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-q="{&quot;strategy&quot;:&quot;quota&quot;,&quot;quota&quot;:2,&quot;period&quot;:&quot;monthly&quot;}" onclick="applyPreset(this)">
                                    📅 2x / Bulan
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-q="{&quot;strategy&quot;:&quot;quota&quot;,&quot;quota&quot;:4,&quot;period&quot;:&quot;semester&quot;}" onclick="applyPreset(this)">
                                    📅 4x / Semester
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-q="{&quot;strategy&quot;:&quot;unrestricted&quot;}" onclick="applyPreset(this)">
                                    🚀 Bebas (Tanpa Batas)
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-q="{&quot;strategy&quot;:&quot;banned&quot;}" onclick="applyPreset(this)">
                                    🚫 Dilarang
                                </button>
                            </div>
                            <small class="text-muted">Klik preset di atas untuk mengisi otomatis strategi &amp; kuota izin pulang.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Strategi Izin <span class="text-danger">*</span></label>
                            <select name="leave_strategy" class="form-select" id="leave_strategy" required>
                                <option value="quota">Dengan Kuota</option>
                                <option value="unrestricted">Tanpa Batasan</option>
                                <option value="banned">Dilarang</option>
                            </select>
                            <small class="text-muted">Atur berapa kali santri boleh pulang dalam periode tertentu. Pilih "Bebas" tanpa limit, atau "Dilarang" agar santri tidak bisa mengajukan izin pulang.</small>
                        </div>
                        <div id="leave_quota_fields">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Kuota</label>
                                    <input type="number" name="leave_quota" class="form-control" id="leave_quota_input" min="0" value="{{ old('leave_quota') }}" onchange="updatePreview()">
                                    <small class="text-muted">Mis. `1` artinya setiap santri boleh pulang paling banyak 1 kali dalam satu periode.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Periode Kuota</label>
                                    <select name="leave_quota_period" class="form-select" id="leave_period_select" onchange="updatePreview()">
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
                                <input type="number" name="curfew_hour" class="form-control" id="curfew_input" min="0" max="23" value="{{ old('curfew_hour') }}" onchange="updatePreview()">
                                <small class="text-muted">Waktu pulang wajib ke asrama (0–23). Kosongkan jika tidak ada batas.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-3">
                                    <input type="hidden" name="special_permission_allowed" value="0">
                                    <input type="checkbox" name="special_permission_allowed" value="1" class="form-check-input" id="sp_allowed" {{ old('special_permission_allowed') ? 'checked' : '' }} onchange="toggleSpecialTypes(); updatePreview()">
                                    <label class="form-check-label" for="sp_allowed">Izinkan Izin Khusus (medis / darurat / keluarga)</label>
                                </div>
                                <small class="text-muted">Jika diaktifkan, santri bisa pulang meski kuota sudah habis — asal alasan termasuk jenis yang diizinkan.</small>
                            </div>
                        </div>
                        <div id="special_types" class="mb-3" style="display: {{ old('special_permission_allowed') ? '' : 'none' }}">
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

                        {{-- Live Preview --}}
                        <div id="leave_preview" class="mt-3 p-3 rounded-3 bg-info bg-opacity-10 border border-info" style="display:none;">
                            <strong class="text-info d-block mb-1"><i class="ri-information-line me-1"></i>Ringkasan Aturan Izin</strong>
                            <p class="mb-0 small text-dark" id="leave_preview_text"></p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Kebijakan Kunjungan</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Strategi Kunjungan <span class="text-danger">*</span></label>
                            <select name="visit_strategy" class="form-select" id="visit_strategy" required onchange="toggleVisitPreset()">
                                <option value="quota">Dengan Kuota</option>
                                <option value="unrestricted">Bebas</option>
                                <option value="banned">Dilarang</option>
                            </select>
                        </div>

                        {{-- Quick Preset Kunjungan --}}
                        <div class="mb-3 p-3 rounded-3 bg-light border">
                            <small class="text-uppercase fw-bold text-muted d-block mb-2">⚡ Quick Preset Kunjungan</small>
                            <div class="btn-group btn-group-sm w-100 flex-wrap" role="group">
                                <button type="button" class="btn btn-outline-primary" data-q="{&quot;strategy&quot;:&quot;quota&quot;,&quot;vquota&quot;:4,&quot;vperiod&quot;:&quot;monthly&quot;}" onclick="applyVisitPreset(this)">
                                    👨‍👩‍👧 4x / Bulan
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-q="{&quot;strategy&quot;:&quot;quota&quot;,&quot;vquota&quot;:8,&quot;vperiod&quot;:&quot;monthly&quot;}" onclick="applyVisitPreset(this)">
                                    👨‍👩‍👧 8x / Bulan
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-q="{&quot;strategy&quot;:&quot;unrestricted&quot;}" onclick="applyVisitPreset(this)">
                                    🚀 Bebas
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-q="{&quot;strategy&quot;:&quot;banned&quot;}" onclick="applyVisitPreset(this)">
                                    ��� Dilarang
                                </button>
                            </div>
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
                                <small class="text-muted">Batas jumlah orang yang boleh datang saat satu kali kunjungan.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Integrasi Akademik</h5></div>
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
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Terapkan ke Asrama</h5></div>
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

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold text-primary">Status</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="ri-save-line me-1"></i> Simpan Kebijakan
                    </button>
                    <a href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
/* ── Toggle quota fields on strategy change ── */
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

/* ── Toggle special types checkbox ── */
function toggleSpecialTypes() {
    const cb = document.getElementById('sp_allowed');
    document.getElementById('special_types').style.display = cb.checked ? '' : 'none';
}
document.getElementById('sp_allowed').addEventListener('change', toggleSpecialTypes);
if (document.getElementById('sp_allowed')) toggleSpecialTypes();

/* ── Quick Preset — Leave ── */
function applyPreset(btn) {
    const q = JSON.parse(btn.getAttribute('data-q'));
    const strategy = document.getElementById('leave_strategy');
    strategy.value = q.strategy;
    strategy.dispatchEvent(new Event('change')); // trigger toggle

    if (q.quota !== undefined) {
        document.getElementById('leave_quota_input').value = q.quota;
    }
    if (q.period !== undefined) {
        document.getElementById('leave_period_select').value = q.period;
    }
    updatePreview();
}

/* ── Quick Preset — Visit ── */
function toggleVisitPreset() {
    const strategy = document.getElementById('visit_strategy');
    if (strategy.value === 'quota') {
        document.getElementById('visit_quota_fields').style.display = '';
    } else {
        document.getElementById('visit_quota_fields').style.display = 'none';
    }
}

function applyVisitPreset(btn) {
    const q = JSON.parse(btn.getAttribute('data-q'));
    const strategy = document.getElementById('visit_strategy');
    strategy.value = q.strategy;
    strategy.dispatchEvent(new Event('change')); // trigger toggle

    if (q.vquota !== undefined) {
        const inputs = document.querySelectorAll('#visit_quota_fields input[name="visit_quota"]');
        if (inputs.length) inputs[0].value = q.vquota;
    }
    if (q.vperiod !== undefined) {
        const selects = document.querySelectorAll('#visit_quota_fields select[name="visit_quota_period"]');
        if (selects.length) selects[0].value = q.vperiod;
    }
}

/* ── Live Preview (Ringkasan Bahasa Manusia) ── */
function getLeaveText() {
    const strategy = document.getElementById('leave_strategy');
    if (!strategy) return null;
    const s = strategy.value;
    const qFields = document.getElementById('leave_quota_fields');
    if (!qFields || qFields.style.display === 'none') {
        if (s === 'banned') return 'Santri tidak boleh mengajukan izin pulang.';
        if (s === 'unrestricted') return 'Santri boleh mengajukan izin pulang tanpa batas.';
    }
    const quotaInput = document.getElementById('leave_quota_input');
    const periodSelect = document.getElementById('leave_period_select');
    const quota = quotaInput ? quotaInput.value : 0;
    const period = periodSelect ? periodSelect.options[periodSelect.selectedIndex]?.text : 'bulan';
    const curfew = document.getElementById('curfew_input');
    const curfewVal = curfew ? curfew.value : null;
    const spCb = document.getElementById('sp_allowed');
    const spAllowed = spCb && spCb.checked;
    const spTypesEl = document.getElementById('special_types');
    let spTypes = [];
    if (spAllowed && spTypesEl && spTypesEl.style.display !== 'none') {
        spTypes = [...spTypesEl.querySelectorAll('input[type=checkbox]:checked')].map(c => c.parentElement.textContent.trim());
    }

    let parts = [`Santri boleh pulang paling banyak ${quota || 0} kali ${period}.`];
    if (curfewVal) parts.push(`Waktu pulang wajib sebelum pukul ${curfewVal}:00.`);
    if (spAllowed && spTypes.length > 0) {
        parts.push(`Izin khusus diizinkan (${spTypes.join(', ')}), meski kuota habis — tetap bisa pulang asal dicatat alasannya.`);
    }
    if (spAllowed && !spTypes.length) {
        parts.push('Izin khusus diizinkan — kuota otomatis dilewati untuk alasan darurat.');
    }
    return parts.join(' ');
}

function updatePreview() {
    const text = getLeaveText();
    const el = document.getElementById('leave_preview');
    const txt = document.getElementById('leave_preview_text');
    if (text && el && txt) {
        el.style.display = '';
        txt.textContent = text;
    } else if (el && txt) {
        el.style.display = 'none';
    }
}

// Init preview on load
updatePreview();
</script>
@endsection