@extends('layouts.master')
@section('title')
    Profile {{ $candidate->user->name }}
@endsection
@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}">
@endsection
@section('content')
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ URL::asset('build/images/profile-bg.jpg') }}" alt="" class="profile-wid-img" />
        </div>
    </div>

    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg">
                    <img src="{{ $candidate->user->avatar ? URL::asset('images/' . $candidate->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                        alt="user-img" class="img-thumbnail rounded-circle" />
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <h3 class="text-white mb-1">{{ $candidate->user->name }}</h3>
                    <p class="text-white text-opacity-75">
                        @foreach ($candidate->skills->take(3) as $skill)
                            {{ $skill->nama_skill }}@if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </p>
                    <div class="hstack text-white-50 gap-1">
                        <div class="me-2"><i
                                class="ri-map-pin-user-line me-1"></i>{{ $candidate->provinsi ?? 'Indonesia' }}</div>
                        <div><i
                                class="ri-building-line me-1"></i>{{ $candidate->workExperiences->first()->nama_perusahaan ?? 'Fresh Graduate' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-auto order-last order-lg-0">
                <div class="row text text-white-50 text-center">
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $candidate->workExperiences->count() }}</h4>
                            <p class="fs-14 mb-0">Pengalaman</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $candidate->skills->count() }}</h4>
                            <p class="fs-14 mb-0">Skills</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-white mb-1">{{ $candidate->applications->count() }}</h4>
                            <p class="fs-14 mb-0">Lamaran</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div>
                <div class="d-flex profile-wrapper">
                    <ul class="nav nav-pills animation-nav profile-nav gap-2 gap-lg-3 flex-grow-1" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#overview-tab" role="tab">
                                <i class="ri-airplay-fill d-inline-block d-md-none"></i>
                                <span class="d-none d-md-inline-block">Overview</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#pendidikan" role="tab">
                                <i class="ri-graduation-cap-line d-inline-block d-md-none"></i>
                                <span class="d-none d-md-inline-block">Pendidikan</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#pengalaman" role="tab">
                                <i class="ri-briefcase-line d-inline-block d-md-none"></i>
                                <span class="d-none d-md-inline-block">Pengalaman</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#skills" role="tab">
                                <i class="ri-price-tag-line d-inline-block d-md-none"></i>
                                <span class="d-none d-md-inline-block">Skills</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#documents" role="tab">
                                <i class="ri-folder-4-line d-inline-block d-md-none"></i>
                                <span class="d-none d-md-inline-block">Documents</span>
                            </a>
                        </li>
                    </ul>
                    <div class="flex-shrink-0">
                        <a href="{{ route('user.ats.applications.index', ['userId' => $userId, 'candidate' => $candidate->id]) }}"
                            class="btn btn-success">
                            <i class="ri-file-list-line align-bottom"></i> Lihat Lamaran
                        </a>
                    </div>
                </div>

                <div class="tab-content pt-4 text-muted">
                    {{-- Overview Tab --}}
                    <div class="tab-pane active" id="overview-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-xxl-3">
                                {{-- Profile Completion --}}
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Profile Completion</h5>
                                        @php
                                            $totalFields = 10;
                                            $filledFields = 0;
                                            if ($candidate->nik) {
                                                $filledFields++;
                                            }
                                            if ($candidate->no_kk) {
                                                $filledFields++;
                                            }
                                            if ($candidate->tempat_lahir) {
                                                $filledFields++;
                                            }
                                            if ($candidate->tanggal_lahir) {
                                                $filledFields++;
                                            }
                                            if ($candidate->no_hp) {
                                                $filledFields++;
                                            }
                                            if ($candidate->alamat_lengkap) {
                                                $filledFields++;
                                            }
                                            if ($candidate->educations->count() > 0) {
                                                $filledFields++;
                                            }
                                            if ($candidate->workExperiences->count() > 0) {
                                                $filledFields++;
                                            }
                                            if ($candidate->skills->count() > 0) {
                                                $filledFields++;
                                            }
                                            if ($candidate->documents->count() > 0) {
                                                $filledFields++;
                                            }
                                            $percentage = round(($filledFields / $totalFields) * 100);
                                        @endphp
                                        <div class="progress animated-progress custom-progress progress-label">
                                            <div class="progress-bar bg-{{ $percentage >= 70 ? 'success' : ($percentage >= 40 ? 'warning' : 'danger') }}"
                                                role="progressbar" style="width: {{ $percentage }}%">
                                                <div class="label">{{ $percentage }}%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Personal Info --}}
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Info Pribadi</h5>
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <tr>
                                                    <th class="ps-0">NIK</th>
                                                    <td class="text-muted">: {{ $candidate->nik ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">No KK</th>
                                                    <td class="text-muted">: {{ $candidate->no_kk ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">Tempat, Tgl Lahir</th>
                                                    <td class="text-muted">: {{ $candidate->tempat_lahir ?? '-' }},
                                                        {{ $candidate->tanggal_lahir ? $candidate->tanggal_lahir->format('d M Y') : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">Jenis Kelamin</th>
                                                    <td class="text-muted">:
                                                        {{ $candidate->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">Agama</th>
                                                    <td class="text-muted">: {{ $candidate->agama ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">Status</th>
                                                    <td class="text-muted">: {{ $candidate->status_perkawinan ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">No HP</th>
                                                    <td class="text-muted">: {{ $candidate->no_hp ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0">Email</th>
                                                    <td class="text-muted">: {{ $candidate->user->email }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Skills Summary --}}
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Skills</h5>
                                        <div class="d-flex flex-wrap gap-2">
                                            @forelse($candidate->skills as $skill)
                                                <span class="badge bg-primary-subtle text-primary fs-12 p-2">
                                                    {{ $skill->nama_skill }}
                                                    @if ($skill->level)
                                                        <span class="ms-1">({{ $skill->level }})</span>
                                                    @endif
                                                </span>
                                            @empty
                                                <p class="text-muted">Belum ada skill</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xxl-9">
                                {{-- About --}}
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">About</h5>
                                        @php
                                            $cv = $candidate->documents->where('jenis_dokumen', 'cv')->first();
                                        @endphp
                                        <p>{{ $cv->ringkasan_profesional ?? 'Belum ada ringkasan' }}</p>
                                    </div>
                                </div>

                                {{-- Recent Activity --}}
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Aktivitas Terakhir</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="acitivity-timeline">
                                            @foreach ($candidate->applications->sortByDesc('created_at')->take(5) as $app)
                                                <div class="acitivity-item d-flex py-2">
                                                    <div class="flex-shrink-0 avatar-xs">
                                                        <div class="avatar-title rounded-circle bg-info-subtle text-info">
                                                            <i class="ri-file-copy-line"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-1">Melamar sebagai
                                                            {{ $app->recruitmentJob->judul }}</h6>
                                                        <p class="text-muted mb-1">Status:
                                                            {{ str_replace('_', ' ', $app->status) }}</p>
                                                        <small
                                                            class="text-muted">{{ $app->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pendidikan Tab --}}
                    <div class="tab-pane fade" id="pendidikan" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Riwayat Pendidikan</h5>
                                @forelse($candidate->educations->sortByDesc('tahun_lulus') as $edu)
                                    <div class="d-flex mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm">
                                                <div
                                                    class="avatar-title bg-{{ $edu->jenjang == 's1' ? 'success' : 'info' }}-subtle rounded">
                                                    <i
                                                        class="ri-graduation-cap-line fs-20 text-{{ $edu->jenjang == 's1' ? 'success' : 'info' }}"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-15">{{ $edu->jenjang }} - {{ $edu->jurusan }}</h6>
                                            <p class="text-muted">{{ $edu->nama_satuan_pendidikan }}</p>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <small class="text-muted">Tahun: {{ $edu->tahun_masuk }} -
                                                        {{ $edu->tahun_lulus }}</small>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">IPK/Nilai:
                                                        {{ $edu->ipk ?? $edu->nilai_akhir }}</small>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">No Ijazah:
                                                        {{ $edu->no_ijazah ?? '-' }}</small>
                                                </div>
                                            </div>
                                            @if ($edu->ijazah_path)
                                                <a href="{{ asset('storage/' . $edu->ijazah_path) }}" target="_blank"
                                                    class="btn btn-sm btn-soft-primary mt-2">
                                                    <i class="ri-file-pdf-line"></i> Lihat Ijazah
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    @if (!$loop->last)
                                        <hr>
                                    @endif
                                @empty
                                    <p class="text-muted text-center">Belum ada data pendidikan</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Pengalaman Tab --}}
                    <div class="tab-pane fade" id="pengalaman" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Riwayat Pekerjaan</h5>
                                @forelse($candidate->workExperiences as $exp)
                                    <div class="d-flex mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm">
                                                <div class="avatar-title bg-primary-subtle rounded">
                                                    <i class="ri-briefcase-line fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-15">{{ $exp->posisi_terakhir }}</h6>
                                            <p class="text-muted">{{ $exp->nama_perusahaan }}</p>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <small class="text-muted">Periode:
                                                        {{ $exp->tanggal_mulai->format('M Y') }} -
                                                        {{ $exp->is_saat_ini ? 'Sekarang' : ($exp->tanggal_selesai ? $exp->tanggal_selesai->format('M Y') : '-') }}</small>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">Durasi:
                                                        {{ floor($exp->lama_bekerja_bulan / 12) }} tahun
                                                        {{ $exp->lama_bekerja_bulan % 12 }} bulan</small>
                                                </div>
                                            </div>
                                            <p class="mt-2">{{ $exp->jobdesc }}</p>
                                            @if ($exp->pencapaian)
                                                <div class="mt-2">
                                                    <strong>Pencapaian:</strong>
                                                    <ul>
                                                        @foreach (json_decode($exp->pencapaian) as $achievement)
                                                            <li>{{ $achievement }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if (!$loop->last)
                                        <hr>
                                    @endif
                                @empty
                                    <p class="text-muted text-center">Belum ada pengalaman kerja</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Skills Tab --}}
                    <div class="tab-pane fade" id="skills" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Kompetensi & Sertifikasi</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Technical Skills</h6>
                                        @foreach ($candidate->skills->where('kategori', 'teknis') as $skill)
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span>{{ $skill->nama_skill }}</span>
                                                    <span class="text-muted">{{ $skill->level ?? '-' }}</span>
                                                </div>
                                                <div class="progress" style="height: 5px;">
                                                    @php
                                                        $levelValue = 0;
                                                        if ($skill->level == 'Pemula') {
                                                            $levelValue = 30;
                                                        } elseif ($skill->level == 'Menengah') {
                                                            $levelValue = 60;
                                                        } elseif ($skill->level == 'Ahli') {
                                                            $levelValue = 90;
                                                        }
                                                    @endphp
                                                    <div class="progress-bar bg-success"
                                                        style="width: {{ $levelValue }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="mb-3">Soft Skills</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($candidate->skills->where('kategori', 'non_teknis') as $skill)
                                                <span
                                                    class="badge bg-info-subtle text-info p-2">{{ $skill->nama_skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="card-title mb-4">Pelatihan & Sertifikasi</h5>
                                @forelse($candidate->trainings as $training)
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm">
                                                <div class="avatar-title bg-warning-subtle rounded">
                                                    <i class="ri-award-line fs-20 text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-15">{{ $training->nama_pelatihan }}</h6>
                                            <p class="text-muted">{{ $training->penyelenggara }} - {{ $training->tahun }}
                                            </p>
                                            @if ($training->sertifikat_path)
                                                <a href="{{ asset('storage/' . $training->sertifikat_path) }}"
                                                    target="_blank" class="btn btn-sm btn-soft-warning">
                                                    <i class="ri-file-pdf-line"></i> Lihat Sertifikat
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    @if (!$loop->last)
                                        <hr>
                                    @endif
                                @empty
                                    <p class="text-muted text-center">Belum ada pelatihan</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Documents Tab --}}
                    <div class="tab-pane fade" id="documents" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <h5 class="card-title flex-grow-1 mb-0">Dokumen</h5>
                                </div>
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
                                            @forelse($candidate->documents as $doc)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm">
                                                                <div
                                                                    class="avatar-title bg-{{ $doc->jenis_dokumen == 'cv' ? 'primary' : 'info' }}-subtle rounded fs-20">
                                                                    <i
                                                                        class="ri-file-{{ $doc->file_extension }}-line"></i>
                                                                </div>
                                                            </div>
                                                            <div class="ms-3">
                                                                <h6 class="fs-15 mb-0">{{ $doc->nama_dokumen }}</h6>
                                                                @if ($doc->is_primary)
                                                                    <span class="badge bg-success">Primary</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ strtoupper($doc->jenis_dokumen) }}</td>
                                                    <td>{{ $doc->file_size }}</td>
                                                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                                                    <td>
                                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                                            class="btn btn-sm btn-soft-primary">
                                                            <i class="ri-download-line"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">Belum ada dokumen</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/profile.init.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
