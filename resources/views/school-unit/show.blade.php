@extends('layouts.master')
@section('title') {{ $school->name }} @endsection
@section('css')
    <style>
    .school-badge-lg { font-size: 0.9rem; padding: 0.4em 0.8em; }
    .doc-preview { border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; background: #f8f9fa; min-height: 140px; display: flex; align-items: center; justify-content: center; }
    .doc-preview img { max-height: 120px; object-fit: contain; }
    .doc-preview-placeholder { color: #adb5bd; font-size: 2rem; }
    .info-card-profile { border: 1px solid #f0f0f0; border-radius: 10px; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Pendidikan @endslot
        @slot('li_2') {{ $workUnit->name }} @endslot
        @slot('li_3') {{ $school->name }} @endslot
        @slot('title') Detail Sekolah @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header: Logo + Name + Badges --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    @if($school->logo_path)
                        <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="rounded-circle" width="90" height="90" style="object-fit:cover;border:3px solid #dee2e6">
                    @else
                        <div class="avatar-xl bg-{{ $school->school_level === 'smk' ? 'info' : ($school->school_level === 'sma' ? 'primary' : ($school->school_level === 'smp' ? 'warning' : 'success')) }}-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:90px;height:90px">
                            <span class="fs-2 fw-bold text-{{ $school->school_level === 'smk' ? 'info' : ($school->school_level === 'sma' ? 'primary' : ($school->school_level === 'smp' ? 'warning' : 'success')) }}">
                                {{ strtoupper(substr($school->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                        <h3 class="mb-0 me-2">{{ $school->name }}</h3>
                        @if(!$school->is_active)
                            <span class="badge bg-danger-subtle text-danger school-badge-lg">Nonaktif</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $school->school_status === 'negeri' ? 'success' : 'danger' }}-subtle text-{{ $school->school_status === 'negeri' ? 'success' : 'danger' }} school-badge-lg">
                            <i class="ri-government-line me-1"></i>{{ $school->status_text }}
                        </span>
                        <span class="badge bg-{{ $school->school_level === 'smk' ? 'info' : ($school->school_level === 'sma' ? 'primary' : ($school->school_level === 'smp' ? 'warning' : 'success')) }}-subtle text-{{ $school->school_level === 'smk' ? 'info' : ($school->school_level === 'sma' ? 'primary' : ($school->school_level === 'smp' ? 'warning' : 'success')) }} school-badge-lg">
                            {{ strtoupper($school->school_level) }}
                        </span>
                        @if($school->accreditation)
                            <span class="badge bg-purple-subtle text-purple school-badge-lg">
                                <i class="ri-medal-line me-1"></i>Akreditasi {{ $school->accreditation }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 text-muted small">
                        @if($school->npsn)
                            <span class="me-3"><i class="ri-shield-star-line me-1"></i>NPSN: {{ $school->npsn }}</span>
                        @endif
                        @if($school->nss)
                            <span class="me-3"><i class="ri-file-list-3-line me-1"></i>NSS: {{ $school->nss }}</span>
                        @endif
                        @if($school->principalUser?->name)
                            <span><i class="ri-user-star-line me-1"></i>KS: {{ $school->principalUser->name }}</span>
                        @elseif($school->principal_name)
                            <span><i class="ri-user-star-line me-1"></i>KS: {{ $school->principal_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    @can('school_edit')
                    <a href="{{ route('user.schools.satuan-kerja.edit', ['userId' => $userId, 'workUnitId' => $workUnit->id, 'schoolId' => $school->id]) }}" class="btn btn-soft-secondary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-8">

            {{-- Identitas & Kepsek --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-shield-star-line me-2 text-primary"></i>Identitas Sekolah</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Nama Sekolah</th>
                                <td>{{ $school->name }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">NPSN</th>
                                <td>{{ $school->npsn ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">NSS</th>
                                <td>{{ $school->nss ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Jenjang</th>
                                <td>{{ $school->level_text }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Status</th>
                                <td>{{ $school->status_text }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Akreditasi</th>
                                <td>
                                    {{ $school->accreditation ?? '-' }}
                                    @if($school->accreditation_year)
                                        <span class="text-muted">({{ $school->accreditation_year }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Jam Operasional</th>
                                <td>
                                    @if($school->operational_hours)
                                        {{ match($school->operational_hours) { 'pagi' => 'Pagi', 'siang' => 'Siang', 'full_day' => 'Full Day', default => '-' } }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Tahun Berdiri / SK</th>
                                <td>
                                    @if($school->established_date)
                                        {{ $school->established_date->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                    @if($school->established_decree)
                                        <span class="text-muted"> — {{ $school->established_decree }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Luas Tanah</th>
                                <td>{{ $school->land_area ? $school->land_area . ' m²' : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Luas Bangunan</th>
                                <td>{{ $school->building_area ? $school->building_area . ' m²' : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kepala Sekolah --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-user-star-line me-2 text-warning"></i>Kepala Sekolah</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Nama</th>
                                <td>{{ $school->principalUser?->name ?? $school->principal_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">NIP</th>
                                <td>{{ $school->principal_nip ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-map-pin-line me-2 text-danger"></i>Kontak & Alamat</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Alamat</th>
                                <td>{{ $school->full_address ?: ($school->address ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">No. Telepon</th>
                                <td>{{ $school->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Email</th>
                                <td>{{ $school->email ? '<a href="mailto:' . $school->email . '">' . $school->email . '</a>' : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Website</th>
                                <td>
                                    @if($school->website)
                                        <a href="{{ $school->website }}" target="_blank">{{ $school->website }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bank & NPWP --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-bank-line me-2 text-success"></i>Informasi Bank & NPWP</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Nama Bank</th>
                                <td>{{ $school->bank_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Cabang</th>
                                <td>{{ $school->bank_cabang ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">No. Rekening</th>
                                <td>{{ $school->bank_rekening ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Atas Nama</th>
                                <td>{{ $school->bank_an ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">NPWP</th>
                                <td>{{ $school->npwp ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-4">

            {{-- Logo --}}
            <div class="card info-card-profile">
                <div class="card-header"><h5 class="mb-0"><i class="ri-image-line me-2 text-info"></i>Logo</h5></div>
                <div class="card-body text-center">
                    @if($school->logo_path)
                        <img src="{{ $school->logo_url }}" alt="Logo" class="img-fluid rounded" style="max-height:160px">
                    @else
                        <div class="doc-preview">
                            <div class="doc-preview-placeholder"><i class="ri-image-add-line"></i></div>
                        </div>
                        <small class="text-muted">Logo belum diupload</small>
                    @endif
                </div>
            </div>

            {{-- Kop Surat --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-file-text-line me-2 text-primary"></i>Kop Surat
                        @if($school->kopsis_active)
                            <span class="badge bg-success-subtle text-success float-end">Aktif</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary float-end">Nonaktif</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($school->kop_path)
                        <div class="mb-3 text-center">
                            <img src="{{ asset('storage/' . $school->kop_path) }}" alt="Kop Surat" class="img-fluid rounded" style="max-height:200px;border:1px solid #dee2e6">
                        </div>
                    @else
                        <div class="doc-preview mb-3">
                            <div class="doc-preview-placeholder"><i class="ri-file-add-line"></i></div>
                        </div>
                        <small class="text-muted d-block text-center mb-3">Kop surat belum diupload</small>
                    @endif
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="text-muted small">Nama</th><td class="small">{{ $school->kop_nama ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">NPSN</th><td class="small">{{ $school->kop_npsn ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">Alamat</th><td class="small">{{ $school->kop_alamat ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">Telp</th><td class="small">{{ $school->kop_telp ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">Email</th><td class="small">{{ $school->kop_email ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">Website</th><td class="small">{{ $school->kop_website ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- TTD KSP --}}
            <div class="card info-card-profile">
                <div class="card-header"><h5 class="mb-0"><i class="ri-edit-line me-2 text-warning"></i>TTD Kepala Sekolah</h5></div>
                <div class="card-body text-center">
                    @if($school->ttd_ksp_path)
                        <img src="{{ asset('storage/' . $school->ttd_ksp_path) }}" alt="TTD KS" class="img-fluid rounded" style="max-height:120px">
                    @else
                        <div class="doc-preview mb-2">
                            <div class="doc-preview-placeholder"><i class="ri-edit-2-line"></i></div>
                        </div>
                        <small class="text-muted">TTD belum diupload</small>
                    @endif
                </div>
            </div>

            {{-- Cap/Stempel --}}
            <div class="card info-card-profile">
                <div class="card-header"><h5 class="mb-0"><i class="ri-file-hporment-line me-2 text-danger"></i>Cap/Stempel</h5></div>
                <div class="card-body text-center">
                    @if($school->stamp_path)
                        <img src="{{ asset('storage/' . $school->stamp_path) }}" alt="Stempel" class="img-fluid rounded" style="max-height:120px">
                    @else
                        <div class="doc-preview mb-2">
                            <div class="doc-preview-placeholder"><i class="ri-passport-line"></i></div>
                        </div>
                        <small class="text-muted">Stempel belum diupload</small>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
