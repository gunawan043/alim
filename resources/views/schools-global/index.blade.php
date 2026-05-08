@extends('layouts.master')
@section('title') Daftar Sekolah @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Daftar Sekolah @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Sekolah</h5>
                            <p class="text-muted mb-0">Kelola data sekolah/unit kerja.</p>
                        </div>
                        <div class="col-sm-auto">
                            @if(auth()->user()->hasRole(['Super Admin', 'Administrator']))
                            <a href="{{ route('user.schools.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Sekolah
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / NPSN..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="level" class="form-control">
                                <option value="">Jenjang</option>
                                <option value="sd"  {{ request('level') === 'sd'  ? 'selected' : '' }}>SD</option>
                                <option value="smp" {{ request('level') === 'smp' ? 'selected' : '' }}>SMP</option>
                                <option value="sma" {{ request('level') === 'sma' ? 'selected' : '' }}>SMA</option>
                                <option value="smk" {{ request('level') === 'smk' ? 'selected' : '' }}>SMK</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Status</option>
                                <option value="negeri"  {{ request('status') === 'negeri'  ? 'selected' : '' }}>Negeri</option>
                                <option value="swasta"  {{ request('status') === 'swasta'  ? 'selected' : '' }}>Swasta</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-control">
                                <option value="">Semua</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.schools-global.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    {{-- School Cards Grid --}}
                    <div class="row g-4">
                        @forelse($schools as $school)
                            <div class="col-xxl-3 col-lg-4 col-md-6">
                                <div class="card border card-shadow h-100 school-card">
                                    {{-- Header with level badge --}}
                                    <div class="card-header bg-{{ $school->school_level === 'smk' ? 'info' : ($school->school_level === 'sma' ? 'primary' : ($school->school_level === 'smp' ? 'warning' : 'success')) }}-subtle py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-shrink-0">
                                                @if($school->logo_path && file_exists(public_path($school->logo_path)))
                                                    <img src="{{ asset($school->logo_path) }}" alt="{{ $school->name }}" class="rounded-circle" width="52" height="52" style="object-fit:cover;border:2px solid white">
                                                @elseif($school->logo_path)
                                                    <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="rounded-circle" width="52" height="52" style="object-fit:cover;border:2px solid white">
                                                @else
                                                    <div class="avatar-xl bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;border:2px solid white">
                                                        <span class="fs-4 fw-bold text-primary">{{ strtoupper(substr($school->name,0,1)) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h6 class="mb-1 text-truncate" title="{{ $school->name }}">{{ $school->name }}</h6>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <span class="badge bg-{{ $school->school_status === 'negeri' ? 'success' : 'danger' }}-subtle text-{{ $school->school_status === 'negeri' ? 'success' : 'danger' }}">{{ $school->status_text }}</span>
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($school->school_level) }}</span>
                                                    @if(!$school->is_active)
                                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Body --}}
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-shield-star-line text-muted"></i>
                                            <small class="text-muted">NPSN: <strong>{{ $school->npsn ?? '-' }}</strong></small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-map-pin-line text-muted"></i>
                                            <small class="text-muted text-truncate d-block" style="max-width:220px">{{ $school->city?->name ?? ($school->address ?? '-') }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-medal-line text-muted"></i>
                                            <small class="text-muted">Akreditasi: <strong>{{ $school->accreditation ?? '-' }}</strong></small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-user-star-line text-muted"></i>
                                            <small class="text-muted">{{ $school->principalUser?->name ?? $school->principal_name ?? 'Belum ada kepala sekolah' }}</small>
                                        </div>
                                    </div>
                                    {{-- Footer --}}
                                    <div class="card-footer bg-transparent border-top-dashed">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $school->id]) }}" class="btn btn-sm btn-soft-primary flex-grow-1">
                                                <i class="ri-eye-line me-1"></i> Detail
                                            </a>
                                            <a href="{{ route('user.schools.edit', ['userId' => $userId, 'schoolId' => $school->id]) }}" class="btn btn-sm btn-soft-secondary">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button class="btn btn-sm btn-soft-danger delete-school"
                                                data-id="{{ $school->id }}"
                                                data-name="{{ $school->name }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <div class="avatar-lg mx-auto mb-3">
                                        <div class="avatar-title bg-light rounded-circle"><i class="ri-school-line fs-1 text-muted"></i></div>
                                    </div>
                                    <h5 class="text-muted">Belum ada data sekolah</h5>
                                    <p class="text-muted">Tambah sekolah pertama Anda.</p>
                                    <a href="{{ route('user.schools.create', ['userId' => $userId]) }}" class="btn btn-success">
                                        <i class="ri-add-line me-1"></i>Tambah Sekolah
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Sekolah?</h4>
                    <p class="text-muted">Sekolah <strong id="deleteSchoolName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteSchoolForm" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-school').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('deleteSchoolName').textContent = this.dataset.name;
                document.getElementById('deleteSchoolForm').action = '/schools/' + this.dataset.id;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });
    });
    </script>
@endsection
