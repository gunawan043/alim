@extends('layouts.master')
@section('title') Penugasan Mengajar @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Penugasan Mengajar @endslot
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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Penugasan Mengajar</h5>
                            <p class="text-muted mb-0">Pengaturan siapa mengajar mapel apa di kelas mana.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId, 'type' => 'SK Pembagian Tugas']) }}" class="btn btn-secondary">
                                <i class="ri-file-list-3-line align-bottom me-1"></i> Lihat SK
                            </a>
                            <a href="{{ route('user.teaching-assignments.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Penugasan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        @if($canViewAllSchools)
                        <div class="col-md-3">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <select name="academic_year_id" class="form-control">
                                <option value="">Th. Ajaran</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="teacher_id" class="form-control">
                                <option value="">Semua Guru</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="subject_id" class="form-control">
                                <option value="">Semua Mapel</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="study_group_id" class="form-control">
                                <option value="">Semua Kelas</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    @if($canViewAllSchools)
                                    <th>Sekolah</th>
                                    @endif
                                    <th>Guru</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <th>Peran</th>
                                    <th>JP/Mgg</th>
                                    <th>SK</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $a)
                                    <tr>
                                        <td>{{ $loop->iteration + ($assignments->currentPage() - 1) * $assignments->perPage() }}</td>
                                        @if($canViewAllSchools)
                                        <td>{{ $a->school?->name ?? '-' }}</td>
                                        @endif
                                        <td>
                                            <span class="fw-medium">{{ $a->teacher?->name ?? '-' }}</span>
                                            @if($a->is_coordinator)
                                                <span class="badge bg-primary-subtle text-primary ms-1">Kor.</span>
                                            @endif
                                        </td>
                                        <td>{{ $a->subject?->name ?? '-' }}</td>
                                        <td>{{ $a->studyGroup?->full_name ?? '-' }}</td>
                                        <td>
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
                                        <td>{{ $a->weekly_hours }}</td>
                                        <td>
                                            @if($a->decree)
                                                <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $a->decree_id]) }}" class="text-muted" title="{{ $a->decree->decree_number }}">
                                                    <i class="ri-file-text-line"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($a->status === 'active')
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
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
                                        <td colspan="{{ $canViewAllSchools ? '10' : '9' }}" class="text-center py-4">
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