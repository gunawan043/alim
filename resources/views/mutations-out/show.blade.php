@extends('layouts.master')
@section('title') Detail PD Keluar @endsection

@section('css')
<style>
    .detail-label { font-weight: 600; color: var(--bs-secondary-color); min-width: 160px; }
    .detail-value { color: var(--bs-body-color); }
    .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--bs-box-shadow); }
    .profile-header {
        background: #405189; 
        border-radius: 12px;
        padding: 24px;
        margin-top: -24px;
        position: relative;
    }
    [data-bs-theme="dark"] .profile-header { background: linear-gradient(135deg, rgba(13,110,253,0.7), rgba(13,202,240,0.4)); }
    .section-card { border: 1px solid var(--bs-border-color); }
    .section-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .contact-item { padding: 10px 0; border-bottom: 1px solid var(--bs-border-color-translucent); }
    .contact-item:last-child { border-bottom: none; }
    .badge-outline { background: transparent; border: 1px solid currentColor; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
    @slot('li_3') <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}">PD Keluar</a> @endslot
    @slot('title') {{ $mutation->student_name }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Profile Header --}}
<div class="profile-header mb-4 mt-2">
    <div class="row align-items-center g-3">
        <div class="col-auto">
            <div class="position-relative">
                <div class="avatar-md">
                    <span class="avatar-title rounded-circle fs-2 fw-bold bg-{{ $mutation->student_gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $mutation->student_gender === 'P' ? 'danger' : 'primary' }}">
                        {{ strtoupper(substr($mutation->student_name, 0, 2)) }}
                    </span>
                </div>
                <span class="position-absolute bottom-0 end-0 badge rounded-circle p-0 border border-2 border-white bg-{{ $mutation->status === 'approved' ? 'success' : ($mutation->status === 'rejected' ? 'danger' : ($mutation->status === 'submitted' ? 'warning' : 'secondary')) }}">
                    <i class="ri-circle-fill fs-16"></i>
                </span>
            </div>
        </div>
        <div class="col">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h3 class="text-white mb-0">{{ $mutation->student_name }}</h3>
                <span class="badge bg-{{ $mutation->status_color }}-subtle text-{{ $mutation->status_color }}">
                    <i class="ri-checkbox-circle-line me-1"></i>{{ $mutation->status_text }}
                </span>
                <span class="badge badge-outline text-white border-white">
                    {{ $mutation->out_type_text }}
                </span>
            </div>
            <div class="text-white d-flex flex-wrap gap-3 opacity-75" style="font-size:0.85rem">
                <span><i class="ri-bookmark-line me-1"></i>NISN <code class="text-white">{{ $mutation->student_nisn ?: '-' }}</code></span>
                @if($mutation->student_nis)
                    <span><i class="ri-file-list-line me-1"></i>NIS <code class="text-white">{{ $mutation->student_nis }}</code></span>
                @endif
                <span><i class="ri-government-line me-1"></i>{{ $mutation->school?->name ?: '-' }}</span>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-8">

        {{-- Identitas Santri --}}
        <div class="card section-card mb-4">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-4">
                    <i class="ri-user-line text-primary me-2"></i>Identitas Santri
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Nama Lengkap</label>
                            <div class="detail-value">{{ $mutation->student_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">NISN</label>
                            <div class="detail-value"><code>{{ $mutation->student_nisn ?: '-' }}</code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">NIS</label>
                            <div class="detail-value">{{ $mutation->student_nis ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Jenis Kelamin</label>
                            <div class="detail-value">
                                <span class="badge bg-{{ $mutation->student_gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $mutation->student_gender === 'L' ? 'primary' : 'danger' }}">
                                    <i class="ri-{{ $mutation->student_gender === 'L' ? 'men' : 'women' }}-line me-1"></i>{{ $mutation->gender_text }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Tempat, Tgl Lahir</label>
                            <div class="detail-value">{{ $mutation->student_birth_place ?: '-' }}, {{ $mutation->student_birth_date?->format('d F Y') ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Alamat</label>
                            <div class="detail-value">{{ $mutation->student_address ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Kelas</label>
                            <div class="detail-value">{{ $mutation->student_current_class ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Asal Sekolah</label>
                            <div class="detail-value">{{ $mutation->student_previous_school ?: ($mutation->school?->name ?: '-') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Sesuai Jenis PD Keluar --}}
        @if($mutation->out_type === 'mutation')
            <div class="card section-card mb-4">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center mb-4">
                        <i class="ri-logout-box-line text-info me-2"></i>Sekolah Tujuan
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label detail-label">Nama Sekolah</label>
                                <div class="detail-value">{{ $mutation->destination_school_name ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label detail-label">Alamat</label>
                                <div class="detail-value">{{ $mutation->destination_school_address ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-0">
                                <label class="form-label detail-label">Alasan Pindah</label>
                                <div class="detail-value">{{ $mutation->reason ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($mutation->out_type === 'graduation')
            <div class="card section-card mb-4" style="border-left: 4px solid #2f9e44;">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center mb-4">
                        <i class="ri-graduation-cap-line text-success me-2"></i>Data Kelulusan
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label detail-label">Tahun Lulus</label>
                                <div class="detail-value">{{ $mutation->graduation_year ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label detail-label">No. Ijazah</label>
                                <div class="detail-value"><code>{{ $mutation->graduation_certificate_number ?: '-' }}</code></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label detail-label">Nama Sekolah</label>
                                <div class="detail-value">{{ $mutation->graduation_school_name ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($mutation->out_type === 'dropout')
            <div class="card section-card mb-4" style="border-left: 4px solid #e67700;">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center mb-4">
                        <i class="ri-user-unfollow-line text-warning me-2"></i>Alasan Drop Out
                    </h5>
                    <div class="alert alert-warning mb-0">
                        <i class="ri-error-warning-line me-2"></i>{{ $mutation->reason ?: '-' }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Orang Tua --}}
        <div class="card section-card mb-4">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-4">
                    <i class="ri-parent-line text-primary me-2"></i>Orang Tua / Wali
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Nama</label>
                            <div class="detail-value">{{ $mutation->parent_name ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Pekerjaan</label>
                            <div class="detail-value">{{ $mutation->parent_occupation ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">No. HP</label>
                            <div class="detail-value">{{ $mutation->parent_phone ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Alamat</label>
                            <div class="detail-value">{{ $mutation->parent_address ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Surat Keterangan --}}
        <div class="card section-card mb-4">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-4">
                    <i class="ri-file-text-line text-secondary me-2"></i>Surat Keterangan
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">No. Surat</label>
                            <div class="detail-value"><code>{{ $mutation->letter_number ?: '-' }}</code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Ditetapkan di</label>
                            <div class="detail-value">{{ $mutation->established_city ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Tanggal Masehi</label>
                            <div class="detail-value">{{ $mutation->established_date?->format('d F Y') ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Tanggal Hijriyah</label>
                            <div class="detail-value">{{ $mutation->hijri_date ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Nama Kepala Sekolah</label>
                            <div class="detail-value">{{ $mutation->head_name ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">Jabatan</label>
                            <div class="detail-value">{{ $mutation->head_title ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label detail-label">NUPY</label>
                            <div class="detail-value"><code>{{ $mutation->head_nip ?: '-' }}</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Sidebar: Aksi --}}
    <div class="col-lg-4">
        <div class="card section-card sticky-top" style="top:1rem">

            {{-- Status Info --}}
            <div class="card-header">
                <h6 class="mb-0"><i class="ri-settings-3-line me-1"></i>Aksi &amp; Status</h6>
            </div>
            <div class="card-body p-0">

                @if($mutation->status === 'draft')
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-secondary-subtle">
                                <i class="ri-edit-line text-secondary fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Draft</div>
                                <div class="text-muted small">Belum diajukan</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <form action="{{ route('user.mutations-out.submit', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ri-send-plane-line me-1"></i>Ajukan
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger w-100"
                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="ri-delete-bin-line me-1"></i>Hapus
                        </button>
                    </div>

                @elseif($mutation->status === 'submitted')
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-warning-subtle">
                                <i class="ri-time-line text-warning fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Menunggu Persetujuan</div>
                                <div class="text-muted small">Sedang direview</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <form action="{{ route('user.mutations-out.approve', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ri-checkbox-circle-line me-1"></i>Setujui
                            </button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-circle-line me-1"></i>Tolak
                        </button>
                    </div>

                @elseif($mutation->status === 'approved')
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-success-subtle">
                                <i class="ri-checkbox-circle-fill text-success fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Disetujui</div>
                                <div class="text-muted small">
                                    <i class="ri-user-line me-1"></i>{{ $mutation->approvedBy?->name ?? '-' }}<br>
                                    <i class="ri-time-line me-1"></i>{{ $mutation->approved_at?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <a href="{{ route('user.mutations-out.print', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}"
                            class="btn btn-success w-100" target="_blank">
                            <i class="ri-printer-line me-1"></i>Cetak Surat
                        </a>
                    </div>

                @elseif($mutation->status === 'rejected')
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-danger-subtle">
                                <i class="ri-close-circle-fill text-danger fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Ditolak</div>
                            </div>
                        </div>
                    </div>
                    @if($mutation->rejection_reason)
                        <div class="p-3">
                            <div class="alert alert-danger py-2 small mb-0">
                                <i class="ri-error-warning-line me-1"></i>{{ $mutation->rejection_reason }}
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Info Tambahan --}}
            <div class="card-footer bg-transparent">
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Dibuat</span>
                        <span>{{ $mutation->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Diubah</span>
                        <span>{{ $mutation->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tolak --}}
@if($mutation->status === 'submitted')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-close-circle-line me-1 text-danger"></i>Tolak PD Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('user.mutations-out.reject', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Jelaskan..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Konfirmasi Hapus --}}
<div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                    colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                <h4 class="mt-3">Hapus PD Keluar?</h4>
                <p class="text-muted">Data <strong id="deleteMutationName">{{ $mutation->student_name }}</strong> akan dihapus permanen.</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('user.mutations-out.destroy', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
