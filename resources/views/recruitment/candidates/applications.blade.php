@extends('layouts.master')
@section('title')
    Lamaran - {{ $candidate->user->name }}
@endsection
@php $userId = $userId ?? request()->route('userId') ?? Auth::id(); @endphp
@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}">
    <style>
        .info-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
        .profile-stat { background: rgba(var(--bs-body-bg-rgb), 0.1); border-radius: 8px; padding: 1rem; transition: all 0.3s ease; }
        .profile-stat:hover { background: rgba(var(--bs-body-bg-rgb), 0.15); transform: translateY(-2px); }
        .detail-label { font-weight: 600; color: var(--bs-secondary-color); min-width: 180px; }
        .detail-value { color: var(--bs-body-color); }
        .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; background: var(--bs-tertiary-bg); }
        .contact-item { border-bottom: 1px solid var(--bs-border-color); padding: 12px 0; }
        .contact-item:last-child { border-bottom: none; }
        .private-data { border-radius: 4px; padding: 4px 8px; background: var(--bs-tertiary-bg); color: var(--bs-body-color); font-family: monospace; }
        .profile-wrapper { background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.9), rgba(var(--bs-success-rgb), 0.7)); border-radius: 12px; padding: 20px; margin-top: -30px; position: relative; z-index: 10; }
        .app-card { transition: all 0.3s ease; border: 1px solid var(--bs-border-color); }
        .app-card:hover { transform: translateY(-3px); box-shadow: var(--bs-box-shadow); }
        [data-bs-theme="dark"] .profile-wrapper { background: linear-gradient(to right, rgba(13, 110, 253, 0.8), rgba(25, 135, 84, 0.6)); }
        [data-bs-theme="dark"] .private-data { background: rgba(var(--bs-body-bg-rgb), 0.1); }
    </style>
@endsection

