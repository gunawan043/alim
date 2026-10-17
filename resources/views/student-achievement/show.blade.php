@extends('layouts.master')
@section('title') Detail Prestasi @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.certificate-img { max-height: 300px; object-fit: contain; cursor: pointer; transition: transform 0.2s; }
.certificate-img:hover { transform: scale(1.02); }
</style>
@endsection

@section('content')
@php
$typeLabel = $achievement->type_label;
$typeParam = request('type', 'akademik');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeParam]) }}">
            {{ $typeLabel }}
        </a>
    @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- BADGES / STATUS --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 align-items-center">
        @php $levelColors = ['internal'=>'secondary','kecamatan'=>'info','kabupaten_kota'=>'primary','provinsi'=>'warning','nasional'=>'danger','internasional'=>'dark']; @endphp
        <span class="badge bg-{{ $levelColors[$achievement->level] ?? 'secondary' }}-subtle fs-6">
            <i class="ri-earth-line me-1"></i> {{ $achievement->level_label }}
        </span>
        @php $posColors = ['juara_1'=>'warning','juara_2'=>'secondary','juara_3'=>'danger','harapan_1'=>'info','harapan_2'=>'info','harapan_3'=>'info','peserta'=>'secondary','lainnya'=>'dark']; @endphp
        <span class="badge bg-{{ $posColors[$achievement->position] ?? 'secondary' }}-subtle fs-6">
            <i class="ri-medal-line me-1"></i> {{ $achievement->position_label }}
        </span>
        @if($achievement->is_verified)
            <span class="badge bg-success-subtle text-success">
                <i class="ri-shield-check-line me-1"></i> Terverifikasi
            </span>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.student-achievement.edit', ['userId' => $userId, 'id' => $achievement->id, 'type' => $typeParam]) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="ri-pencil-line me-1"></i> Edit
        </a>
        <form method="POST"
              action="{{ route('user.student-achievement.destroy', ['userId' => $userId, 'id' => $achievement->id, 'type' => $typeParam]) }}"
              onsubmit="return confirm('Yakin hapus data prestasi ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="ri-delete-bin-line me-1"></i> Hapus
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    {{-- LEFT: Detail --}}
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri-trophy-line me-1"></i> {{ $achievement->event_name }}</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted py-2" style="width:170px">Siswa</td>
                            <td class="py-2">
                                <div class="fw-semibold">{{ $achievement->student->name ?? '-' }}</div>
                                <div class="small text-muted">
                                    NISN: {{ $achievement->student->nisn ?? '-' }}
                                    @if($achievement->student?->classHistories?->firstWhere('is_active', true)?->studyGroup)
                                        | Kelas: {{ $achievement->student->classHistories->firstWhere('is_active', true)->studyGroup->full_name }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($achievement->academicYear)
                            <tr>
                                <td class="text-muted py-2">Tahun Ajaran</td>
                                <td class="py-2">{{ $achievement->academicYear->name }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted py-2">Jenis</td>
                            <td class="py-2">{{ $typeLabel }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Penyelenggara</td>
                            <td class="py-2">{{ $achievement->organizer ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Tanggal Kegiatan</td>
                            <td class="py-2">{{ $achievement->event_date?->locale('id')->format('d M Y') ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Lokasi</td>
                            <td class="py-2">{{ $achievement->event_location ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Tingkat</td>
                            <td class="py-2">{{ $achievement->level_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Peringkat / Juara</td>
                            <td class="py-2">{{ $achievement->position_label }}</td>
                        </tr>
                        @if($achievement->coach)
                            <tr>
                                <td class="text-muted py-2">Guru Pembimbing</td>
                                <td class="py-2">{{ $achievement->coach->name }}</td>
                            </tr>
                        @endif
                        @if($achievement->notes)
                            <tr>
                                <td class="text-muted py-2">Keterangan</td>
                                <td class="py-2">{{ $achievement->notes }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted py-2">Dibuat</td>
                            <td class="py-2">
                                {{ $achievement->created_at->locale('id')->format('d M Y H:i') }}
                                @if($achievement->createdByUser)
                                    oleh {{ $achievement->createdByUser->name }}
                                @endif
                            </td>
                        </tr>
                        @if($achievement->is_verified && $achievement->verifiedByUser)
                            <tr>
                                <td class="text-muted py-2">Diverifikasi</td>
                                <td class="py-2">
                                    <span class="text-success">
                                        <i class="ri-shield-check-line me-1"></i>
                                        {{ $achievement->verified_at?->locale('id')->format('d M Y H:i') }}
                                        oleh {{ $achievement->verifiedByUser->name }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Certificate Image --}}
        @if($achievement->certificate_url)
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-file-image-line me-1"></i> Piagam / Sertifikat</h6>
                    <div>
                        <a href="{{ $achievement->certificate_url }}" target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i> Lihat Full
                        </a>
                        @php $ext = strtolower(pathinfo(parse_url($achievement->certificate_path, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png']))
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleZoom()">
                                <i class="ri-zoom-in-line" id="zoomBtn"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body text-center bg-light" id="certContainer">
                    @if(in_array($ext, ['jpg','jpeg','png']))
                        <img src="{{ $achievement->certificate_url }}" alt="Piagam"
                             class="certificate-img rounded shadow-sm" id="certImg"
                             onclick="toggleLightbox(this.src)">
                    @elseif($ext === 'pdf')
                        <div class="text-center py-4">
                            <i class="ri-file-pdf-2-line text-danger fs-1"></i>
                            <div class="mt-2">
                                <a href="{{ $achievement->certificate_url }}" target="_blank"
                                   class="btn btn-danger btn-sm">
                                    <i class="ri-eye-line me-1"></i> Buka PDF
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="card mt-3">
                <div class="card-body text-center text-muted py-4">
                    <i class="ri-image-off-line fs-2 d-block mb-2"></i>
                    Belum ada file piagam tersimpan.
                    <div class="mt-2">
                        <a href="{{ route('user.student-achievement.edit', ['userId' => $userId, 'id' => $achievement->id, 'type' => $typeParam]) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="ri-upload-cloud-line me-1"></i> Unggah Piagam
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- RIGHT: Student Card --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Informasi Siswa</h6></div>
            <div class="card-body text-center">
                <div class="avatar-xl mx-auto mb-3">
                    <div class="avatar-title bg-light text-muted fs-1 rounded">
                        <i class="ri-user-3-line"></i>
                    </div>
                </div>
                <div class="fw-semibold fs-5">{{ $achievement->student->name ?? '-' }}</div>
                <div class="text-muted small mb-2">{{ $achievement->student->nisn ?? '' }}</div>
                @if($achievement->student?->classHistories?->firstWhere('is_active', true)?->studyGroup)
                    <span class="badge bg-primary-subtle text-primary">
                        {{ $achievement->student->classHistories->firstWhere('is_active', true)->studyGroup->full_name }}
                    </span>
                @endif
            </div>
            @if($achievement->student)
                <div class="card-footer text-center">
                    <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $achievement->student->id]) }}"
                       class="btn btn-outline-primary btn-sm w-100">
                        <i class="ri-user-search-line me-1"></i> Lihat Profil Siswa
                    </a>
                </div>
            @endif
        </div>

        {{-- Quick Stats --}}
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Statistik</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted small">Tingkat</span>
                    <span class="fw-semibold">{{ $achievement->level_label }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted small">Peringkat</span>
                    <span class="fw-semibold">{{ $achievement->position_label }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted small">Tahun Ajaran</span>
                    <span class="fw-semibold">{{ $achievement->academicYear?->name ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Back button --}}
        <div class="mt-3">
            <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeParam]) }}"
               class="btn btn-light w-100">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

{{-- Lightbox modal --}}
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img src="" id="lightboxImg" class="img-fluid" alt="Piagam Full">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function toggleLightbox(src) {
    const modal = new bootstrap.Modal(document.getElementById('lightboxModal'));
    document.getElementById('lightboxImg').src = src;
    modal.show();
}

let zoomed = false;
function toggleZoom() {
    const img = document.getElementById('certImg');
    const btn = document.getElementById('zoomBtn');
    zoomed = !zoomed;
    img.style.maxHeight = zoomed ? '600px' : '300px';
    btn.className = zoomed ? 'ri-zoom-out-line' : 'ri-zoom-in-line';
}
</script>
@endsection