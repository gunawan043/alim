@extends('layouts.master')
@section('title') Rombongan Belajar @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Rombongan Belajar @endslot
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

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Rombel</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Rombel aktif pada filter ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-user-follow-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Santri Terdaftar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['santri']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Keanggotaan aktif di rombel</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-star-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Wali Kelas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['wali']) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">/ {{ $stats['total'] }}</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        @if($stats['total'] - $stats['wali'] > 0)
                            <span class="badge bg-warning-subtle text-warning stat-label">{{ $stats['total'] - $stats['wali'] }} belum terisi</span>
                        @else
                            <span class="badge bg-success-subtle text-success stat-label">Semua terisi</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-building-2-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Kapasitas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['kapasitas']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Daya tampung seluruh rombel</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="studyGroupList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h5 class="card-title mb-0">Daftar Rombongan Belajar</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $studyGroups->total() }} rombel</span>
                                <span class="text-muted small ms-2">Pengaturan rombel @if(!$isGlobalView) di sekolah Anda @else per sekolah dan tahun ajaran @endif.</span>
                            </p>
                        </div>
                        <div class="col-lg-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    @if($isGlobalView)
                                    <select name="school_id" class="form-select" style="width:160px" onchange="this.form.submit()">
                                        <option value="">Semua Sekolah</option>
                                        @foreach($schools as $s)
                                            <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    @endif
                                    <select name="academic_year_id" class="form-select" style="width:170px" onchange="this.form.submit()">
                                        <option value="">Semester Aktif</option>
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester_text }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="search" class="form-control" style="width:170px" placeholder="Cari rombel..." value="{{ request('search') }}">
                                    <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                    <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <a href="{{ route('user.study-groups.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Rombel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['semua_ta' => null, 'academic_year_id' => null]) }}" class="filter-badge {{ ! request('semua_ta') && ! request('academic_year_id') ? 'active' : '' }}"><i class="ri-calendar-check-line"></i>Semester Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['semua_ta' => '1', 'academic_year_id' => null]) }}" class="filter-badge {{ request('semua_ta') ? 'active' : '' }}"><i class="ri-calendar-2-line"></i>Semua Tahun Ajaran</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small">{{ $stats['santri'] }} santri · {{ $stats['wali'] }} wali kelas</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                @if($isGlobalView)<th>Satuan Pendidikan</th>@endif
                                <th>Rombel</th>
                                <th>Tahun Ajaran</th>
                                <th>Tingkat</th>
                                <th>Santri</th>
                                <th>Ruang</th>
                                <th>Wali Kelas</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studyGroups as $sg)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration + ($studyGroups->currentPage() - 1) * $studyGroups->perPage() }}</td>
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
                                            <span class="text-muted small"><i class="ri-user-star-line me-1"></i>{{ $sg->homeroomTeacher->name }}</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sg->is_active)
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
                                    <td colspan="{{ $isGlobalView ? '10' : '9' }}" class="text-center py-5">
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
                <div class="card-body pt-0">
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
