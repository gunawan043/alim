@extends('layouts.master')
@section('title') Bank Soal @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $stats = $statistics ?? ['total' => 0, 'soal' => 0, 'publik' => 0, 'tahun_ajaran_ini' => 0];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Bank Soal @endslot
        @slot('title') Daftar Bank Soal @endslot
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
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-archive-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Bank</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Bank yang Anda akses</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-file-list-3-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Soal Terdaftar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['soal']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Soal approved di bank Anda</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-global-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Bank Publik / Share</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['publik']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-share-forward-line me-1"></i>Publik &amp; kolom publik</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-2-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tahun Ajaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['tahun_ajaran_ini']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-calendar-check-line me-1"></i>{{ $activeYear->name ?? 'Belum ada TA aktif' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card" id="bankSoalList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4">
                            <h5 class="card-title mb-0">Bank Soal</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $banks->total() }} bank</span>
                                <span class="text-muted small ms-2">Kelola kumpulan soal per mapel &amp; jenjang.</span>
                            </p>
                        </div>
                        <div class="col-lg-8">
                            <form method="GET" class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <input type="text" name="search" class="form-control" style="width:200px"
                                       placeholder="Cari nama bank soal..." value="{{ request('search') }}">
                                @if(request()->filled('is_public'))<input type="hidden" name="is_public" value="{{ request('is_public') }}">@endif
                                <select name="subject_id" class="form-select" style="width:150px">
                                    <option value="">Semua Mapel</option>
                                    @foreach($subjects as $s)
                                        <option value="{{ $s->id }}" {{ (string) request('subject_id') === (string) $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="jenis_soal" class="form-select" style="width:140px">
                                    <option value="">Semua Jenis</option>
                                    <option value="pilihan_ganda" {{ request('jenis_soal') === 'pilihan_ganda' ? 'selected' : '' }}>Pilihan Ganda</option>
                                    <option value="benar_salah" {{ request('jenis_soal') === 'benar_salah' ? 'selected' : '' }}>Benar/Salah</option>
                                    <option value="uraian" {{ request('jenis_soal') === 'uraian' ? 'selected' : '' }}>Uraian</option>
                                    <option value="campuran" {{ request('jenis_soal') === 'campuran' ? 'selected' : '' }}>Campuran</option>
                                </select>
                                <select name="shared_scope" class="form-select" style="width:150px">
                                    <option value="">Semua Jangkauan</option>
                                    <option value="private" {{ request('shared_scope') === 'private' ? 'selected' : '' }}>Privat</option>
                                    <option value="internal_school" {{ request('shared_scope') === 'internal_school' ? 'selected' : '' }}>Internal Sekolah</option>
                                    <option value="public_pool" {{ request('shared_scope') === 'public_pool' ? 'selected' : '' }}>Publik</option>
                                </select>
                                <select name="academic_year_id" class="form-select" style="width:140px">
                                    <option value="">Semua T.A.</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) request('academic_year_id') === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ url()->current() }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Bank Soal
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['shared_scope' => null, 'is_public' => null]) }}"
                           class="filter-badge {{ ! request()->filled('shared_scope') && ! request()->filled('is_public') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['shared_scope' => 'private', 'is_public' => null]) }}"
                           class="filter-badge {{ request('shared_scope') === 'private' ? 'active' : '' }}"><i class="ri-lock-line"></i> Privat</a>
                        <a href="{{ request()->fullUrlWithQuery(['shared_scope' => 'internal_school', 'is_public' => null]) }}"
                           class="filter-badge {{ request('shared_scope') === 'internal_school' ? 'active' : '' }}"><i class="ri-community-line"></i> Internal</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_public' => 1, 'shared_scope' => null]) }}"
                           class="filter-badge {{ request()->filled('is_public') && (bool) request('is_public') ? 'active' : '' }}"><i class="ri-global-line"></i> Publik</a>
                        @if($activeYear)
                            <span class="text-muted small ms-2 me-2">·</span>
                            <a href="{{ request()->fullUrlWithQuery(['academic_year_id' => $activeYear->id]) }}"
                               class="filter-badge {{ (string) request('academic_year_id') === (string) $activeYear->id ? 'active' : '' }}">
                                <i class="ri-calendar-check-line"></i> T.A. {{ $activeYear->name }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:48px">#</th>
                                <th>Nama Bank Soal</th>
                                <th>Mapel</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Tingkat</th>
                                <th class="text-center">Jumlah Soal</th>
                                <th class="text-center">Jangkauan</th>
                                <th>Pemilik</th>
                                <th class="text-end" style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($banks as $bank)
                                <tr>
                                    <td class="text-center text-muted">{{ $loop->iteration + ($banks->currentPage() - 1) * $banks->perPage() }}</td>
                                    <td>
                                        <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}"
                                           class="fw-medium link-primary">
                                            {{ $bank->nama }}
                                        </a>
                                        @if($bank->deskripsi)
                                            <br><small class="text-muted">{{ Str::limit($bank->deskripsi, 80) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $bank->subject?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info">
                                            {{ ucwords(str_replace('_', ' ', $bank->jenis_soal)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $bank->tingkat_kesulitan_target ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $bank->soal_count ?? 0 }} soal
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($bank->shared_scope === 'private')
                                            <span class="badge bg-danger-subtle text-danger">Privat</span>
                                        @elseif($bank->shared_scope === 'internal_school')
                                            <span class="badge bg-warning-subtle text-warning">Internal</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Publik</span>
                                        @endif
                                        @if($bank->is_public)
                                            &nbsp;<i class="ri-global-line text-success" title="Is Public"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($bank->owner)
                                            <span class="text-muted small">{{ $bank->owner->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                        <i class="ri-eye-line me-2"></i>Lihat
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="{{ route('user.bank-soal.edit', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                        <i class="ri-pencil-line me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="{{ route('user.bank-soal.clone', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                        <i class="ri-file-copy-line me-2"></i>Clone
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-bank"
                                                       href="javascript:void(0)"
                                                       data-id="{{ $bank->id }}" data-name="{{ $bank->nama }}">
                                                        <i class="ri-delete-bin-line me-2"></i>Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="ri-question-line fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                        <h5 class="text-muted">Belum ada Bank Soal</h5>
                                        <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}"
                                           class="btn btn-success mt-2">
                                            <i class="ri-add-line me-1"></i>Tambah Bank Soal
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $banks])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-bank').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Bank soal \"" + name + "\" akan dihapus permanen.",
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
                        form.action = '/{{ $userId }}/bank-soal/' + id;
                        ['_token', '_method'].forEach(function(n, i) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = n;
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
