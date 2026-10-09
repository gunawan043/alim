@extends('layouts.master')
@section('title') Penugasan Mengajar @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Plotting Guru Mengajar @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $currentUser = auth()->user();
        $canViewAllSchools = $currentUser && $currentUser->hasPermissionTo('teaching-assignment-all-access');
    @endphp

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Penugasan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Sesuai filter aktif</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-user-star-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Guru Mengajar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['guru']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-team-line me-1"></i>Guru unik terplot</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-book-2-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Terplot</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['mapel']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>Mata pelajaran unik</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-timer-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total JP / Minggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['jp']) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">JP</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi jam mengajar</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="teachingAssignmentList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h5 class="card-title mb-0">Daftar Plotting Guru Mengajar</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $assignments->total() }} penugasan</span>
                                <span class="text-muted small ms-2">Siapa mengajar mapel apa di kelas mana — sumber JP untuk generator jadwal.</span>
                            </p>
                        </div>
                        <div class="col-lg-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <a href="{{ route('user.institution-decrees.index', ['userId' => $userId, 'type' => 'SK Pembagian Tugas']) }}" class="btn btn-soft-secondary">
                                    <i class="ri-file-list-3-line align-bottom me-1"></i> Lihat SK
                                </a>
                                <a href="{{ route('user.teaching-assignments.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Penugasan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <form method="GET" action="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-1"><i class="ri-filter-3-line me-1"></i>Filter:</span>
                        @if($canViewAllSchools)
                        <select name="school_id" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                            <option value="">Semua Sekolah</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @endif
                        <select name="academic_year_id" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                            <option value="">Th. Ajaran</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                            @endforeach
                        </select>
                        <select name="teacher_id" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
                            <option value="">Semua Guru</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <select name="subject_id" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
                            <option value="">Semua Mapel</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                        <select name="study_group_id" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            @foreach($studyGroups as $sg)
                                <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="form-select form-select-sm" style="width:115px" onchange="this.form.submit()">
                            <option value="">Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <select name="role" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
                            <option value="">Semua Peran</option>
                            <option value="guru_mapel" {{ request('role') === 'guru_mapel' ? 'selected' : '' }}>Guru Mapel</option>
                            <option value="guru_pendamping" {{ request('role') === 'guru_pendamping' ? 'selected' : '' }}>Guru Pendamping</option>
                            <option value="guru_praktik" {{ request('role') === 'guru_praktik' ? 'selected' : '' }}>Guru Praktik</option>
                        </select>
                        <button class="btn btn-primary btn-sm"><i class="ri-search-line"></i></button>
                        <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                @if($canViewAllSchools)
                                <th>Satuan Pendidikan</th>
                                @endif
                                <th>Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th class="text-center">Peran</th>
                                <th class="text-center">JP/Mgg</th>
                                <th class="text-center">SK</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $a)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration + ($assignments->currentPage() - 1) * $assignments->perPage() }}</td>
                                    @if($canViewAllSchools)
                                    <td><span class="text-muted small">{{ $a->school?->name ?? '-' }}</span></td>
                                    @endif
                                    <td>
                                        <span class="fw-medium">{{ $a->teacher?->name ?? '-' }}</span>
                                        @if($a->is_coordinator)
                                            <span class="badge bg-primary-subtle text-primary ms-1">Kor.</span>
                                        @endif
                                    </td>
                                    <td>{{ $a->subject?->name ?? '-' }}</td>
                                    <td>{{ $a->studyGroup?->full_name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($a->role === 'guru_mapel')
                                            <span class="badge bg-info-subtle text-info">Guru Mapel</span>
                                        @elseif($a->role === 'guru_pendamping')
                                            <span class="badge bg-warning-subtle text-warning">Guru Pendamping</span>
                                        @elseif($a->role === 'guru_praktik')
                                            <span class="badge bg-purple-subtle text-purple">Guru Praktik</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $a->role }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-semibold">{{ $a->weekly_hours }}</td>
                                    <td class="text-center">
                                        @if($a->decree)
                                            <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $a->decree_id]) }}" class="text-muted" title="{{ $a->decree->decree_number }}">
                                                <i class="ri-file-text-line"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($a->status === 'active')
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.teaching-assignments.show', ['userId' => $userId, 'id' => $a->id]) }}">
                                                        <i class="ri-eye-line me-2"></i>Lihat
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.teaching-assignments.edit', ['userId' => $userId, 'id' => $a->id]) }}">
                                                        <i class="ri-pencil-line me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-ta" href="javascript:void(0)"
                                                        data-id="{{ $a->id }}" data-name="{{ $a->teacher?->name }} - {{ $a->subject?->name }}">
                                                        <i class="ri-delete-bin-line me-2"></i>Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canViewAllSchools ? '10' : '9' }}" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="ri-team-line fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                        <h5 class="text-muted">Belum ada penugasan mengajar</h5>
                                        <a href="{{ route('user.teaching-assignments.create', ['userId' => $userId]) }}" class="btn btn-success">
                                            <i class="ri-add-line me-1"></i>Tambah Penugasan
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $assignments])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-ta').forEach(function(btn) {
            btn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'Penugasan "' + this.dataset.name + '" akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/{{ $userId }}/teaching-assignments/' + btn.dataset.id;
                        var token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = '{{ csrf_token() }}';
                        var method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        form.appendChild(token);
                        form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
