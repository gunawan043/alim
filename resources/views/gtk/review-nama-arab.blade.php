@extends('layouts.master')
@section('title', 'Review Nama Arab GTK')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Data GTK</a> @endslot
        @slot('li_2') Review Nama Arab @endslot
        @slot('title') Review &amp; Approval Nama Arab GTK @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Belum Ada Nama Arab</p>
                <h3 class="fw-bold text-secondary mb-0">{{ number_format($stats['missing']) }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Menunggu Tinjauan</p>
                <h3 class="fw-bold text-warning mb-0">{{ number_format($stats['pending']) }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Disetujui</p>
                <h3 class="fw-bold text-success mb-0">{{ number_format($stats['approved']) }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-1 stat-label">NUPY Ganda</p>
                <h3 class="fw-bold {{ $stats['duplicate_nupy'] > 0 ? 'text-danger' : 'text-muted' }} mb-0">{{ number_format($stats['duplicate_nupy']) }}</h3>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-check-double-line text-success me-1"></i>Review &amp; Approval Nama Arab GTK</h5>
                    <p class="text-muted mb-0 small">Nama berstatus <strong>Disetujui</strong> dipakai pada dokumen resmi (rapor). Impor/generator menghasilkan status <em>menunggu tinjauan</em>.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user.gtk.import-nama-arab-form', ['userId' => $userId]) }}" class="btn btn-outline-dark btn-sm">
                        <i class="ri-upload-2-line me-1"></i>Import / Generate
                    </a>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveAllGtkModal">
                        <i class="ri-check-double-line me-1"></i>Setujui Semua ({{ number_format($stats['pending']) }})
                    </button>
                </div>
            </div>
        </div>

        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter:</span>
                @foreach([
                    'pending' => 'Menunggu',
                    'missing' => 'Belum Ada',
                    'approved' => 'Disetujui',
                    'all' => 'Semua',
                ] as $key => $label)
                    <a href="{{ route('user.gtk.review-nama-arab', ['userId' => $userId, 'status' => $key, 'search' => request('search')]) }}"
                       class="filter-badge {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach

                <form method="GET" class="d-flex gap-2 ms-auto">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" name="search" class="form-control form-control-sm" style="width:200px"
                           placeholder="Cari nama / NUPY..." value="{{ request('search') }}">
                    <button class="btn btn-primary btn-sm"><i class="ri-search-line"></i></button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:150px">NUPY</th>
                        <th>Nama Latin</th>
                        <th style="width:280px">Nama Arab</th>
                        <th style="width:150px" class="text-center">Status</th>
                        <th style="width:110px" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td class="small text-muted">{{ $employee->employment?->nupy ?: '—' }}</td>
                            <td class="fw-medium">{{ $employee->name }}</td>
                            <td>
                                <form method="POST"
                                      action="{{ route('user.gtk.review-nama-arab-approve', ['userId' => $userId, 'gtkUserId' => $employee->id]) }}"
                                      class="d-flex gap-2">
                                    @csrf
                                    <input type="text" name="name_ar" class="form-control form-control-sm" dir="rtl"
                                           value="{{ $employee->name_ar }}" placeholder="الاسم بالعربية" maxlength="255" required>
                                    <button type="submit" class="btn btn-sm btn-success flex-shrink-0" title="Simpan & setujui">
                                        <i class="ri-check-line"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                @if(! $employee->name_ar)
                                    <span class="badge bg-secondary-subtle text-secondary">Belum ada</span>
                                @elseif($employee->name_ar_status === 'approved')
                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Disetujui</span>
                                    @if($employee->name_ar_reviewed_at)
                                        <div class="text-muted" style="font-size:10px;">{{ $employee->name_ar_reviewed_at->format('d M Y H:i') }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i>Menunggu</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $employee->id]) }}"
                                   class="btn btn-sm btn-soft-secondary" title="Edit data GTK"><i class="ri-pencil-line"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <i class="ri-check-double-line fs-3 d-block mb-2"></i>
                            Tidak ada data pada filter ini.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body pt-0">
            @include('shared._pagination', ['paginator' => $employees])
        </div>
    </div>
@endsection

@section('modal')
    <form id="approveAllGtkForm" method="POST"
          action="{{ route('user.gtk.review-nama-arab-approve-all', ['userId' => $userId]) }}">
        @csrf
    </form>

    <x-modal-confirm id="approveAllGtkModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        title="Setujui Semua Nama Arab GTK?"
        message="Seluruh nama Arab GTK berstatus <strong>menunggu tinjauan</strong> akan disetujui dan dipakai pada dokumen resmi."
        submit-label="Ya, Setujui Semua"
        submit-icon="ri-check-double-line"
        submit-class="btn-success"
        form-id="approveAllGtkForm" />
@endsection
