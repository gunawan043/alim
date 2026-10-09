@extends('layouts.master')
@section('title') Tingkat Kelas @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Data Kelas @endslot
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

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-stack-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Tingkat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Jenjang kelas terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['aktif']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Siap menerima rombel</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-pause-circle-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nonaktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['nonaktif']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tidak dipakai tahun berjalan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-layout-grid-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Fase</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['fase']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Fase CP yang tercakup</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="gradeLevelList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h5 class="card-title mb-0">Daftar Tingkat Kelas</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $gradeLevels->total() }} tingkat</span>
                                <span class="text-muted small ms-2">Pengaturan tingkat/jenjang kelas @if(!$isGlobalView) di sekolah Anda @else per sekolah @endif.</span>
                            </p>
                        </div>
                        <div class="col-lg-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    @if($isGlobalView)
                                    <select name="school_id" class="form-select" style="width:170px" onchange="this.form.submit()">
                                        <option value="">Semua Sekolah</option>
                                        @foreach($schools as $s)
                                            <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    @endif
                                    <input type="text" name="search" class="form-control" style="width:180px" placeholder="Cari tingkat..." value="{{ request('search') }}">
                                    <select name="is_active" class="form-select" style="width:120px" onchange="this.form.submit()">
                                        <option value="">Status</option>
                                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                    <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                    <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <a href="{{ route('user.grade-levels.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Tingkat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => null]) }}" class="filter-badge {{ ! request('is_active') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => '1']) }}" class="filter-badge {{ request('is_active') === '1' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i>Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => '0']) }}" class="filter-badge {{ request('is_active') === '0' ? 'active' : '' }}"><i class="ri-close-circle-line"></i>Nonaktif</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                @if($isGlobalView)<th>Satuan Pendidikan</th>@endif
                                <th class="text-center">Tingkat</th>
                                <th>Nama Tingkat</th>
                                <th class="text-center">Fase</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gradeLevels as $gl)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration + ($gradeLevels->currentPage() - 1) * $gradeLevels->perPage() }}</td>
                                    @if($isGlobalView)
                                    <td>
                                        <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $gl->school_id]) }}" class="text-muted small">
                                            {{ $gl->school?->name ?? '-' }}
                                        </a>
                                    </td>
                                    @endif
                                    <td class="text-center">
                                        <span class="badge bg-dark-subtle text-dark">Kelas {{ $gl->level }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('user.grade-levels.show', ['userId' => $userId, 'id' => $gl->id]) }}" class="fw-medium link-primary">
                                            {{ $gl->name }}
                                        </a>
                                        @if($gl->code)
                                            <span class="text-muted small ms-1">({{ $gl->code }})</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($gl->fase)
                                            <span class="badge bg-info-subtle text-info">{{ $gl->fase }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($gl->is_active)
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
                                                    <a class="dropdown-item" href="{{ route('user.grade-levels.show', ['userId' => $userId, 'id' => $gl->id]) }}">
                                                        <i class="ri-eye-line me-2"></i>Lihat
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.grade-levels.edit', ['userId' => $userId, 'id' => $gl->id]) }}">
                                                        <i class="ri-pencil-line me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-gl" href="javascript:void(0)"
                                                        data-id="{{ $gl->id }}" data-name="{{ $gl->name }}">
                                                        <i class="ri-delete-bin-line me-2"></i>Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isGlobalView ? '7' : '6' }}" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="ri-stack-line fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                        <h5 class="text-muted">Belum ada data tingkat kelas</h5>
                                        <a href="{{ route('user.grade-levels.create', ['userId' => $userId]) }}" class="btn btn-success">
                                            <i class="ri-add-line me-1"></i>Tambah Tingkat
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $gradeLevels])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-gl').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Tingkat kelas \"" + name + "\" akan dihapus permanen.",
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
                        form.action = '/{{ $userId }}/grade-levels/' + id;
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
