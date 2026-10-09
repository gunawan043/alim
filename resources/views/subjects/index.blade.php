@extends('layouts.master')
@section('title') Mata Pelajaran @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
    <style>
        .kelompok-header { background: #f1f5f9 !important; }
        .badge-kelompok {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
@php
    $userId = $userId ?? auth()->id();
    $currentUser = auth()->user();
    $canViewAllSchools = $currentUser && $currentUser->hasPermissionTo('subject-all-access');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Kurikulum @endslot
    @slot('title') Mata Pelajaran @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
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
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-book-2-line text-primary"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Mata Pelajaran</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label">
                    <i class="ri-information-line me-1"></i>{{ $subjects->where('category', 'nasional')->count() }} nasional · {{ $subjects->where('category', 'muatan_lokal')->count() }} muatan lokal
                </p>
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
                <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Siap dipakai di jadwal &amp; penilaian</p>
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
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tidak dihitung JP aktif</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-timer-line text-info"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total JP / Minggu</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_jp']) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">JP</small></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi jam pelajaran mapel terdaftar</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="subjectList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-lg">
                        <h5 class="card-title mb-0"><i class="ri-book-open-line text-primary me-1"></i>Daftar Mata Pelajaran</h5>
                        <p class="text-muted mb-0">
                            <span class="badge bg-primary-subtle text-primary">{{ $stats['total'] }} mapel</span>
                            <span class="text-muted small ms-2">Kelompok: Agama &bull; Bahasa Arab &bull; Hadits &bull; Umum</span>
                        </p>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($kelompokLabels as $key => $meta)
                                <span class="badge badge-kelompok bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }}">
                                    <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}: {{ ($grouped[$key] ?? collect([]))->count() }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-auto">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <form method="GET" action="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                <input type="text" name="search" class="form-control" style="width:190px"
                                    placeholder="Cari mata pelajaran..." value="{{ request('search') }}">
                                <select name="category" class="form-select" style="width:140px" onchange="this.form.submit()">
                                    <option value="">Kategori</option>
                                    <option value="nasional" {{ request('category') === 'nasional' ? 'selected' : '' }}>Nasional</option>
                                    <option value="muatan_lokal" {{ request('category') === 'muatan_lokal' ? 'selected' : '' }}>Muatan Lokal</option>
                                </select>
                                <select name="is_active" class="form-select" style="width:120px" onchange="this.form.submit()">
                                    <option value="">Status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                                <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                            </form>
                            <a href="{{ route('user.subjects.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Mapel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center">
                    <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                    <a href="{{ request()->fullUrlWithQuery(['is_active' => null, 'category' => null]) }}" class="filter-badge {{ ! request('is_active') && ! request('category') ? 'active' : '' }}">Semua</a>
                    <a href="{{ request()->fullUrlWithQuery(['is_active' => '1', 'category' => null]) }}" class="filter-badge {{ request('is_active') === '1' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i>Aktif</a>
                    <a href="{{ request()->fullUrlWithQuery(['is_active' => '0', 'category' => null]) }}" class="filter-badge {{ request('is_active') === '0' ? 'active' : '' }}"><i class="ri-close-circle-line"></i>Nonaktif</a>
                    <span class="text-muted small ms-2 me-2">·</span>
                    <a href="{{ request()->fullUrlWithQuery(['category' => 'nasional']) }}" class="filter-badge {{ request('category') === 'nasional' ? 'active' : '' }}"><i class="ri-flag-2-line"></i>Nasional</a>
                    <a href="{{ request()->fullUrlWithQuery(['category' => 'muatan_lokal']) }}" class="filter-badge {{ request('category') === 'muatan_lokal' ? 'active' : '' }}"><i class="ri-plant-line"></i>Muatan Lokal</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-freeze mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            @if($canViewAllSchools)<th>Satuan Pendidikan</th>@endif
                            <th style="width:90px;">Kode</th>
                            <th>Nama Mata Pelajaran</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">JP/Minggu</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNum = 0; @endphp
                        @foreach(['agama','arab','hadits','umum'] as $key)
                            @php $items = $grouped[$key] ?? collect([]); @endphp
                            @if($items->isEmpty())
                                @continue
                            @endif
                            @php $meta = $kelompokLabels[$key]; @endphp
                            <tr class="kelompok-header">
                                <td colspan="{{ $canViewAllSchools ? '8' : '7' }}">
                                    <i class="{{ $meta['icon'] }} me-1"></i>
                                    {{ $meta['label'] }}
                                    <span class="text-muted ms-2" style="font-weight:400;text-transform:none;font-size:11px;">
                                        ({{ $items->count() }} mapel · {{ (int) $items->sum('credit_hours') }} JP)
                                    </span>
                                </td>
                            </tr>
                            @foreach($items as $sub)
                                @php $rowNum++ @endphp
                                <tr class="{{ !$sub->is_active ? 'table-secondary opacity-75' : '' }}">
                                    <td class="text-muted text-center" style="font-size:12px;">{{ $rowNum }}</td>
                                    @if($canViewAllSchools)
                                    <td><span style="font-size:12px;" class="text-muted">{{ $sub->school?->name ?? '—' }}</span></td>
                                    @endif
                                    <td><span class="badge bg-dark-subtle text-dark" style="font-size:11px;">{{ $sub->code ?? '—' }}</span></td>
                                    <td>
                                        <a href="{{ route('user.subjects.show', ['userId' => $userId, 'id' => $sub->id]) }}"
                                           class="fw-medium">{{ $sub->name }}</a>
                                        @if($sub->description)
                                            <br><small class="text-muted">{{ $sub->description }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sub->category === 'nasional')
                                            <span class="badge bg-info-subtle text-info" style="font-size:11px;">Nasional</span>
                                        @elseif($sub->category === 'muatan_lokal')
                                            <span class="badge bg-success-subtle text-success" style="font-size:11px;">Muatan Lokal</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning" style="font-size:11px;">{{ $sub->category }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-semibold">{{ $sub->credit_hours ?? '—' }} JP</td>
                                    <td class="text-center">
                                        <span class="badge {{ $sub->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}"
                                              style="font-size:11px;">{{ $sub->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.subjects.edit', ['userId' => $userId, 'id' => $sub->id]) }}"
                                           class="btn btn-soft-primary btn-sm" title="Edit"><i class="ri-pencil-line"></i></a>
                                        <button class="btn btn-soft-danger btn-sm delete-sub"
                                                data-id="{{ $sub->id }}" data-name="{{ $sub->name }}" title="Hapus">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach

                        @if($subjects->isEmpty())
                            <tr>
                                <td colspan="{{ $canViewAllSchools ? '8' : '7' }}" class="text-center py-5">
                                    <div class="avatar-lg mx-auto mb-3">
                                        <div class="avatar-title bg-light rounded-circle">
                                            <i class="ri-book-open-line fs-1 text-muted"></i>
                                        </div>
                                    </div>
                                    <h6 class="text-muted">Belum ada mata pelajaran</h6>
                                    <a href="{{ route('user.subjects.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm mt-1">
                                        <i class="ri-add-line me-1"></i>Tambah Mapel
                                    </a>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-sub').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'Mata pelajaran "' + name + '" akan dihapus permanen.',
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
                        form.action = '/{{ $userId }}/subjects/' + id;
                        var token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}';
                        var method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                        form.appendChild(token); form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
