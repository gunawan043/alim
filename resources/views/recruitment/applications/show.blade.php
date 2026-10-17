@extends('layouts.master')

@section('title', 'Detail Lamaran #' . $application->no_lamaran)

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <style>
        .acitivity-timeline { max-height: 400px; overflow-y: auto; padding-right: 10px; }
        .acitivity-item { border-left: 2px solid #e2e8f0; padding-left: 20px; margin-left: 10px; position: relative; }
        .acitivity-item:before { content: ''; position: absolute; left: -6px; top: 10px; width: 10px; height: 10px; border-radius: 50%; background: #667eea; }
        .app-wid-bg { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%) !important; }
        .profile-app-img { height: 160px; object-fit: cover; width: 100%; }
        .avatar-2xl { width: 80px !important; height: 80px !important; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2_link') {{ route('user.ats.applications.index', ['userId' => $userId]) }} @endslot
        @slot('li_2') Lamaran @endslot
        @slot('title') #{{ $application->no_lamaran }} — {{ $application->recruitmentProfile->user->name }} @endslot
    @endcomponent

    {{-- Header Banner --}}
    <div class="row">
        <div class="col-12">
            <div class="profile-wid-bg position-relative mx-n4 mt-n4">
                <img src="{{ URL::asset('build/images/profile-bg.jpg') }}" alt="" class="profile-app-img" />
                <div class="profile-wid-bg-overlay position-absolute top-0 start-0 w-100 h-100"></div>
            </div>
            <div class="card mb-0 border-0 rounded-0" style="margin-top:-1px;">
                <div class="card-body pb-0">
                    <div class="row g-4">
                        <div class="col-auto">
                            <div class="avatar-lg">
                                <img src="{{ $application->recruitmentProfile->user->avatar ? URL::asset('images/' . $application->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                                    alt="user-img" class="img-thumbnail rounded-circle" />
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2">
                                <h3 class="text-white mb-1">{{ $application->recruitmentProfile->user->name }}</h3>
                                <p class="text-white text-opacity-75 mb-1">
                                    {{ $application->recruitmentJob->judul }}
                                    <span class="mx-2">•</span>
                                    {{ $application->recruitmentJob->penempatan ?? $application->recruitmentJob->lokasi ?? '-' }}
                                </p>
                                <div class="hstack text-white-50 gap-3">
                                    <div><i class="ri-calendar-line me-1"></i>Melamar {{ $application->tanggal_melamar->format('d M Y') }}</div>
                                    <div><i class="ri-briefcase-line me-1"></i>{{ $application->status_label }}</div>
                                    <div>
                                        <span class="badge bg-{{ $application->nilai_akhir ? ($application->nilai_akhir >= 75 ? 'success' : ($application->nilai_akhir >= 60 ? 'warning' : 'danger')) : 'secondary' }}">
                                            Nilai Akhir: {{ $application->nilai_akhir ? number_format($application->nilai_akhir, 1) : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto">
                            <div class="row text text-white-50 text-center">
                                <div class="col-4">
                                    <div class="p-2">
                                        <h4 class="text-white mb-0">{{ $application->recruitmentProfile->educations->count() }}</h4>
                                        <p class="fs-14 mb-0">Pendidikan</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2">
                                        <h4 class="text-white mb-0">{{ $application->recruitmentProfile->workExperiences->count() }}</h4>
                                        <p class="fs-14 mb-0">Pengalaman</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2">
                                        <h4 class="text-white mb-0">{{ $application->recruitmentProfile->skills->count() }}</h4>
                                        <p class="fs-14 mb-0">Skills</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav Tabs + Actions --}}
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="d-flex align-items-center gap-3">
                <ul class="nav nav-pills animation-nav profile-nav gap-2 gap-lg-3 flex-grow-1" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#tab-overview" role="tab">
                            <i class="ri-airplay-fill d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-14" data-bs-toggle="tab" href="#tab-pendidikan" role="tab">
                            <i class="ri-graduation-cap-line d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Pendidikan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-14" data-bs-toggle="tab" href="#tab-pengalaman" role="tab">
                            <i class="ri-briefcase-line d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Pengalaman</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-14" data-bs-toggle="tab" href="#tab-skills" role="tab">
                            <i class="ri-price-tag-line d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Skills</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-14" data-bs-toggle="tab" href="#tab-dokumen" role="tab">
                            <i class="ri-folder-4-line d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Dokumen</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-14" data-bs-toggle="tab" href="#tab-aktivitas" role="tab">
                            <i class="ri-time-line d-inline-block d-md-none"></i>
                            <span class="d-none d-md-inline-block">Aktivitas</span>
                        </a>
                    </li>
                </ul>
                <div class="flex-shrink-0 d-flex gap-2">
                    <a href="{{ route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $application->recruitmentProfile->id]) }}" class="job-list-button btn btn-light">
                        <i class="ri-user-line align-bottom"></i> Profile
                    </a>
                    @if ($application->recruitmentProfile->documents->where('jenis_dokumen', 'cv')->first())
                        <a href="{{ route('user.ats.candidates.download-cv', ['userId' => $userId, 'candidate' => $application->recruitmentProfile->id]) }}" class="job-list-button btn btn-success">
                            <i class="ri-file-pdf-line align-bottom"></i> Download CV
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="tab-content text-muted">

                {{-- TAB: Overview --}}
                <div class="tab-pane active" id="tab-overview" role="tabpanel">
                    <div class="row">
                        {{-- Left Column --}}
                        <div class="col-xxl-3">
                            {{-- Info Pribadi --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Info Pribadi</h5></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-borderless mb-0">
                                            <tr><th class="ps-0 text-muted">NIK</th><td class="text-muted">: {{ $application->recruitmentProfile->nik ?? '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">No KK</th><td class="text-muted">: {{ $application->recruitmentProfile->no_kk ?? '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">TTL</th><td class="text-muted">: {{ $application->recruitmentProfile->tempat_lahir ?? '-' }}, {{ $application->recruitmentProfile->tanggal_lahir ? $application->recruitmentProfile->tanggal_lahir->format('d M Y') : '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">JK</th><td class="text-muted">: {{ $application->recruitmentProfile->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">Agama</th><td class="text-muted">: {{ $application->recruitmentProfile->agama ?? '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">Status</th><td class="text-muted">: {{ $application->recruitmentProfile->status_perkawinan ?? '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">No HP</th><td class="text-muted">: {{ $application->recruitmentProfile->no_hp ?? '-' }}</td></tr>
                                            <tr><th class="ps-0 text-muted">Email</th><td class="text-muted">: {{ $application->recruitmentProfile->user->email }}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Ringkasan --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Ringkasan</h5></div>
                                <div class="card-body">
                                    <p class="text-muted">{{ $application->recruitmentProfile->documents->where('jenis_dokumen', 'cv')->first()->ringkasan_profesional ?? 'Belum ada ringkasan' }}</p>
                                </div>
                            </div>

                            {{-- Kontak --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Kontak</h5></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><th class="ps-0 text-muted">Alamat</th><td class="text-muted">: {{ $application->recruitmentProfile->alamat_lengkap ?? '-' }}</td></tr>
                                        <tr><th class="ps-0 text-muted">Provinsi</th><td class="text-muted">: {{ $application->recruitmentProfile->provinsi ?? '-' }}</td></tr>
                                    </table>
                                </div>
                            </div>

                            {{-- Info Lamaran --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Info Lamaran</h5></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">No Lamaran</span><span class="fw-medium">#{{ Str::limit($application->no_lamaran, 8, '') }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tanggal</span><span class="fw-medium">{{ $application->tanggal_melamar->format('d M Y') }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Posisi</span><span class="fw-medium">{{ $application->recruitmentProfile->applied_jobs }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Status</span><span class="badge bg-{{ $application->status_color }}">{{ Str::title(str_replace('_', ' ', $application->status)) }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Skor Adm</span><span class="fw-medium">{{ $application->skor_administrasi ?? '-' }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nilai Tes</span><span class="fw-medium">{{ $application->nilai_tes ?? '-' }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nilai Wawancara</span><span class="fw-medium">{{ $application->nilai_wawancara ?? '-' }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nilai Praktikum</span><span class="fw-medium">{{ $application->nilai_praktikum ?? '-' }}</span></div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nilai Akhir</span><span class="fw-medium">{{ $application->nilai_akhir ? number_format($application->nilai_akhir, 2) : '-' }}</span></div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Progress + Form Update Status --}}
                        <div class="col-xxl-9">
                            {{-- Progress Tracker --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Progress Seleksi</h5></div>
                                <div class="card-body">
                                    @include('recruitment.applications.progress-tracker', ['application' => $application])
                                </div>
                            </div>

                            {{-- Update Status Form --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Update Status & Nilai</h5></div>
                                <div class="card-body">
                                    <form action="{{ route('user.ats.applications.update-status', ['userId' => $userId, 'application' => $application->id]) }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                <select class="form-control" name="status" required>
                                                    <option value="menunggu_seleksi" {{ $application->status == 'menunggu_seleksi' ? 'selected' : '' }}>Menunggu Seleksi</option>
                                                    <option value="seleksi_administrasi" {{ $application->status == 'seleksi_administrasi' ? 'selected' : '' }}>Seleksi Administrasi</option>
                                                    <option value="lolos_administrasi" {{ $application->status == 'lolos_administrasi' ? 'selected' : '' }}>Lolos Administrasi</option>
                                                    <option value="tidak_lolos_administrasi" {{ $application->status == 'tidak_lolos_administrasi' ? 'selected' : '' }}>Tidak Lolos Administrasi</option>
                                                    <option value="tes_tertulis" {{ $application->status == 'tes_tertulis' ? 'selected' : '' }}>Tes Tertulis</option>
                                                    <option value="lolos_tes" {{ $application->status == 'lolos_tes' ? 'selected' : '' }}>Lolos Tes</option>
                                                    <option value="tidak_lolos_tes" {{ $application->status == 'tidak_lolos_tes' ? 'selected' : '' }}>Tidak Lolos Tes</option>
                                                    <option value="wawancara" {{ $application->status == 'wawancara' ? 'selected' : '' }}>Wawancara</option>
                                                    <option value="lolos_wawancara" {{ $application->status == 'lolos_wawancara' ? 'selected' : '' }}>Lolos Wawancara</option>
                                                    <option value="tidak_lolos_wawancara" {{ $application->status == 'tidak_lolos_wawancara' ? 'selected' : '' }}>Tidak Lolos Wawancara</option>
                                                    <option value="penawaran_kerja" {{ $application->status == 'penawaran_kerja' ? 'selected' : '' }}>Penawaran Kerja</option>
                                                    <option value="diterima" {{ $application->status == 'diterima' ? 'selected' : '' }}>Diterima</option>
                                                    <option value="ditolak" {{ $application->status == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Skor Administrasi (0-100)</label>
                                                <input type="number" step="0.01" class="form-control" name="skor_administrasi" value="{{ $application->skor_administrasi }}" placeholder="0 - 100">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nilai Tes (0-100)</label>
                                                <input type="number" step="0.01" class="form-control" name="nilai_tes" value="{{ $application->nilai_tes }}" placeholder="0 - 100">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nilai Wawancara (0-100)</label>
                                                <input type="number" step="0.01" class="form-control" name="nilai_wawancara" value="{{ $application->nilai_wawancara }}" placeholder="0 - 100">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nilai Praktikum (0-100)</label>
                                                <input type="number" step="0.01" class="form-control" name="nilai_praktikum" value="{{ $application->nilai_praktikum }}" placeholder="0 - 100">
                                            </div>

                                            {{-- Detail Penilaian per-Kriteria --}}
                                            <div class="col-12">
                                                <hr class="my-2">
                                                <h6 class="text-muted mb-2"><i class="ri-list-check-2"></i> Detail Penilaian per-Kriteria</h6>
                                                <div class="row g-2">
                                                    @php
                                                        $kriteria = [
                                                            'komunikasi' => 'Komunikasi',
                                                            'attitude' => 'Attitude / Sikap',
                                                            'teknis' => 'Kompetensi Teknis',
                                                            'leadership' => 'Leadership',
                                                            'problem_solving' => 'Problem Solving',
                                                            'kerjasama_tim' => 'Kerja Sama Tim',
                                                        ];
                                                        $existingDetail = is_array($application->detail_penilaian)
                                                            ? $application->detail_penilaian
                                                            : (json_decode($application->detail_penilaian ?? '{}', true) ?: []);
                                                    @endphp
                                                    @foreach($kriteria as $key => $label)
                                                        <div class="col-md-4">
                                                            <label class="form-label small">{{ $label }} (0-100)</label>
                                                            <input type="number" step="0.01" min="0" max="100"
                                                                class="form-control form-control-sm"
                                                                name="detail_penilaian[{{ $key }}]"
                                                                value="{{ $existingDetail[$key] ?? '' }}"
                                                                placeholder="0 - 100">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Catatan</label>
                                                <textarea class="form-control" name="catatan" rows="2" placeholder="Tambahkan catatan proses seleksi...">{{ $application->catatan_rekruter }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan</button>
                                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scheduleModal"><i class="ri-calendar-line"></i> Jadwalkan Interview</button>
                                                <button type="button" class="btn btn-info" onclick="sendMessage()"><i class="ri-mail-send-line"></i> Kirim Pesan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Aktivitas Singkat --}}
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Aktivitas</h5></div>
                                <div class="card-body">
                                    <div class="acitivity-timeline">
                                        @forelse($application->stages->sortByDesc('created_at') as $stage)
                                            <div class="acitivity-item d-flex py-2">
                                                <div class="flex-shrink-0 avatar-xs">
                                                    <div class="avatar-title rounded-circle bg-{{ $stage->status == 'diterima' ? 'success' : ($stage->status == 'ditolak' ? 'danger' : 'info') }}-subtle text-{{ $stage->status == 'diterima' ? 'success' : ($stage->status == 'ditolak' ? 'danger' : 'info') }}">
                                                        <i class="ri-timeline-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">{{ Str::title(str_replace('_', ' ', $stage->status)) }}
                                                        @if ($stage->nilai)<span class="badge bg-success ms-2">{{ $stage->nilai }}</span>@endif
                                                    </h6>
                                                    <p class="text-muted mb-1">{{ $stage->catatan ?? '-' }}</p>
                                                    <small class="text-muted">{{ $stage->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-muted text-center mb-0">Belum ada aktivitas</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Pendidikan --}}
                <div class="tab-pane fade" id="tab-pendidikan" role="tabpanel">
                    <div class="row g-3">
                        @forelse($application->recruitmentProfile->educations->sortByDesc('tahun_lulus') as $edu)
                            <div class="col-xl-4 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <div class="avatar-title bg-{{ $edu->jenjang == 's1' || $edu->jenjang == 's2' ? 'success' : 'info' }}-subtle rounded">
                                                    <i class="ri-graduation-cap-line fs-20 text-{{ $edu->jenjang == 's1' || $edu->jenjang == 's2' ? 'success' : 'info' }}"></i>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="fs-15 mb-1">{{ Str::upper($edu->jenjang) }}</h6>
                                                <p class="text-muted mb-0 fs-13">{{ $edu->jurusan }}</p>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-1"><i class="ri-building-line me-1"></i>{{ $edu->nama_satuan_pendidikan }}</p>
                                        <p class="text-muted mb-1"><i class="ri-calendar-line me-1"></i>{{ $edu->tahun_masuk }} – {{ $edu->tahun_lulus }}</p>
                                        @if ($edu->ipk)<p class="text-muted mb-2"><i class="ri-star-line me-1"></i>IPK: {{ $edu->ipk }}</p>@endif
                                        @if ($edu->ijazah_path)
                                            <a href="{{ asset('storage/' . $edu->ijazah_path) }}" target="_blank" class="btn btn-sm btn-soft-primary mt-2"><i class="ri-file-pdf-line"></i> Lihat Ijazah</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12"><div class="alert alert-warning mb-0"><i class="ri-alert-line me-2"></i>Belum ada data pendidikan.</div></div>
                        @endforelse
                    </div>
                </div>

                {{-- TAB: Pengalaman --}}
                <div class="tab-pane fade" id="tab-pengalaman" role="tabpanel">
                    <div class="row g-3">
                        @forelse($application->recruitmentProfile->workExperiences as $exp)
                            <div class="col-xl-6 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <div class="avatar-title bg-primary-subtle rounded"><i class="ri-briefcase-line fs-20 text-primary"></i></div>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="fs-15 mb-1">{{ $exp->posisi_terakhir }}</h6>
                                                <p class="text-muted mb-0 fs-13">{{ $exp->nama_perusahaan }}</p>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-1"><i class="ri-calendar-line me-1"></i>{{ $exp->tanggal_mulai->format('M Y') }} – {{ $exp->is_saat_ini ? 'Sekarang' : ($exp->tanggal_selesai ? $exp->tanggal_selesai->format('M Y') : '-') }}
                                            <span class="badge bg-secondary ms-1">{{ floor($exp->lama_bekerja_bulan / 12) }} th {{ $exp->lama_bekerja_bulan % 12 }} bl</span>
                                        </p>
                                        @if ($exp->jobdesc)<p class="text-muted mb-2">{{ $exp->jobdesc }}</p>@endif
                                        @if ($exp->pencapaian)
                                            <div class="mt-2">
                                                <strong class="fs-13">Pencapaian:</strong>
                                                <ul class="mb-0 ps-3">
                                                    @foreach (is_array($exp->pencapaian) ? $exp->pencapaian : json_decode($exp->pencapaian, true) ?? [] as $achievement)
                                                        <li class="text-muted fs-13">{{ $achievement }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12"><div class="alert alert-warning mb-0"><i class="ri-alert-line me-2"></i>Belum ada pengalaman kerja.</div></div>
                        @endforelse
                    </div>
                </div>

                {{-- TAB: Skills --}}
                <div class="tab-pane fade" id="tab-skills" role="tabpanel">
                    <div class="row">
                        <div class="col-xl-6 col-md-6">
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Technical Skills</h5></div>
                                <div class="card-body">
                                    @forelse($application->recruitmentProfile->skills->where('kategori', 'teknis') as $skill)
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="fw-medium fs-13">{{ $skill->nama_skill }}</span>
                                                <span class="text-muted fs-13">{{ $skill->level ?? '-' }}</span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                @php $lv = $skill->level == 'Pemula' ? 30 : ($skill->level == 'Menengah' ? 60 : ($skill->level == 'Ahli' ? 90 : 50)); @endphp
                                                <div class="progress-bar bg-success" style="width:{{ $lv }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">Tidak ada skill teknis.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Soft Skills</h5></div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($application->recruitmentProfile->skills->where('kategori', 'non_teknis') as $skill)
                                            <span class="badge bg-info-subtle text-info p-2">{{ $skill->nama_skill }}</span>
                                        @empty
                                            <p class="text-muted mb-0">Tidak ada soft skill.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Bahasa</h5></div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($application->recruitmentProfile->skills->where('kategori', 'bahasa') as $skill)
                                            <span class="badge bg-warning-subtle text-warning p-2">{{ $skill->nama_skill }}</span>
                                        @empty
                                            <p class="text-muted mb-0">Tidak ada data bahasa.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Sertifikasi</h5></div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($application->recruitmentProfile->skills->where('kategori', 'sertifikasi') as $skill)
                                            <span class="badge bg-primary-subtle text-primary p-2">{{ $skill->nama_skill }}</span>
                                        @empty
                                            <p class="text-muted mb-0">Tidak ada sertifikasi.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header"><h5 class="card-title mb-0">Pelatihan</h5></div>
                                <div class="card-body">
                                    @forelse($application->recruitmentProfile->trainings as $training)
                                        <div class="d-flex mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <div class="avatar-title bg-warning-subtle rounded"><i class="ri-award-line fs-20 text-warning"></i></div>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="fs-15 mb-1">{{ $training->nama_pelatihan }}</h6>
                                                <p class="text-muted mb-1">{{ $training->penyelenggara }} — {{ $training->tahun }}</p>
                                                @if ($training->sertifikat_path)
                                                    <a href="{{ asset('storage/' . $training->sertifikat_path) }}" target="_blank" class="btn btn-sm btn-soft-warning"><i class="ri-file-pdf-line"></i> Sertifikat</a>
                                                @endif
                                            </div>
                                        </div>
                                        @if (!$loop->last)<hr>@endif
                                    @empty
                                        <p class="text-muted mb-0">Belum ada pelatihan.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Dokumen --}}
                <div class="tab-pane fade" id="tab-dokumen" role="tabpanel">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Dokumen Pelamar</h5></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama File</th>
                                            <th>Jenis</th>
                                            <th>Ukuran</th>
                                            <th>Tanggal Upload</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($application->recruitmentProfile->documents as $doc)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-sm">
                                                            <div class="avatar-title bg-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}-subtle rounded fs-20">
                                                                <i class="ri-file-{{ $doc->file_extension ?? 'file' }}-line"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="fs-15 mb-0">{{ $doc->nama_dokumen }}</h6>
                                                            @if ($doc->is_primary)<span class="badge bg-success">Primary</span>@endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-secondary">{{ Str::upper($doc->jenis_dokumen) }}</span></td>
                                                <td class="text-muted">{{ $doc->file_size ?? '-' }}</td>
                                                <td class="text-muted">{{ $doc->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-soft-primary"><i class="ri-download-line"></i></a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">Belum ada dokumen</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Aktivitas --}}
                <div class="tab-pane fade" id="tab-aktivitas" role="tabpanel">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Riwayat Aktivitas</h5></div>
                        <div class="card-body">
                            <div class="acitivity-timeline">
                                @forelse($application->stages->sortByDesc('created_at') as $stage)
                                    <div class="acitivity-item d-flex py-2">
                                        <div class="flex-shrink-0 avatar-xs">
                                            <div class="avatar-title rounded-circle bg-{{ $stage->status == 'diterima' ? 'success' : ($stage->status == 'ditolak' ? 'danger' : 'info') }}-subtle text-{{ $stage->status == 'diterima' ? 'success' : ($stage->status == 'ditolak' ? 'danger' : 'info') }}
                                                ">
                                                <i class="ri-timeline-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">{{ Str::title(str_replace('_', ' ', $stage->status)) }}
                                                @if ($stage->nilai)<span class="badge bg-success ms-2">{{ $stage->nilai }}</span>@endif
                                            </h6>
                                            <p class="text-muted mb-1">{{ $stage->catatan ?? '-' }}</p>
                                            @if ($stage->jadwal_mulai)
                                                <p class="text-muted mb-1"><i class="ri-calendar-line"></i> {{ \Carbon\Carbon::parse($stage->jadwal_mulai)->format('d M Y H:i') }} {{ $stage->lokasi ? '— ' . $stage->lokasi : '' }}</p>
                                            @endif
                                            <small class="text-muted">{{ $stage->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4"><p class="text-muted mb-0">Belum ada aktivitas</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Jadwalkan Interview --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Jadwalkan Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('user.ats.interviews.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tahapan <span class="text-danger">*</span></label>
                            <select class="form-control" name="stage_name" required>
                                <option value="">Pilih Tahapan</option>
                                <option>Seleksi Administrasi</option>
                                <option>Tes Tertulis</option>
                                <option>Wawancara HR</option>
                                <option>Wawancara User</option>
                                <option>Tes Praktek</option>
                                <option>Medical Checkup</option>
                                <option>Psychological Test</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="jadwal_mulai" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="datetime-local" class="form-control" name="jadwal_selesai">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi / Metode</label>
                                <input type="text" class="form-control" name="lokasi" placeholder="Online / Kantor cabang X">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Penilai</label>
                                <select class="form-control" name="penilai_id">
                                    <option value="">Pilih Penilai</option>
                                    @foreach ($interviewers ?? [] as $interviewer)
                                        <option value="{{ $interviewer->id }}">{{ $interviewer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function sendMessage() {
            Swal.fire({
                title: 'Kirim Pesan ke Pelamar',
                html: '<textarea id="messageText" class="swal2-textarea" placeholder="Tulis pesan Anda..."></textarea><small class="text-muted d-block mt-2">Pesan akan dikirim via email dan notifikasi.</small>',
                showCancelButton: true, confirmButtonText: 'Kirim', cancelButtonText: 'Batal', focusConfirm: false,
                preConfirm: () => {
                    const msg = document.getElementById('messageText').value;
                    if (!msg.trim()) { Swal.showValidationMessage('Pesan tidak boleh kosong'); return false; }
                    return msg;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('user.ats.applications.send-message', ['userId' => $userId, 'application' => $application->id]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ message: result.value })
                    }).then(r => r.json()).then(d => {
                        Swal.fire(d.success ? 'Terkirim!' : 'Gagal!', d.message, d.success ? 'success' : 'error');
                    }).catch(() => { Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error'); });
                }
            });
        }
    </script>
@endsection
