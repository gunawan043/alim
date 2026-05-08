@extends('layouts.master')
@section('title') {{ $alumni->student->name ?? 'Alumni' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}">Data Alumni</a> @endslot
        @slot('title') {{ $alumni->student->name ?? '-' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tracer Status Banner --}}
    <div class="alert alert-{{ $alumni->tracer_status === 'verified' ? 'success' : ($alumni->tracer_status === 'filled' ? 'info' : 'warning') }} d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="ri-{{ $alumni->tracer_status === 'verified' ? 'checkbox-circle' : ($alumni->tracer_status === 'filled' ? 'information' : 'time') }}-line fs-5"></i>
        <div>
            <strong>Tracer Study:</strong> {{ $alumni->tracer_status_text }}
            @if($alumni->tracer_filled_at)
                · Diisi {{ $alumni->tracer_filled_at->locale('id')->diffForHumans() }}
            @endif
            @if($alumni->tracer_status === 'filled')
                <a href="{{ route('user.alumni.verify', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}"
                   class="btn btn-sm btn-{{ $alumni->tracer_status === 'verified' ? 'success' : 'outline-success' }} ms-2">
                    <i class="ri-check-line me-1"></i>Verifikasi
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Left: Profile Card --}}
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-xl mx-auto mb-3 bg-{{ $alumni->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:100px;height:100px">
                        <span class="fs-2 fw-bold text-{{ $alumni->student?->gender === 'P' ? 'danger' : 'primary' }}">
                            {{ strtoupper(substr($alumni->student->name ?? 'A', 0, 2)) }}
                        </span>
                    </div>
                    <h5 class="mb-1">{{ $alumni->student->name ?? '-' }}</h5>
                    <p class="text-muted mb-1"><small>{{ $alumni->school->name ?? '-' }}</small></p>
                    <span class="badge bg-info-subtle text-info mb-3">
                        <i class="ri-graduation-cap-line me-1"></i>Lulus {{ $alumni->graduation_year }}
                    </span>

                    <table class="table table-sm table-borderless text-start mb-0">
                        <tr><th class="text-muted">NISN</th><td><code>{{ $alumni->student->nisn ?? '-' }}</code></td></tr>
                        <tr><th class="text-muted">NIK</th><td><code>{{ $alumni->student->nik ?? '-' }}</code></td></tr>
                        <tr><th class="text-muted">JK</th><td>{{ $alumni->student->gender_text ?? '-' }}</td></tr>
                        @if($alumni->student?->birth_place)
                            <tr><th class="text-muted">TTL</th>
                                <td>{{ $alumni->student->birth_place }}, {{ $alumni->student->birth_date?->format('d M Y') }}
                                </td>
                            </tr>
                        @endif
                        @if($alumni->student?->religion)
                            <tr><th class="text-muted">Agama</th><td>{{ ucfirst($alumni->student->religion) }}</td></tr>
                        @endif
                        <tr><th class="text-muted">No. Ijazah</th><td>{{ $alumni->graduation_certificate_number ?: '-' }}</td></tr>
                        <tr><th class="text-muted">No. HP</th><td>{{ $alumni->student->mobile_phone ?: '-' }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $alumni->student->email ?: '-' }}</td></tr>
                    </table>
                </div>
                <div class="card-footer text-center d-flex gap-2 justify-content-center">
                    <a href="{{ route('user.alumni.edit', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}"
                       class="btn btn-warning btn-sm">
                        <i class="ri-edit-line me-1"></i>Tracer Study
                    </a>
                    <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}"
                       class="btn btn-light btn-sm">Kembali</a>
                </div>
            </div>
        </div>

        {{-- Right: Detail Tabs --}}
        <div class="col-lg-9">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-identitas" type="button">
                        <i class="ri-user-line me-1"></i>Identitas
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alamat" type="button">
                        <i class="ri-home-line me-1"></i>Alamat
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-keluarga" type="button">
                        <i class="ri-parent-line me-1"></i>Orang Tua
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tracer" type="button">
                        <i class="ri-survey-line me-1"></i>Tracer Study
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- Tab: Identitas --}}
                <div class="tab-pane fade show active" id="tab-identitas" role="tabpanel">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="ri-user-line me-2"></i>Data Identitas</h6></div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th class="text-muted" style="width:200px">Nama Lengkap</th><td>{{ $alumni->student->name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">NISN</th><td><code>{{ $alumni->student->nisn ?? '-' }}</code></td></tr>
                                <tr><th class="text-muted">NIS</th><td>{{ $alumni->student->nis ?: '-' }}</td></tr>
                                <tr><th class="text-muted">NIK</th><td>{{ $alumni->student->nik ?: '-' }}</td></tr>
                                <tr><th class="text-muted">No. KK</th><td>{{ $alumni->student->no_kk ?: '-' }}</td></tr>
                                <tr><th class="text-muted">Jenis Kelamin</th><td>{{ $alumni->student->gender_text ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Tempat Lahir</th><td>{{ $alumni->student->birth_place ?: '-' }}</td></tr>
                                <tr><th class="text-muted">Tanggal Lahir</th><td>{{ $alumni->student->birth_date?->format('d M Y') ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Agama</th><td>{{ $alumni->student->religion ? ucfirst($alumni->student->religion) : '-' }}</td></tr>
                                <tr><th class="text-muted">Email</th><td>{{ $alumni->student->email ?: '-' }}</td></tr>
                                <tr><th class="text-muted">No. HP</th><td>{{ $alumni->student->mobile_phone ?: '-' }}</td></tr>
                                <tr><th class="text-muted">No. Telepon</th><td>{{ $alumni->student->phone ?: '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Tab: Alamat --}}
                <div class="tab-pane fade" id="tab-alamat" role="tabpanel">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="ri-home-line me-2"></i>Data Alamat</h6></div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th class="text-muted" style="width:200px">Alamat</th><td>{{ $alumni->student->full_address ?: '-' }}</td></tr>
                                <tr><th class="text-muted">RT/RW</th>
                                    <td>{{ [$alumni->student->rt, $alumni->student->rw] ? implode(' / ', array_filter([$alumni->student->rt, $alumni->student->rw])) : '-' }}
                                    </td>
                                </tr>
                                <tr><th class="text-muted">Provinsi</th><td>{{ $alumni->student->province?->name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Kota/Kab</th><td>{{ $alumni->student->city?->name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Kecamatan</th><td>{{ $alumni->student->district?->name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Desa/Kel</th><td>{{ $alumni->student->village?->name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Kode Pos</th><td>{{ $alumni->student->postal_code ?: '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Tab: Keluarga --}}
                <div class="tab-pane fade" id="tab-keluarga" role="tabpanel">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0"><i class="ri-parent-line me-2"></i>Data Orang Tua / Wali</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:120px">Nama Ayah</th><td>{{ $alumni->student->father_name ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">NIK Ayah</th><td>{{ $alumni->student->father_nik ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pend. Ayah</th><td>{{ $alumni->student->father_education ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pekerjaan</th><td>{{ $alumni->student->father_occupation ?: '-' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:120px">Nama Ibu</th><td>{{ $alumni->student->mother_name ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">NIK Ibu</th><td>{{ $alumni->student->mother_nik ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pend. Ibu</th><td>{{ $alumni->student->mother_education ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pekerjaan</th><td>{{ $alumni->student->mother_occupation ?: '-' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:120px">Nama Wali</th><td>{{ $alumni->student->guardian_name ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">NIK Wali</th><td>{{ $alumni->student->guardian_nik ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pend. Wali</th><td>{{ $alumni->student->guardian_education ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Pekerjaan</th><td>{{ $alumni->student->guardian_occupation ?: '-' }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab: Tracer Study --}}
                <div class="tab-pane fade" id="tab-tracer" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="ri-survey-line me-2"></i>Tracer Study</h6>
                            <a href="{{ route('user.alumni.edit', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}"
                               class="btn btn-sm btn-warning">
                                <i class="ri-edit-line me-1"></i>Edit Tracer
                            </a>
                        </div>
                        <div class="card-body">
                            {{-- Study Section --}}
                            <h6 class="text-primary mb-3"><i class="ri-book-open-line me-1"></i>Melanjutkan Studi</h6>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:150px">Status</th>
                                            <td>
                                                <span class="badge bg-{{ $alumni->continuing_study_status === 'sudah' ? 'success' : ($alumni->continuing_study_status === 'sedang' ? 'warning' : 'secondary') }}">
                                                    {{ $alumni->continuing_study_status_text }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr><th class="text-muted">Kampus/Institution</th><td>{{ $alumni->higher_education_institution ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Jurusan</th><td>{{ $alumni->study_program ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Kota</th><td>{{ $alumni->higher_education_city ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Tahun Masuk</th><td>{{ $alumni->higher_education_year_start ?: '-' }}</td></tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            {{-- Work Section --}}
                            <h6 class="text-success mb-3"><i class="ri-briefcase-line me-1"></i>Bekerja</h6>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:150px">Status</th>
                                            <td>
                                                <span class="badge bg-{{ $alumni->working_status === 'sudah' ? 'success' : ($alumni->working_status === 'sedang' ? 'warning' : 'secondary') }}">
                                                    {{ $alumni->working_status_text }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr><th class="text-muted">Jabatan</th><td>{{ $alumni->occupation ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Nama Perusahaan</th><td>{{ $alumni->company_name ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Alamat Kantor</th><td>{{ $alumni->company_address ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Kota</th><td>{{ $alumni->company_city ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">No. Telepon</th><td>{{ $alumni->company_phone ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Gaji/Bulan</th>
                                            <td>{{ $alumni->monthly_income ? 'Rp ' . number_format($alumni->monthly_income, 0, ',', '.') : '-' }}
                                            </td>
                                        </tr>
                                        <tr><th class="text-muted">Tahun Mulai</th><td>{{ $alumni->working_year_start ?: '-' }}</td></tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            {{-- Other Info --}}
                            <h6 class="text-secondary mb-3"><i class="ri-information-line me-1"></i>Informasi Lainnya</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr><th class="text-muted" style="width:160px">Dapat Dihubungi</th>
                                            <td>{{ $alumni->is_contactable ? 'Ya' : 'Tidak' }}</td>
                                        </tr>
                                        <tr><th class="text-muted">Prestasi</th><td>{{ $alumni->achievements ?: '-' }}</td></tr>
                                        <tr><th class="text-muted">Catatan</th><td>{{ $alumni->tracer_notes ?: '-' }}</td></tr>
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
