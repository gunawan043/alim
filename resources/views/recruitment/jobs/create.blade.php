@extends('layouts.master')
@section('title') Tambah Lowongan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2') Daftar Lowongan @endslot
    @slot('title') Tambah Lowongan @endslot
@endcomponent

<style>
    :root {
        --primary: #2563eb;
        --primary-soft: #eff6ff;
        --primary-border: #bfdbfe;
        --success: #16a34a;
        --danger: #dc2626;
        --muted: #6b7280;
        --border: #e5e7eb;
        --surface: #ffffff;
        --surface-alt: #f9fafb;
        --text: #111827;
        --text-soft: #374151;
        --radius: 12px;
        --radius-sm: 8px;
        --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
        --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    }

    .job-form-card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    .job-form-header {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 60%, #3b82f6 100%);
        padding: 28px 32px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .job-form-header .icon-wrap {
        width: 48px; height: 48px;
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: #fff; flex-shrink: 0;
    }

    .job-form-header h4 { margin: 0; color: #fff; font-weight: 700; font-size: 1.2rem; letter-spacing: -0.3px; }
    .job-form-header p  { margin: 2px 0 0; color: rgba(255,255,255,0.75); font-size: 0.85rem; }

    .form-section { padding: 24px 32px 0; }
    .form-section:last-child { padding-bottom: 0; }

    .section-label {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 20px; padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .section-label .section-icon {
        width: 32px; height: 32px;
        background: var(--primary-soft);
        border: 1px solid var(--primary-border);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; color: var(--primary);
    }

    .section-label span { font-weight: 600; font-size: 0.95rem; }

    .gaji-toggle-wrap {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer; transition: border-color 0.2s, background 0.2s; user-select: none;
    }

    .gaji-toggle-wrap .label-text {
        display: flex; align-items: center; gap: 10px;
        font-size: 0.88rem; font-weight: 600; color: var(--text-soft);
    }

    .gaji-toggle-wrap .label-text i { font-size: 17px; color: var(--primary); }

    .toggle-switch { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; inset: 0; background: #d1d5db;
        border-radius: 24px; cursor: pointer; transition: background 0.25s;
    }
    .toggle-slider:before {
        content: ''; position: absolute;
        height: 18px; width: 18px; left: 3px; top: 3px;
        background: white; border-radius: 50%;
        transition: transform 0.25s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }

    #gaji-section { overflow: hidden; transition: max-height 0.35s ease, opacity 0.3s ease; max-height: 0; opacity: 0; }
    #gaji-section.show { max-height: 200px; opacity: 1; }

    /* Tahapan seleksi */
    .tahapan-list { display: flex; flex-direction: column; gap: 8px; }
    .tahapan-item {
        display: flex; align-items: center; gap: 8px;
        background: var(--surface-alt); border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); padding: 8px 12px;
    }
    .step-num {
        width: 24px; height: 24px; border-radius: 50%;
        background: var(--primary-soft); border: 1px solid var(--primary-border);
        color: var(--primary); font-size: 0.75rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .tahapan-item input { flex: 1; border: none; background: transparent; outline: none; font-size: 0.88rem; }
    .btn-rm { border: none; background: none; color: #9ca3af; cursor: pointer; padding: 2px; border-radius: 4px; transition: color 0.15s; }
    .btn-rm:hover { color: var(--danger); }
    .btn-add-tahapan {
        margin-top: 8px; padding: 7px 16px;
        border: 1.5px dashed var(--primary-border); border-radius: var(--radius-sm);
        background: var(--primary-soft); color: var(--primary);
        font-size: 0.83rem; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 6px; transition: all 0.2s;
        width: 100%; justify-content: center;
    }
    .btn-add-tahapan:hover { border-style: solid; }

    .form-hint { font-size: 0.77rem; color: var(--muted); margin-top: 4px; display: flex; align-items: center; gap: 4px; }

    .form-footer {
        padding: 20px 32px 28px;
        display: flex; justify-content: flex-end; gap: 10px;
        border-top: 1px solid var(--border); margin-top: 28px;
    }

    .btn-cancel {
        padding: 9px 20px; border: 1.5px solid;
        border-radius: var(--radius-sm); font-size: 0.88rem; font-weight: 600;
        cursor: pointer; text-decoration: none;
        display: flex; align-items: center; gap: 6px; transition: all 0.2s;
    }
    .btn-cancel:hover { border-color: var(--danger); color: var(--danger); background: #fef2f2; }

    .btn-submit {
        padding: 9px 24px; border: none; border-radius: var(--radius-sm);
        background: linear-gradient(135deg, #1e40af, #2563eb);
        color: white; font-size: 0.88rem; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 8px; transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(37,99,235,0.35);
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
        box-shadow: 0 4px 14px rgba(37,99,235,0.45); transform: translateY(-1px);
    }

    .required-star { color: var(--danger); }
</style>

<div class="row">
<div class="col-lg-12">
<div class="job-form-card mb-3">
<form action="{{ route('user.ats.jobs.store', ['userId' => $userId]) }}" method="POST" id="jobForm">
@csrf

{{-- HEADER --}}
<div class="job-form-header">
    <div class="icon-wrap"><i class="ri-briefcase-4-line"></i></div>
    <div>
        <h4>Tambah Lowongan Baru</h4>
        <p>Isi formulir di bawah untuk membuat lowongan pekerjaan</p>
    </div>
</div>

<div class="card card-body mb-n3">

    {{-- SEKSI 1: Informasi Dasar --}}
    <div class="form-section">
        <div class="section-label">
            <div class="section-icon"><i class="ri-file-text-line"></i></div>
            <span>Informasi Dasar</span>
        </div>
        <div class="row g-3">

            <div class="col-lg-6">
                <label class="form-label">Judul Lowongan <span class="required-star">*</span></label>
                <input type="text" class="form-control @error('judul') is-invalid @enderror"
                    name="judul" placeholder="Contoh: Staff Keuangan Senior"
                    value="{{ old('judul') }}" required />
                @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Posisi / Jabatan <span class="required-star">*</span></label>
                <input type="text" class="form-control @error('posisi') is-invalid @enderror"
                    name="posisi" placeholder="Contoh: Kepala Seksi Anggaran"
                    value="{{ old('posisi') }}" required />
                @error('posisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Unit Kerja</label>
                <select class="form-control @error('work_unit_id_uuid') is-invalid @enderror"
                    data-choices name="work_unit_id_uuid">
                    <option value="">-- Pilih Unit Kerja --</option>
                    @foreach ($workUnits as $unit)
                        <option value="{{ $unit->uuid }}" {{ old('work_unit_id_uuid') == $unit->uuid ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
                @error('work_unit_id_uuid')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Kategori Pekerjaan <span class="required-star">*</span></label>
                <select class="form-control @error('kategori') is-invalid @enderror"
                    data-choices name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    @php
                        $kategoriList = [
                            'Akuntansi & Keuangan','Administrasi & Perkantoran','Teknologi Informasi',
                            'Pemasaran & Periklanan','Pemasaran Digital','Pendidikan & Pelatihan',
                            'Pengadaan & Logistik','Hukum & Kepatuhan','Sumber Daya Manusia',
                            'Kesehatan & Medis','Teknik & Rekayasa','Pelayanan Publik',
                            'Kehumasan & Komunikasi','Penelitian & Pengembangan','Lainnya',
                        ];
                    @endphp
                    @foreach ($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ old('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
                @error('kategori')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Status Pegawai</label>
                <select class="form-control @error('status_pegawai') is-invalid @enderror"
                    data-choices name="status_pegawai">
                    <option value="">-- Pilih Status --</option>
                    @foreach(['pns'=>'PNS','pppk'=>'PPPK','honor'=>'Honor','kontrak'=>'Kontrak','magang'=>'Magang','tetap'=>'Tetap','probation'=>'Probation'] as $val => $label)
                        <option value="{{ $val }}" {{ old('status_pegawai') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Pengalaman <span class="required-star">*</span></label>
                <select class="form-control @error('pengalaman') is-invalid @enderror"
                    data-choices name="pengalaman" required>
                    <option value="">-- Pilih Pengalaman --</option>
                    @foreach(['0 Tahun','1 Tahun','2 Tahun','3 Tahun','4 Tahun','5+ Tahun'] as $exp)
                        <option value="{{ $exp }}" {{ old('pengalaman') == $exp ? 'selected' : '' }}>{{ $exp }}</option>
                    @endforeach
                </select>
                @error('pengalaman')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-12">
                <label class="form-label">Deskripsi Pekerjaan <span class="required-star">*</span></label>
                <textarea class="form-control @error('deskripsi_pekerjaan') is-invalid @enderror"
                    name="deskripsi_pekerjaan" rows="4"
                    placeholder="Jelaskan tugas dan tanggung jawab pekerjaan..." required>{{ old('deskripsi_pekerjaan') }}</textarea>
                @error('deskripsi_pekerjaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- SEKSI 2: Waktu & Lokasi --}}
    <div class="form-section" style="margin-top: 24px;">
        <div class="section-label">
            <div class="section-icon"><i class="ri-calendar-line"></i></div>
            <span>Waktu & Kuota</span>
        </div>
        <div class="row g-3">

            <div class="col-md-3">
                <label class="form-label">Jumlah Kuota <span class="required-star">*</span></label>
                <input type="number" class="form-control @error('kuota') is-invalid @enderror"
                    name="kuota" placeholder="Jumlah lowongan"
                    value="{{ old('kuota', 1) }}" required min="1" />
                @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label">Tanggal Buka <span class="required-star">*</span></label>
                {{-- Gunakan type="date" agar value Y-m-d dikirim ke controller --}}
                <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                    name="tanggal_mulai" data-provider="flatpickr" data-date-format="d M, Y"
                    value="{{ old('tanggal_mulai') }}" required />
                @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label">Tanggal Tutup <span class="required-star">*</span></label>
                <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                    name="tanggal_selesai" data-provider="flatpickr" data-date-format="d M, Y"
                    value="{{ old('tanggal_selesai') }}" required />
                @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label">Lokasi <span class="required-star">*</span></label>
                <input type="text" class="form-control @error('lokasi') is-invalid @enderror"
                    name="lokasi" placeholder="Kota / Daerah"
                    value="{{ old('lokasi', 'Mataram') }}" required />
                @error('lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- SEKSI 3: Informasi Gaji --}}
    <div class="form-section" style="margin-top: 24px;">
        <div class="section-label">
            <div class="section-icon"><i class="ri-money-dollar-circle-line"></i></div>
            <span>Informasi Gaji</span>
        </div>

        <label class="gaji-toggle-wrap" for="tampilkan_gaji_toggle">
            <span class="label-text">
                <i class="ri-eye-line"></i>
                Tampilkan rentang gaji kepada pelamar
            </span>
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="tampilkan_gaji_toggle"
                    {{ old('_gaji_aktif') == '1' ? 'checked' : '' }}
                    onchange="toggleGaji(this)">
                <span class="toggle-slider"></span>
            </label>
        </label>
        <input type="hidden" name="_gaji_aktif" id="_gaji_aktif" value="{{ old('_gaji_aktif', '0') }}">

        <div id="gaji-section" class="{{ old('_gaji_aktif') == '1' ? 'show' : '' }}">
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Gaji Minimum (Rp)</label>
                    {{-- PENTING: name="rentang_gaji[min]" agar terkirim sebagai array --}}
                    <input type="number" class="form-control @error('rentang_gaji.min') is-invalid @enderror"
                        name="rentang_gaji[min]" placeholder="Contoh: 3000000"
                        value="{{ old('rentang_gaji.min') }}" disabled />
                    @error('rentang_gaji.min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gaji Maksimum (Rp)</label>
                    <input type="number" class="form-control @error('rentang_gaji.max') is-invalid @enderror"
                        name="rentang_gaji[max]" placeholder="Contoh: 6000000"
                        value="{{ old('rentang_gaji.max') }}" disabled />
                    @error('rentang_gaji.max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- SEKSI 4: Kualifikasi, Persyaratan & Fasilitas --}}
    <div class="form-section" style="margin-top: 24px;">
        <div class="section-label">
            <div class="section-icon"><i class="ri-list-check-2"></i></div>
            <span>Kualifikasi, Persyaratan & Fasilitas</span>
        </div>
        <div class="row g-3">

            <div class="col-lg-6">
                <label class="form-label">Kualifikasi Pendidikan</label>
                <textarea class="form-control @error('kualifikasi_pendidikan') is-invalid @enderror"
                    id="kualifikasi_pendidikan" name="kualifikasi_pendidikan" rows="3"
                    placeholder="- Minimal S1 Akuntansi&#10;- Diutamakan lulusan PTN">{{ old('kualifikasi_pendidikan') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('kualifikasi_pendidikan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Kualifikasi Pengalaman</label>
                <textarea class="form-control @error('kualifikasi_pengalaman') is-invalid @enderror"
                    id="kualifikasi_pengalaman" name="kualifikasi_pengalaman" rows="3"
                    placeholder="- Minimal 2 tahun di bidang keuangan">{{ old('kualifikasi_pengalaman') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('kualifikasi_pengalaman')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Persyaratan Umum</label>
                <textarea class="form-control @error('persyaratan_umum') is-invalid @enderror"
                    id="persyaratan_umum" name="persyaratan_umum" rows="4"
                    placeholder="- WNI&#10;- Usia minimal 18 tahun&#10;- Pendidikan minimal S1">{{ old('persyaratan_umum') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('persyaratan_umum')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Persyaratan Khusus</label>
                <textarea class="form-control @error('persyaratan_khusus') is-invalid @enderror"
                    id="persyaratan_khusus" name="persyaratan_khusus" rows="4"
                    placeholder="- Menguasai Ms. Office&#10;- Berpengalaman di bidang keuangan">{{ old('persyaratan_khusus') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('persyaratan_khusus')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Fasilitas & Tunjangan</label>
                <textarea class="form-control @error('fasilitas') is-invalid @enderror"
                    id="fasilitas" name="fasilitas" rows="4"
                    placeholder="- BPJS Kesehatan&#10;- Tunjangan Transportasi&#10;- Cuti Tahunan">{{ old('fasilitas') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('fasilitas')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label class="form-label">Kompetensi / Skill yang Dibutuhkan</label>
                <textarea class="form-control @error('kompetensi_dibutuhkan') is-invalid @enderror"
                    id="kompetensi_dibutuhkan" name="kompetensi_dibutuhkan" rows="4"
                    placeholder="- Microsoft Excel&#10;- Analisis Laporan Keuangan">{{ old('kompetensi_dibutuhkan') }}</textarea>
                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                @error('kompetensi_dibutuhkan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- SEKSI 5: Tahapan Seleksi --}}
    <div class="form-section" style="margin-top: 24px;">
        <div class="section-label">
            <div class="section-icon"><i class="ri-git-branch-line"></i></div>
            <span>Tahapan Seleksi</span>
        </div>

        <div class="tahapan-list" id="tahapanList">
            @php $defaultTahapan = old('tahapan_seleksi', ['Seleksi Administrasi', 'Tes Tertulis', 'Wawancara']); @endphp
            @foreach ($defaultTahapan as $i => $tahap)
            <div class="tahapan-item">
                <div class="step-num">{{ $i + 1 }}</div>
                <input type="text" name="tahapan_seleksi[]" value="{{ $tahap }}" placeholder="Nama tahapan..." />
                <button type="button" class="btn-rm" onclick="removeTahapan(this)"><i class="ri-close-line"></i></button>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn-add-tahapan" onclick="addTahapan()">
            <i class="ri-add-line"></i> Tambah Tahapan
        </button>
        <div class="form-hint mt-1"><i class="ri-information-line"></i> Urutkan dari tahap pertama hingga akhir</div>
    </div>

    {{-- SEKSI 6: Publikasi --}}
    <div class="form-section" style="margin-top: 24px;">
        <div class="section-label">
            <div class="section-icon"><i class="ri-settings-3-line"></i></div>
            <span>Status Publikasi</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status Lowongan</label>
                <select class="form-control @error('status') is-invalid @enderror" data-choices name="status">
                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif / Dipublikasikan</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="form-footer">
        <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn-cancel">
            <i class="ri-close-line"></i> Batal
        </a>
        <button type="submit" class="btn-submit">
            <i class="ri-save-line"></i> Simpan Lowongan
        </button>
    </div>

</div>{{-- card-body --}}
</form>
</div>{{-- job-form-card --}}
</div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
// ── Toggle gaji ──────────────────────────────────────────────
function toggleGaji(cb) {
    const sec    = document.getElementById('gaji-section');
    const state  = document.getElementById('_gaji_aktif');
    const inputs = sec.querySelectorAll('input[type="number"]');

    if (cb.checked) {
        sec.classList.add('show');
        inputs.forEach(i => i.disabled = false);
        state.value = '1';
    } else {
        sec.classList.remove('show');
        inputs.forEach(i => { i.disabled = true; i.value = ''; });
        state.value = '0';
    }
}

// Init: pastikan inputs disabled jika toggle off
document.addEventListener('DOMContentLoaded', function () {
    const cb = document.getElementById('tampilkan_gaji_toggle');
    if (cb && !cb.checked) {
        document.querySelectorAll('#gaji-section input[type="number"]').forEach(i => i.disabled = true);
    }
});

// ── Tahapan Seleksi ──────────────────────────────────────────
function addTahapan() {
    const list = document.getElementById('tahapanList');
    const div  = document.createElement('div');
    div.className = 'tahapan-item';
    div.innerHTML = `
        <div class="step-num">${list.children.length + 1}</div>
        <input type="text" name="tahapan_seleksi[]" placeholder="Nama tahapan..." />
        <button type="button" class="btn-rm" onclick="removeTahapan(this)"><i class="ri-close-line"></i></button>`;
    list.appendChild(div);
    renumber();
}
function removeTahapan(btn) { btn.closest('.tahapan-item').remove(); renumber(); }
function renumber() {
    document.querySelectorAll('#tahapanList .step-num').forEach((el, i) => el.textContent = i + 1);
}

// ── Submit: textarea → JSON string ──────────────────────────
// Controller parseLines() akan decode-nya
function parseTextarea(id) {
    const el = document.getElementById(id);
    if (!el || !el.value.trim()) return;
    const lines = el.value
        .split('\n')
        .map(l => l.replace(/^[-•]\s*/, '').trim())
        .filter(Boolean);
    const h  = document.createElement('input');
    h.type   = 'hidden';
    h.name   = id;                   // nama sama dengan textarea → override
    h.value  = JSON.stringify(lines);
    el.name  = '__skip_' + id;       // unbind textarea agar tidak bentrok
    el.closest('div').appendChild(h);
}

document.getElementById('jobForm').addEventListener('submit', function (e) {
    e.preventDefault();
    ['persyaratan_umum', 'persyaratan_khusus', 'kualifikasi_pendidikan',
     'kualifikasi_pengalaman', 'kompetensi_dibutuhkan', 'fasilitas'].forEach(parseTextarea);
    this.submit();
});
</script>
@endsection