@extends('layouts.master')
@section('title') Edit Lowongan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2') Daftar Lowongan @endslot
    @slot('title') Edit Lowongan @endslot
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
        background: linear-gradient(135deg, #065f46 0%, #059669 60%, #10b981 100%);
        padding: 28px 32px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .job-form-header .icon-wrap {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #fff;
        flex-shrink: 0;
    }

    .job-form-header h4 {
        margin: 0;
        color: #fff;
        font-weight: 700;
        font-size: 1.2rem;
        letter-spacing: -0.3px;
    }

    .job-form-header p {
        margin: 2px 0 0;
        color: rgba(255,255,255,0.75);
        font-size: 0.85rem;
    }

    .kode-badge {
        margin-left: auto;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        padding: 6px 14px;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 600;
        font-family: monospace;
        letter-spacing: 0.5px;
    }

    .form-section {
        padding: 24px 32px 0;
    }

    .section-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .section-label .section-icon {
        width: 32px;
        height: 32px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: #059669;
    }

    .section-label span {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text);
    }

    .form-label {
        font-size: 0.83rem;
        font-weight: 600;
        color: var(--text-soft);
        margin-bottom: 6px;
        letter-spacing: 0.2px;
    }

    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 9px 13px;
        font-size: 0.9rem;
        color: var(--text);
        background: var(--surface);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5,150,105,0.12);
        outline: none;
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--danger);
    }

    .form-hint {
        font-size: 0.77rem;
        color: var(--muted);
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .gaji-toggle-wrap {
        background: var(--surface-alt);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        user-select: none;
    }

    .gaji-toggle-wrap:hover {
        border-color: #059669;
        background: #f0fdf4;
    }

    .gaji-toggle-wrap .label-text {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-soft);
    }

    .gaji-toggle-wrap .label-text i {
        font-size: 17px;
        color: #059669;
    }

    .toggle-switch {
        position: relative;
        width: 42px;
        height: 24px;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        inset: 0;
        background: #d1d5db;
        border-radius: 24px;
        cursor: pointer;
        transition: background 0.25s;
    }

    .toggle-slider:before {
        content: '';
        position: absolute;
        height: 18px;
        width: 18px;
        left: 3px;
        top: 3px;
        background: white;
        border-radius: 50%;
        transition: transform 0.25s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .toggle-switch input:checked + .toggle-slider { background: #059669; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }

    #gaji-section {
        overflow: hidden;
        transition: max-height 0.35s ease, opacity 0.3s ease;
        max-height: 0;
        opacity: 0;
    }

    #gaji-section.show {
        max-height: 200px;
        opacity: 1;
    }

    .form-footer {
        padding: 20px 32px 28px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid var(--border);
        background: var(--surface-alt);
        margin-top: 28px;
    }

    .btn-cancel {
        padding: 9px 20px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--text-soft);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        border-color: var(--danger);
        color: var(--danger);
        background: #fef2f2;
    }

    .btn-submit {
        padding: 9px 24px;
        border: none;
        border-radius: var(--radius-sm);
        background: linear-gradient(135deg, #065f46, #059669);
        color: white;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(5,150,105,0.35);
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #064e3b, #047857);
        box-shadow: 0 4px 14px rgba(5,150,105,0.45);
        transform: translateY(-1px);
    }

    .required-star { color: var(--danger); }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="job-form-card">
            <form action="{{ route('user.ats.jobs.update', ['userId' => $userId, 'job' => $job->id]) }}" method="POST" id="jobForm">
                @csrf
                @method('PUT')

                {{-- HEADER --}}
                <div class="job-form-header">
                    <div class="icon-wrap"><i class="ri-edit-box-line"></i></div>
                    <div>
                        <h4>Edit Lowongan Pekerjaan</h4>
                        <p>Perbarui informasi lowongan sesuai kebutuhan</p>
                    </div>
                    <div class="kode-badge">{{ $job->kode_lowongan }}</div>
                </div>

                <div>

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
                                    value="{{ old('judul', $job->judul) }}" required />
                                @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Posisi / Jabatan <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('posisi') is-invalid @enderror"
                                    name="posisi" placeholder="Contoh: Kepala Seksi Anggaran"
                                    value="{{ old('posisi', $job->posisi) }}" required />
                                @error('posisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Unit Kerja</label>
                                <select class="form-control @error('work_unit_id_uuid') is-invalid @enderror"
                                    data-choices name="work_unit_id_uuid">
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    @foreach ($workUnits as $unit)
                                        <option value="{{ $unit->uuid }}"
                                            {{ old('work_unit_id_uuid', $job->work_unit_id_uuid) == $unit->uuid ? 'selected' : '' }}>
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
                                            'Akuntansi & Keuangan',
                                            'Administrasi & Perkantoran',
                                            'Teknologi Informasi',
                                            'Pemasaran & Periklanan',
                                            'Pemasaran Digital',
                                            'Pendidikan & Pelatihan',
                                            'Pengadaan & Logistik',
                                            'Hukum & Kepatuhan',
                                            'Sumber Daya Manusia',
                                            'Kesehatan & Medis',
                                            'Teknik & Rekayasa',
                                            'Pelayanan Publik',
                                            'Kehumasan & Komunikasi',
                                            'Penelitian & Pengembangan',
                                            'Lainnya',
                                        ];
                                    @endphp
                                    @foreach ($kategoriList as $kat)
                                        <option value="{{ $kat }}"
                                            {{ old('kategori', $job->kategori) == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                                    @endforeach
                                </select>
                                @error('kategori')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Status Pegawai</label>
                                <select class="form-control @error('status_pegawai') is-invalid @enderror"
                                    data-choices name="status_pegawai">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="tetap" {{ old('status_pegawai', $job->status_pegawai) == 'tetap' ? 'selected' : '' }}>Tetap</option>
                                    <option value="kontrak" {{ old('status_pegawai', $job->status_pegawai) == 'kontrak' ? 'selected' : '' }}>Kontrak</option>
                                    <option value="probation" {{ old('status_pegawai', $job->status_pegawai) == 'probation' ? 'selected' : '' }}>Probation</option>
                                </select>
                                @error('status_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Pengalaman <span class="required-star">*</span></label>
                                <select class="form-control @error('pengalaman') is-invalid @enderror"
                                    data-choices name="pengalaman" required>
                                    <option value="">-- Pilih Pengalaman --</option>
                                    @foreach(['0 Tahun','1 Tahun','2 Tahun','3 Tahun','4 Tahun','5+ Tahun'] as $exp)
                                        <option value="{{ $exp }}" {{ old('pengalaman', $job->pengalaman) == $exp ? 'selected' : '' }}>{{ $exp }}</option>
                                    @endforeach
                                </select>
                                @error('pengalaman')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-12">
                                <label class="form-label">Deskripsi Pekerjaan <span class="required-star">*</span></label>
                                <textarea class="form-control @error('deskripsi_pekerjaan') is-invalid @enderror"
                                    name="deskripsi_pekerjaan" rows="4"
                                    placeholder="Jelaskan tugas dan tanggung jawab pekerjaan..." required>{{ old('deskripsi_pekerjaan', $job->deskripsi_pekerjaan) }}</textarea>
                                @error('deskripsi_pekerjaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- SEKSI 2: Waktu & Lokasi --}}
                    <div class="form-section" style="margin-top: 24px;">
                        <div class="section-label">
                            <div class="section-icon"><i class="ri-calendar-line"></i></div>
                            <span>Waktu & Lokasi</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Kuota <span class="required-star">*</span></label>
                                <input type="number" class="form-control @error('kuota') is-invalid @enderror"
                                    name="kuota" placeholder="Jumlah lowongan"
                                    value="{{ old('kuota', $job->kuota) }}" required min="1" />
                                @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tanggal Buka <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                    name="tanggal_mulai" data-provider="flatpickr"
                                    data-date-format="d M, Y" placeholder="Pilih tanggal"
                                    value="{{ old('tanggal_mulai', $job->tanggal_mulai->format('d M, Y')) }}" required />
                                @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tanggal Tutup <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                    name="tanggal_selesai" data-provider="flatpickr"
                                    data-date-format="d M, Y" placeholder="Pilih tanggal"
                                    value="{{ old('tanggal_selesai', $job->tanggal_selesai->format('d M, Y')) }}" required />
                                @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Lokasi <span class="required-star">*</span></label>
                                <input type="text" class="form-control @error('lokasi') is-invalid @enderror"
                                    name="lokasi" placeholder="Kota / Daerah"
                                    value="{{ old('lokasi', $job->lokasi) }}" required />
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

                        @php $gajiChecked = old('tampilkan_gaji', $job->tampilkan_gaji ?? false); @endphp

                        <label class="gaji-toggle-wrap" for="tampilkan_gaji_toggle">
                            <span class="label-text">
                                <i class="ri-eye-line"></i>
                                Tampilkan rentang gaji kepada pelamar
                            </span>
                            <label class="toggle-switch" onclick="event.stopPropagation()">
                                <input type="checkbox" id="tampilkan_gaji_toggle" name="tampilkan_gaji" value="1"
                                    {{ $gajiChecked ? 'checked' : '' }}
                                    onchange="toggleGaji(this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                        <input type="hidden" name="tampilkan_gaji" value="0" id="tampilkan_gaji_hidden"
                            {{ $gajiChecked ? 'disabled' : '' }}>

                        <div id="gaji-section" class="{{ $gajiChecked ? 'show' : '' }}">
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Gaji Minimum (Rp)</label>
                                    <input type="number" class="form-control @error('gaji_min') is-invalid @enderror"
                                        name="gaji_min" placeholder="Contoh: 3000000"
                                        value="{{ old('gaji_min', $job->gaji_min) }}" />
                                    @error('gaji_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gaji Maksimum (Rp)</label>
                                    <input type="number" class="form-control @error('gaji_max') is-invalid @enderror"
                                        name="gaji_max" placeholder="Contoh: 6000000"
                                        value="{{ old('gaji_max', $job->gaji_max) }}" />
                                    @error('gaji_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SEKSI 4: Persyaratan & Fasilitas --}}
                    <div class="form-section" style="margin-top: 24px;">
                        <div class="section-label">
                            <div class="section-icon"><i class="ri-list-check-2"></i></div>
                            <span>Persyaratan & Fasilitas</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">Persyaratan Umum</label>
                                <textarea class="form-control @error('persyaratan_umum') is-invalid @enderror"
                                    id="persyaratan_umum" name="persyaratan_umum" rows="4"
                                    placeholder="- WNI&#10;- Usia minimal 18 tahun">{{ old('persyaratan_umum', is_array($job->persyaratan_umum) ? implode("\n", $job->persyaratan_umum) : (is_string($job->persyaratan_umum) ? implode("\n", json_decode($job->persyaratan_umum, true) ?? []) : '')) }}</textarea>
                                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                                @error('persyaratan_umum')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Persyaratan Khusus</label>
                                <textarea class="form-control @error('persyaratan_khusus') is-invalid @enderror"
                                    id="persyaratan_khusus" name="persyaratan_khusus" rows="4"
                                    placeholder="- Menguasai Ms. Office">{{ old('persyaratan_khusus', is_array($job->persyaratan_khusus) ? implode("\n", $job->persyaratan_khusus) : (is_string($job->persyaratan_khusus) ? implode("\n", json_decode($job->persyaratan_khusus, true) ?? []) : '')) }}</textarea>
                                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                                @error('persyaratan_khusus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Fasilitas & Tunjangan</label>
                                <textarea class="form-control @error('fasilitas') is-invalid @enderror"
                                    id="fasilitas" name="fasilitas" rows="4"
                                    placeholder="- BPJS Kesehatan&#10;- Tunjangan Transportasi">{{ old('fasilitas', is_array($job->fasilitas) ? implode("\n", $job->fasilitas) : (is_string($job->fasilitas) ? implode("\n", json_decode($job->fasilitas, true) ?? []) : '')) }}</textarea>
                                <div class="form-hint"><i class="ri-information-line"></i> Satu poin per baris</div>
                                @error('fasilitas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Kompetensi / Skill yang Dibutuhkan</label>
                                <input class="form-control @error('kompetensi_dibutuhkan') is-invalid @enderror"
                                    id="kompetensi_dibutuhkan" name="kompetensi_dibutuhkan"
                                    data-choices data-choices-text-unique-true type="text"
                                    value="{{ old('kompetensi_dibutuhkan', $job->kompetensi_dibutuhkan) }}" />
                                <div class="form-hint"><i class="ri-information-line"></i> Ketik skill lalu tekan Enter</div>
                                @error('kompetensi_dibutuhkan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- SEKSI 5: Status --}}
                    <div class="form-section" style="margin-top: 24px;">
                        <div class="section-label">
                            <div class="section-icon"><i class="ri-settings-3-line"></i></div>
                            <span>Status Publikasi</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status Lowongan</label>
                                <select class="form-control @error('status') is-invalid @enderror"
                                    data-choices name="status">
                                    <option value="draft" {{ old('status', $job->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="aktif" {{ old('status', $job->status) == 'aktif' ? 'selected' : '' }}>Aktif / Dipublikasikan</option>
                                    <option value="ditutup" {{ old('status', $job->status) == 'ditutup' ? 'selected' : '' }}>Ditutup</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="form-footer">
                    <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn-cancel">
                        <i class="ri-close-line"></i> Batal
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="ri-save-line"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
    function toggleGaji(checkbox) {
        const section = document.getElementById('gaji-section');
        const hidden = document.getElementById('tampilkan_gaji_hidden');
        if (checkbox.checked) {
            section.classList.add('show');
            hidden.disabled = true;
        } else {
            section.classList.remove('show');
            hidden.disabled = false;
        }
    }

    document.getElementById('jobForm').addEventListener('submit', function (e) {
        e.preventDefault();
        ['persyaratan_umum', 'persyaratan_khusus', 'fasilitas'].forEach(id => {
            let textarea = document.getElementById(id);
            if (textarea && textarea.value.trim()) {
                let lines = textarea.value.split('\n').map(l => l.replace(/^-\s*/, '').trim()).filter(l => l !== '');
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = id + '_json';
                hidden.value = JSON.stringify(lines);
                textarea.parentNode.appendChild(hidden);
            }
        });
        this.submit();
    });
</script>
@endsection