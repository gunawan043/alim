@extends('layouts.master')
@section('title') Rombongan Belajar @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Rombongan Belajar @endslot
        @slot('title') Daftar Rombel @endslot
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

    {{-- Warning: rombel yang melebihi kapasitas --}}
    @php
        $overCapacityGroups = $studyGroups->filter(fn($sg) => ($sg->studentCount ?? 0) > $sg->capacity);
    @endphp
    @if($overCapacityGroups->isNotEmpty())
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ri-error-warning-fill fs-4"></i>
            <div>
                <strong>{{ $overCapacityGroups->count() }} rombel melebihi kapasitas:</strong>
                @foreach($overCapacityGroups->take(5) as $sg)
                    {{ $sg->full_name }} ({{ $sg->studentCount }}/{{ $sg->capacity }})@if(!$loop->last), @endif
                @endforeach
                @if($overCapacityGroups->count() > 5)
                    dan {{ $overCapacityGroups->count() - 5 }} rombel lainnya.
                @endif
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rombongan Belajar</h5>
                            <p class="text-muted mb-0">Pengaturan rombel @if(!$isGlobalView)di sekolah Anda.{{-- scoped --}} @else {{-- global --}}per sekolah dan tahun ajaran.@endif</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.study-groups.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Rombel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        @if($isGlobalView)
                        <div class="col-md-3">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <select name="academic_year_id" class="form-control">
                                <option value="">Semua Tahun Ajaran</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }} ({{ $ay->semester_text }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama rombel..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    @if($isGlobalView)<th>Sekolah</th>@endif
                                    <th>Rombel</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tingkat</th>
                                    <th>Santri</th>
                                    <th>Ruang</th>
                                    <th>Wali Kelas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studyGroups as $sg)
                                    <tr>
                                        <td>{{ $loop->iteration + ($studyGroups->currentPage() - 1) * $studyGroups->perPage() }}</td>
                                        @if($isGlobalView)
                                        <td>
                                            <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $sg->school_id]) }}" class="text-muted small">
                                                {{ $sg->school?->name ?? '-' }}
                                            </a>
                                        </td>
                                        @endif
                                        <td>
                                            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sg->id]) }}" class="fw-medium link-primary">
                                                {{ $sg->full_name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $sg->academicYear?->semester === 'ganjil' ? 'primary' : 'info' }}-subtle text-{{ $sg->academicYear?->semester === 'ganjil' ? 'primary' : 'info' }}">
                                                {{ $sg->academicYear?->name ?? '-' }} {{ $sg->academicYear?->semester_text ?? '' }}
                                            </span>
                                        </td>
                                        <td>{{ $sg->gradeLevel?->name ?? '-' }}</td>
                                        <td>
                                            @php
                                                $filled = $sg->studentCount ?? 0;
                                                $cap    = $sg->capacity;
                                                $pct    = $cap > 0 ? min(100, round($filled / $cap * 100)) : 0;
                                                $color  = $filled >= $cap ? 'danger' : ($filled >= $cap * 0.9 ? 'warning' : 'success');
                                            @endphp
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                                                    <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div>
                                                </div>
                                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} fw-normal" style="font-size:11px;white-space:nowrap">
                                                    {{ $filled }}/{{ $cap }}
                                                    @if($filled >= $cap)
                                                        <i class="ri-error-warning-fill ms-1"></i>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $sg->room ?? '-' }}</td>
                                        <td>
                                            @if($sg->homeroomTeacher)
                                                <span class="text-muted small">{{ $sg->homeroomTeacher->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sg->is_active)
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
                                                        <a class="dropdown-item" href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sg->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.study-groups.edit', ['userId' => $userId, 'id' => $sg->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-sg" href="javascript:void(0)"
                                                            data-id="{{ $sg->id }}" data-name="{{ $sg->full_name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isGlobalView ? '10' : '9' }}" class="text-center py-4">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-group-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data rombel</h5>
                                            <a href="{{ route('user.study-groups.create', ['userId' => $userId]) }}" class="btn btn-success">
                                                <i class="ri-add-line me-1"></i>Tambah Rombel
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $studyGroups])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-sg').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Rombel \"" + name + "\" akan dihapus permanen.",
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
                        form.action = '/{{ $userId }}/study-groups/' + id;
                        ['_token','_method'].forEach(function(name, i) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = name;
                            inp.value = i === 0 ? '{{ csrf_token() }}' : 'DELETE';
                            form.appendChild(inp);
                        });
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
