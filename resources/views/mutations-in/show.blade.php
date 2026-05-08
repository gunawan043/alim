@extends('layouts.master')
@section('title') Detail PD Masuk @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
    @slot('li_3') <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">PD Masuk</a> @endslot
    @slot('title') {{ $mutation->student_name }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="ri-user-line me-2 text-primary"></i>Identitas Santri</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nama Lengkap</label>
                        <div class="fw-semibold">{{ $mutation->student_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Jenis Kelamin</label>
                        <div>
                            <span class="badge bg-{{ $mutation->student_gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $mutation->student_gender === 'P' ? 'danger' : 'primary' }}">
                                {{ $mutation->gender_text }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Tempat, Tgl Lahir</label>
                        <div>{{ $mutation->student_birth_place ?: '-' }}, {{ $mutation->student_birth_date?->format('d F Y') ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Agama</label>
                        <div>{{ $mutation->student_religion ?: 'Islam' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Sekolah Asal</label>
                        <div>{{ $mutation->student_previous_school ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Kelas di Sekolah Asal</label>
                        <div>{{ $mutation->student_previous_class ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="ri-parent-line me-2 text-primary"></i>Data Orang Tua/Wali</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nama Bapak</label>
                        <div>{{ $mutation->father_name ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Pekerjaan Bapak</label>
                        <div>{{ $mutation->father_occupation ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nama Ibu</label>
                        <div>{{ $mutation->mother_name ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Pekerjaan Ibu</label>
                        <div>{{ $mutation->mother_occupation ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Alamat</label>
                        <div>{{ $mutation->parent_address ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">No. HP</label>
                        <div>{{ $mutation->parent_phone ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="ri-login-box-line me-2 text-info"></i>Data Penerimaan</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Kelas Diterima</label>
                        <div>{{ $mutation->accepted_class ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Semester</label>
                        <div>{{ $mutation->accepted_semester ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Tahun Ajaran</label>
                        <div>{{ $mutation->accepted_academic_year ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="ri-file-text-line me-2 text-secondary"></i>Surat Keterangan</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">No. Surat</label>
                        <div><code>{{ $mutation->letter_number ?: '-' }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Ditetapkan di</label>
                        <div>{{ $mutation->established_city ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Tanggal Masehi</label>
                        <div>{{ $mutation->established_date?->format('d F Y') ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Tanggal Hijriyah</label>
                        <div>{{ $mutation->hijri_date ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nama Kepala Sekolah</label>
                        <div>{{ $mutation->head_name ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">NUPY</label>
                        <div><code>{{ $mutation->head_nip ?: '-' }}</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:1rem">
            <div class="card-header"><h6 class="mb-0">Aksi & Status</h6></div>
            <div class="card-body p-0">
                <div class="p-3 border-bottom">
                    <span class="badge bg-{{ $mutation->status_color }}-subtle text-{{ $mutation->status_color }} fs-6">
                        <i class="ri-checkbox-circle-line me-1"></i>{{ $mutation->status_text }}
                    </span>
                </div>

                @if($mutation->status === 'draft')
                    <div class="p-3">
                        <form action="{{ route('user.mutations-in.submit', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"><i class="ri-send-plane-line me-1"></i>Ajukan</button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="ri-delete-bin-line me-1"></i>Hapus
                        </button>
                    </div>
                @elseif($mutation->status === 'submitted')
                    <div class="p-3">
                        <form action="{{ route('user.mutations-in.approve', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"><i class="ri-checkbox-circle-line me-1"></i>Setujui</button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-circle-line me-1"></i>Tolak
                        </button>
                    </div>
                @elseif($mutation->status === 'approved')
                    <div class="p-3">
                        <a href="{{ route('user.mutations-in.print', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" class="btn btn-success w-100" target="_blank">
                            <i class="ri-printer-line me-1"></i>Cetak Surat
                        </a>
                    </div>
                @endif

                <div class="card-footer bg-transparent small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Dibuat</span><span>{{ $mutation->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Diubah</span><span>{{ $mutation->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($mutation->status === 'submitted')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-close-circle-line me-1 text-danger"></i>Tolak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('user.mutations-in.reject', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
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

<div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <h4 class="mt-2">Hapus PD Masuk?</h4>
                <p class="text-muted">Data <strong>{{ $mutation->student_name }}</strong> akan dihapus permanen.</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('user.mutations-in.destroy', ['userId' => $userId, 'mutationUuid' => $mutation->id]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