@section('content')
    @php
        $totalApps   = $applications->count();
        $activeApps  = $applications->whereIn('status', [
            'menunggu_seleksi','seleksi_administrasi','lolos_administrasi',
            'tes_tertulis','lolos_tes','wawancara','lolos_wawancara',
            'penawaran_kerja'
        ])->count();
        $acceptedApps = $applications->where('status', 'diterima')->count();
        $rejectedApps = $applications->whereIn('status', [
            'tidak_lolos_administrasi','tidak_lolos_tes','tidak_lolos_wawancara','ditolak'
        ])->count();
    @endphp

    <!-- Header Profile -->
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/profile-bg.jpg') }}" alt="" class="profile-wid-img" />
        </div>
    </div>

    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg position-relative">
                    <img src="{{ $candidate->user->avatar ? URL::asset('images/' . $candidate->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                        alt="" class="img-thumbnail rounded-circle shadow" />
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h3 class="text-white mb-0">{{ $candidate->user->name }}</h3>
                        <span class="badge bg-{{ $totalApps > 0 ? 'success' : 'secondary' }}-subtle text-{{ $totalApps > 0 ? 'success' : 'secondary' }}">
                            <i class="ri-file-list-line me-1"></i> {{ $totalApps }} Lamaran
                        </span>
                    </div>
                    <div class="hstack text-white-50 gap-1">
                        <div class="me-2">
                            <i class="ri-map-pin-user-line me-1"></i>{{ $candidate->provinsi ?? 'Indonesia' }}
                        </div>
                        <div>
                            <i class="ri-mail-line me-1"></i>{{ $candidate->user->email }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-auto order-last order-lg-0">
                <div class="row text text-white-50 text-center">
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $totalApps }}</h4>
                            <p class="fs-14 mb-0">Total Lamaran</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $activeApps }}</h4>
                            <p class="fs-14 mb-0">Aktif</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $acceptedApps }}</h4>
                            <p class="fs-14 mb-0">Diterima</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $candidate->id]) }}"
                    class="btn btn-light me-2">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                </a>
                <h4 class="flex-grow-1 mb-0">Riwayat Lamaran</h4>
            </div>

            @forelse($applications as $app)
                @php
                    $statusConfig = [
                        'menunggu_seleksi'            => ['bg' => 'secondary', 'icon' => 'ri-time-line',         'label' => 'Menunggu Seleksi'],
                        'seleksi_administrasi'        => ['bg' => 'info',     'icon' => 'ri-file-search-line', 'label' => 'Seleksi Administrasi'],
                        'lolos_administrasi'          => ['bg' => 'primary',  'icon' => 'ri-check-line',       'label' => 'Lolos Administrasi'],
                        'tidak_lolos_administrasi'    => ['bg' => 'danger',   'icon' => 'ri-close-line',       'label' => 'Tidak Lolos Adm'],
                        'tes_tertulis'                => ['bg' => 'warning',  'icon' => 'ri-file-text-line',    'label' => 'Tes Tertulis'],
                        'lolos_tes'                   => ['bg' => 'primary',  'icon' => 'ri-check-line',       'label' => 'Lolos Tes'],
                        'tidak_lolos_tes'             => ['bg' => 'danger',   'icon' => 'ri-close-line',       'label' => 'Tidak Lolos Tes'],
                        'wawancara'                   => ['bg' => 'warning',  'icon' => 'ri-user-follow-line',  'label' => 'Wawancara'],
                        'lolos_wawancara'             => ['bg' => 'primary',  'icon' => 'ri-check-line',       'label' => 'Lolos Wawancara'],
                        'tidak_lolos_wawancara'       => ['bg' => 'danger',   'icon' => 'ri-close-line',       'label' => 'Tidak Lolos Wawancara'],
                        'penawaran_kerja'            => ['bg' => 'info',     'icon' => 'ri-hand-coin-line',   'label' => 'Penawaran Kerja'],
                        'diterima'                    => ['bg' => 'success',  'icon' => 'ri-checkbox-circle-line','label' => 'Diterima'],
                        'ditolak'                     => ['bg' => 'danger',   'icon' => 'ri-forbid-line',       'label' => 'Ditolak'],
                    ];
                    $cfg = $statusConfig[$app->status] ?? ['bg' => 'secondary', 'icon' => 'ri-circle-fill', 'label' => $app->status];
                @endphp

                <div class="card app-card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            {{-- Job Info --}}
                            <div class="col-md-5">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="icon-circle bg-{{ $cfg['bg'] }}-subtle flex-shrink-0">
                                        <i class="ri-briefcase-line text-{{ $cfg['bg'] }} fs-18"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">{{ $app->recruitmentJob->judul ?? '-' }}</h5>
                                        <p class="text-muted mb-1">
                                            <i class="ri-building-line me-1"></i>
                                            {{ $app->recruitmentJob->recruitmentDepartment->nama_departemen ?? '-' }}
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="ri-map-pin-2-line me-1"></i>
                                            {{ $app->recruitmentJob->lokasi ?? '-' }}
                                            @if ($app->recruitmentJob->jenis_pekerjaan)
                                                &bull; {{ $app->recruitmentJob->jenis_pekerjaan }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Application Meta --}}
                            <div class="col-md-3">
                                <div class="contact-item">
                                    <span class="text-muted" style="font-size:0.8rem">No. Lamaran</span>
                                    <div class="fw-semibold">#{{ $app->no_lamaran }}</div>
                                </div>
                                <div class="contact-item">
                                    <span class="text-muted" style="font-size:0.8rem">Tanggal Melamar</span>
                                    <div class="fw-semibold">{{ $app->tanggal_melamar->format('d M Y') }}</div>
                                </div>
                            </div>

                            {{-- Status & Score --}}
                            <div class="col-md-2">
                                <div class="d-flex flex-column gap-2">
                                    <span class="badge bg-{{ $cfg['bg'] }}-subtle text-{{ $cfg['bg'] }} p-2">
                                        <i class="{{ $cfg['icon'] }} me-1"></i>{{ $cfg['label'] }}
                                    </span>
                                    @if ($app->skor_administrasi)
                                        <small class="text-muted">Skor Adm: <strong>{{ $app->skor_administrasi }}</strong></small>
                                    @endif
                                    @if ($app->nilai_tes)
                                        <small class="text-muted">Nilai Tes: <strong>{{ $app->nilai_tes }}</strong></small>
                                    @endif
                                    @if ($app->nilai_wawancara)
                                        <small class="text-muted">Nilai Wawancara: <strong>{{ $app->nilai_wawancara }}</strong></small>
                                    @endif
                                    @if ($app->nilai_akhir)
                                        <small class="text-muted fw-bold">Nilai Akhir: {{ number_format($app->nilai_akhir, 2) }}</small>
                                    @endif
                                    @if ($app->ranking)
                                        <span class="badge bg-info-subtle text-info">Ranking #{{ $app->ranking }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="col-md-2 text-md-end">
                                <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}"
                                    class="btn btn-sm btn-primary w-100 mb-2">
                                    <i class="ri-eye-line me-1"></i> Detail
                                </a>
                                @if ($app->recruitmentJob)
                                    <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $app->recruitmentJob->id]) }}"
                                        class="btn btn-sm btn-soft-secondary w-100">
                                        <i class="ri-briefcase-line me-1"></i> Lihat Lowongan
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Pipeline Stages --}}
                        @if ($app->stages && $app->stages->count() > 0)
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($app->stages as $stage)
                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size:0.8rem">
                                            <i class="ri-checkbox-circle-fill text-success"></i>
                                            <span>{{ $stage->recruitmentPipelineStage->nama_tahapan ?? $stage->stage_name ?? '-' }}</span>
                                            @if ($stage->nilai)
                                                <span class="badge bg-success-subtle text-success ms-1"
                                                    style="font-size:0.7rem">Nilai: {{ $stage->nilai }}</span>
                                            @endif
                                            @if ($stage->jadwal_mulai)
                                                <span class="text-muted ms-1">
                                                    <i class="ri-calendar-line"></i>
                                                    {{ \Carbon\Carbon::parse($stage->jadwal_mulai)->format('d/m') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title rounded-circle bg-secondary-subtle">
                                <i class="ri-file-list-2-line fs-32 text-secondary"></i>
                            </div>
                        </div>
                        <h5 class="mb-2">Belum Ada Lamaran</h5>
                        <p class="text-muted mb-4">Belum ada lamaran yang diajukan oleh kandidat ini.</p>
                        <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}"
                            class="btn btn-primary">
                            <i class="ri-briefcase-line me-1"></i> Lihat Lowongan
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/profile.init.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection