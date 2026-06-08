@extends('layouts.master')
@section('title') Lowongan @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('title') Lowongan @endslot
@endcomponent

{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-5">
                                <i class="ri-briefcase-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Total Lowongan</p>
                        <h5 class="mb-0">{{ $statusCounts['all'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-success border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle text-success rounded-2 fs-5">
                                <i class="ri-checkbox-circle-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Aktif</p>
                        <h5 class="mb-0">{{ $statusCounts['aktif'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-warning border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-5">
                                <i class="ri-time-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Draft</p>
                        <h5 class="mb-0">{{ $statusCounts['draft'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-danger border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-danger-subtle text-danger rounded-2 fs-5">
                                <i class="ri-close-circle-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Ditutup</p>
                        <h5 class="mb-0">{{ $statusCounts['ditutup'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table Card --}}
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <h6 class="card-title mb-0 flex-grow-1">Daftar Lowongan</h6>
            <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                <i class="ri-add-line me-1"></i> Buat Lowongan
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card-body border-bottom py-2">
        <form method="GET" action="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="search-box">
                    <input type="text" class="form-control" name="search"
                        placeholder="Cari judul, posisi, atau unit kerja..."
                        value="{{ request('search') }}">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices data-choices-search-false name="status">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ditutup" {{ request('status') == 'ditutup' ? 'selected' : '' }}>Ditutup</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices data-choices-search-false name="kategori">
                    <option value="">Semua Jabatan</option>
                    @foreach($jabatanList ?? [] as $jab)
                        <option value="{{ $jab->uuid }}" {{ request('kategori') == $jab->uuid ? 'selected' : '' }}>
                            {{ $jab->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices data-choices-search-false name="sort">
                    <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                    <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Terpopuler</option>
                </select>
            </div>
            <div class="col-md-3" class="d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ri-filter-line me-1"></i> Filter
                </button>
                <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                    <i class="ri-refresh-line"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-nowrap table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lowongan</th>
                        <th>Kategori Jabatan</th>
                        <th>Unit Kerja</th>
                        <th>Lokasi</th>
                        <th>Kuota</th>
                        <th>Pelamar</th>
                        <th>Status</th>
                        <th class="text-center" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-light text-primary rounded fs-5">
                                            <i class="ri-briefcase-4-line"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}"
                                        class="fw-semibold text-body">{{ $job->judul }}</a>
                                    <div class="small text-muted">{{ $job->kode_lowongan ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($job->jabatan)
                                <span class="badge bg-info-subtle text-info">{{ $job->jabatan->nama }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ $job->workUnit->name ?? '-' }}</td>
                        <td>
                            <i class="ri-map-pin-2-line text-muted me-1"></i>{{ $job->location ?? '-' }}
                        </td>
                        <td>
                            <span class="badge bg-dark-subtle text-dark">{{ $job->kuota ?? 1 }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $job->applications_count ?? 0 }}</span>
                        </td>
                        <td>
                            @php
                                $sClass = match($job->status) {
                                    'aktif' => 'success',
                                    'ditutup' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $sClass }}-subtle text-{{ $sClass }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}">
                                            <i class="ri-eye-line me-2 text-muted"></i>Lihat Detail
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.applications.index', ['userId' => $userId, 'job' => $job->id]) }}">
                                            <i class="ri-user-follow-line me-2 text-muted"></i>Lihat Pelamar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.jobs.edit', ['userId' => $userId, 'job' => $job->id]) }}">
                                            <i class="ri-edit-line me-2 text-muted"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="toggleStatus('{{ $job->id }}')">
                                            <i class="ri-toggle-line me-2"></i>{{ $job->status == 'aktif' ? 'Tutup Lowongan' : 'Buka Kembali' }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="ri-briefcase-line display-5 text-muted"></i>
                                <h6 class="mt-2 mb-1">Belum Ada Lowongan</h6>
                                <p class="text-muted mb-3">Buat lowongan pertama Anda untuk mulai merekrut.</p>
                                <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Buat Lowongan
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
        <div class="border-top px-3 py-2">
            @include('shared._pagination', ['paginator' => $jobs])
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="https://cdn.lordicon.com/lordicon.js"></script>
<script>
function toggleStatus(id) {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Ubah status lowongan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('/{{ $userId }}/ats/jobs/' + id + '/toggle-status', {
                _token: '{{ csrf_token() }}'
            }).then(res => {
                Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
            });
        }
    });
}
let st = null;
document.getElementById('search')?.addEventListener('keyup', function() {
    clearTimeout(st);
    st = setTimeout(() => document.querySelector('#searchForm').submit(), 600);
});
</script>
@endsection