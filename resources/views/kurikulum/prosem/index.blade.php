@extends('layouts.master')
@section('title', 'PROSEM')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $totalProsem = $prosemList->count();
        $adjustedCount = count($adjustedIds ?? []);
        $staleCount = count($staleIds);
        $totalBaris = (int) $prosemList->sum('items_count');
        $totalJp = (int) $prosemList->sum(fn ($p) => (int) ($p->prota?->total_jp ?? 0));
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') PROSEM @endslot
        @slot('title') Program Semester @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total PROSEM</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalProsem) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ number_format($totalBaris) }} baris distribusi</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-equalizer-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Disesuaikan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($adjustedCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-hand-coin-line me-1"></i>Ada penyesuaian manual</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title {{ $staleCount > 0 ? 'bg-warning-subtle' : 'bg-success-subtle' }} rounded fs-2">
                                <i class="{{ $staleCount > 0 ? 'ri-refresh-line text-warning' : 'ri-checkbox-circle-line text-success' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Perlu Diperbarui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($staleCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>{{ $staleCount > 0 ? 'PROTA berubah — sinkronkan' : 'Semua sinkron' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-timer-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total JP PROTA</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalJp) }}<small class="fw-normal text-muted ms-1 stat-label">JP</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>Alokasi yang didistribusikan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="prosemList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar PROSEM</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $totalProsem }} PROSEM</span>
                                <span class="text-muted small ms-2">Distribusi TP/materi ke pekan efektif dari Kalender Pendidikan.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <select name="academic_year_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <a href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#prosem-modal">
                                    <i class="ri-add-line align-bottom me-1"></i> Susun PROSEM
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil']) }}" class="filter-badge {{ $semester === 'ganjil' ? 'active' : '' }}">Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap']) }}" class="filter-badge {{ $semester === 'genap' ? 'active' : '' }}">Genap</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small">{{ $adjustedCount }} disesuaikan · {{ $staleCount }} perlu diperbarui</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mapel</th>
                                <th>Kelas / Fase</th>
                                <th>Guru</th>
                                <th class="text-center">Total JP PROTA</th>
                                <th class="text-center">Baris</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:160px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prosemList as $prosem)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $prosem->subject?->name ?? '-' }}
                                        @if(in_array($prosem->id, $staleIds, true))
                                            <span class="badge bg-warning-subtle text-warning ms-1" title="PROTA berubah — perlu sinkron">
                                                <i class="ri-refresh-line me-1"></i>Perlu diperbarui
                                            </span>
                                        @endif
                                        @if(in_array($prosem->id, $adjustedIds ?? [], true))
                                            <span class="badge bg-primary-subtle text-primary ms-1" title="Ada penyesuaian manual">
                                                <i class="ri-equalizer-line me-1"></i>Disesuaikan
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }}
                                        @if($prosem->gradeLevel?->fase) <span class="badge bg-info-subtle text-info ms-1">{{ $prosem->gradeLevel->fase }}</span> @endif
                                    </td>
                                    <td class="small">{{ $prosem->teacher?->name ?? '—' }}</td>
                                    <td class="text-center">{{ $prosem->prota?->total_jp ?? '—' }}</td>
                                    <td class="text-center">{{ $prosem->items_count }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $prosem->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ \App\Models\Prosem::STATUS_OPTIONS[$prosem->status] ?? $prosem->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.kurikulum.prosem.show', ['userId' => $userId, 'id' => $prosem->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('user.kurikulum.cetak.prosem', ['userId' => $userId, 'id' => $prosem->id]) }}" class="btn btn-sm btn-soft-secondary" title="Cetak PDF" target="_blank">
                                            <i class="ri-printer-line"></i>
                                        </a>
                                        <form action="{{ route('user.kurikulum.prosem.destroy', ['userId' => $userId, 'id' => $prosem->id]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus PROSEM ini?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-calendar-2-line fs-1 d-block mb-2"></i>
                                            Belum ada PROSEM. Susun dari PROTA yang sudah dibuat.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade zoomIn" id="prosem-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.prosem.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-calendar-2-line me-1"></i> Susun PROSEM dari PROTA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($protaOptions->isEmpty())
                            <p class="text-muted small mb-0">
                                Belum ada PROTA tanpa PROSEM untuk mapel Anda.
                                <a href="{{ route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="link-primary">Susun PROTA</a> terlebih dahulu.
                            </p>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">PROTA <span class="text-danger">*</span></label>
                                    <select name="prota_id" class="form-select" required>
                                        <option value="">-- Pilih PROTA --</option>
                                        @foreach($protaOptions as $prota)
                                            <option value="{{ $prota->id }}">
                                                {{ $prota->subject?->name }} — {{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }}
                                                ({{ $prota->items_count }} baris · {{ $prota->total_jp }} JP)
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Distribusi pekan &amp; bulan dihitung dari Pekan Efektif (Kalender Pendidikan).</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Catatan</label>
                                    <textarea name="catatan" rows="2" class="form-control" placeholder="Opsional"></textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        @if($protaOptions->isNotEmpty())
                            <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Susun PROSEM</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
