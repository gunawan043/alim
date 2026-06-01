@extends('layouts.master')
@section('title') Konversi Pelamar → GTK @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .convert-card { border: 0; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
    .profile-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); border-radius: 16px 16px 0 0; padding: 24px; }
    .profile-header .avatar-lg { width: 72px; height: 72px; }
    .section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: .8px; color: var(--bs-secondary-color); font-weight: 700; }
    .form-section { border: 1px solid var(--bs-border-color); border-radius: 12px; padding: 20px; margin-bottom: 16px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2_link') {{ route('user.ats.applications.index', ['userId' => $userId]) }} @endslot
    @slot('li_2') Lamaran @endslot
    @slot('li_3_link') {{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]) }} @endslot
    @slot('li_3') #{{ Str::limit($application->no_lamaran, 8, '') }} @endslot
    @slot('title') Konversi → GTK @endslot
@endcomponent

<div class="row">
    <div class="col-xl-10 mx-auto">
        {{-- Profile Header --}}
        <div class="profile-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $application->recruitmentProfile->user->avatar
                    ? URL::asset('images/'.$application->recruitmentProfile->user->avatar)
                    : URL::asset('build/images/users/avatar-1.jpg') }}"
                    alt="" class="img-thumbnail rounded-circle border-2 border-white">
                <div>
                    <h4 class="text-white mb-1">{{ $application->recruitmentProfile->user->name }}</h4>
                    <p class="text-white text-opacity-75 mb-0">
                        {{ $application->recruitmentJob->judul }}
                        <span class="mx-2">•</span>
                        {{ $application->recruitmentProfile->nik ?? 'NIK belum ada' }}
                    </p>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="ri-user-follow-line me-1"></i> Diterima
                    </span>
                </div>
            </div>
        </div>

        {{-- Alert --}}
        <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
            <i class="ri-information-line fs-5 me-2 mt-1"></i>
            <div>
                <strong>Catatan:</strong> Data pelamar akan dipindahkan ke modul GTK sebagai pegawai baru.
                Pastikan semua data sudah benar sebelum melakukan konversi. Tindakan ini tidak dapat dibatalkan.
            </div>
        </div>

        <form action="{{ route('user.ats.applications.convert', ['userId' => $userId, 'application' => $application->id]) }}"
            method="POST" id="convertForm">
            @csrf

            {{-- Data GTK --}}
            <div class="card convert-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data GTK Baru</h5>
                </div>
                <div class="card-body">
                    <div class="form-section">
                        <div class="section-title mb-3">Informasi Jabatan & Penempatan</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jenis GTK <span class="text-danger">*</span></label>
                                <select class="form-select" name="jenis_gtk" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="guru">Guru</option>
                                    <option value="tendik">Tenaga Kependidikan</option>
                                    <option value="staf">Staf Administrasi</option>
                                    <option value="kopf">Kepala Program/Fasilitator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status Kepegawaian <span class="text-danger">*</span></label>
                                <select class="form-select" name="status_kepegawaian" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="tetap">Pegawai Tetap</option>
                                    <option value="kontrak">Kontrak</option>
                                    <option value="probation"> masa Probation</option>
                                    <option value="magang">Magang</option>
                                    <option value="honor">Honor / harian</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Unit Kerja / Divisi <span class="text-danger">*</span></label>
                                <select class="form-select" name="unit_kerja" required>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($workUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Posisi / Jabatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="jabatan"
                                    value="{{ $application->recruitmentJob->posisi ?? $application->recruitmentJob->judul }}"
                                    placeholder="Contoh: Guru Matematika" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">TMT (Tanggal Mulai Kerja) <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tmt" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Penempatan / Lokasi</label>
                                <input type="text" class="form-control" name="penempatan"
                                    value="{{ $application->recruitmentJob->penempatan ?? '' }}"
                                    placeholder="Lokasi penugasan...">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title mb-3">Kontrak Kerja</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Jenis Kontrak</label>
                                <select class="form-select" name="kontrak_jenis">
                                    <option value="pkwt">PKWT</option>
                                    <option value="pkwt_perpanjangan">PKWT Perpanjangan</option>
                                    <option value="magang">Magang</option>
                                    <option value="mitra">Mitra</option>
                                    <option value="perjanjian_kerja_harian">Perjanjian Kerja Harian</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tanggal Berakhir</label>
                                <input type="date" class="form-control" name="kontrak_berakhir" value="{{ now()->addYear()->toDateString() }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Durasi (bulan)</label>
                                <input type="number" class="form-control" name="durasi_bulan" value="12" min="1" max="60">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Salary --}}
            <div class="card convert-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-wallet-line me-2 text-primary"></i>Gaji Awal</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Gaji Pokok</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="gaji_pokok" placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tunjangan Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="tunjangan_tetap" placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tunjangan Tidak Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="tunjangan_tidak_tetap" placeholder="0" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]) }}"
                    class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Batal
                </a>
                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="save_draft" class="btn btn-light">
                        <i class="ri-save-line me-1"></i> Simpan Draft
                    </button>
                    <button type="submit" name="action" value="convert" class="btn btn-success px-4">
                        <i class="ri-user-follow-line me-1"></i> Konversi Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.getElementById('convertForm').addEventListener('submit', function(e) {
        const btn = e.submitter;
        if (btn && btn.value === 'convert') {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Konversi',
                text: 'Pelamar akan dikonversi menjadi GTK. Data tidak bisa dikembalikan. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#34c38f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Konversi!',
                cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    // Replace button text
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengonversi...';
                    btn.disabled = true;
                    // Submit the form
                    const form = document.getElementById('convertForm');
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'convert';
                    form.appendChild(actionInput);
                    form.submit();
                }
            });
        }
    });
</script>
@endsection