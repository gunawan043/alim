@extends('layouts.master')
@section('title') Tahun Ajaran @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Tahun Ajaran @endslot
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
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Tahun Ajaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Terpusat untuk semua satuan pendidikan</p>
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
                    <p class="text-muted mb-0 stat-label">
                        <span class="badge bg-success-subtle text-success stat-label">{{ $stats['aktif'] > 0 ? 'Ada tahun ajaran berjalan' : 'Belum ditetapkan' }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-sun-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Semester Ganjil</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['ganjil']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>Periode terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-moon-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Semester Genap</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['genap']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>Periode terdaftar</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="academicYearList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h5 class="card-title mb-0">Daftar Tahun Ajaran</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $academicYears->total() }} data</span>
                                <span class="text-muted small ms-2">Pengaturan tahun ajaran dan semester terpusat — semua sekolah mengikuti data ini.</span>
                            </p>
                        </div>
                        <div class="col-lg-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.academic-years.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <input type="text" name="search" class="form-control" style="width:200px"
                                        placeholder="Cari tahun ajaran..." value="{{ request('search') }}">
                                    <select name="semester" class="form-select" style="width:130px" onchange="this.form.submit()">
                                        <option value="">Semester</option>
                                        <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <select name="is_active" class="form-select" style="width:120px" onchange="this.form.submit()">
                                        <option value="">Status</option>
                                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                    <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                    <a href="{{ route('user.academic-years.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <a href="{{ route('user.academic-years.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Tahun Ajaran
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => null, 'semester' => null]) }}" class="filter-badge {{ ! request('is_active') && ! request('semester') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => '1', 'semester' => null]) }}" class="filter-badge {{ request('is_active') === '1' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i>Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => '0', 'semester' => null]) }}" class="filter-badge {{ request('is_active') === '0' ? 'active' : '' }}"><i class="ri-close-circle-line"></i>Nonaktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil', 'is_active' => null]) }}" class="filter-badge {{ request('semester') === 'ganjil' ? 'active' : '' }}"><i class="ri-sun-line"></i>Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap', 'is_active' => null]) }}" class="filter-badge {{ request('semester') === 'genap' ? 'active' : '' }}"><i class="ri-moon-line"></i>Genap</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-center">Semester</th>
                                <th>Periode</th>
                                <th>Masa Pendaftaran</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($academicYears as $ay)
                                <tr class="{{ $ay->is_active ? 'table-success' : '' }}">
                                    <td class="text-muted">{{ $loop->iteration + ($academicYears->currentPage() - 1) * $academicYears->perPage() }}</td>
                                    <td>
                                        <a href="{{ route('user.academic-years.show', ['userId' => $userId, 'id' => $ay->id]) }}" class="fw-medium link-primary">
                                            {{ $ay->name }}
                                        </a>
                                        @if($ay->is_active)
                                            <span class="badge bg-success-subtle text-success ms-1 stat-label">Berjalan</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ay->semester === 'ganjil' ? 'primary' : 'info' }}-subtle text-{{ $ay->semester === 'ganjil' ? 'primary' : 'info' }}">
                                            {{ $ay->semester_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            @if($ay->start_date)
                                                {{ $ay->start_date->format('d M Y') }} – {{ $ay->end_date?->format('d M Y') ?? '-' }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            @if($ay->registration_start)
                                                {{ $ay->registration_start->format('d M Y') }} – {{ $ay->registration_end?->format('d M Y') ?? '-' }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($ay->is_active)
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
                                                    <a class="dropdown-item" href="{{ route('user.academic-years.show', ['userId' => $userId, 'id' => $ay->id]) }}">
                                                        <i class="ri-eye-line me-2"></i>Lihat Detail
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.academic-years.edit', ['userId' => $userId, 'id' => $ay->id]) }}">
                                                        <i class="ri-pencil-line me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.academic-years.toggle-active', ['userId' => $userId, 'id' => $ay->id]) }}"
                                                        onclick="return confirm('Yakin ingin {{ $ay->is_active ? 'menonaktifkan' : 'mengaktifkan' }} tahun ajaran ini?')">
                                                        <i class="ri-{{ $ay->is_active ? 'close' : 'check' }}-line me-2"></i>
                                                        {{ $ay->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-ay" href="javascript:void(0)"
                                                        data-id="{{ $ay->id }}" data-name="{{ $ay->name }}">
                                                        <i class="ri-delete-bin-line me-2"></i>Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="ri-calendar-event-line fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                        <h5 class="text-muted">Belum ada data tahun ajaran</h5>
                                        <p class="text-muted">Tambah tahun ajaran baru untuk mulai.</p>
                                        <a href="{{ route('user.academic-years.create', ['userId' => $userId]) }}" class="btn btn-success">
                                            <i class="ri-add-line me-1"></i>Tambah Tahun Ajaran
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $academicYears])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-ay').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Tahun ajaran \"" + name + "\" akan dihapus permanen.",
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
                        form.action = '/{{ $userId }}/academic-years/' + id;
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
