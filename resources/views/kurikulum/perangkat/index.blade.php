@extends('layouts.master')
@section('title', 'RPM / Perangkat Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $totalRpm = $perangkatList->count();
        $finalCount = $perangkatList->where('status', 'final')->count();
        $draftCount = $totalRpm - $finalCount;
        $serumpunCount = $perangkatList->filter(function ($p) use ($userId, $mySubjectIds) {
            $isOwner = $p->teacher_id === $userId || $p->created_by === $userId;

            return ! $isOwner && in_array($p->subject_id, $mySubjectIds ?? [], true);
        })->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Perangkat @endslot
        @slot('title') Perangkat Pembelajaran @endslot
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
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-booklet-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total RPM</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalRpm) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Rencana Pembelajaran Mendalam</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-verified-badge-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Final</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($finalCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Siap digunakan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-draft-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($draftCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-edit-line me-1"></i>Masih disusun</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-team-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Serumpun</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($serumpunCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-share-forward-line me-1"></i>RPM bersama mapel Anda</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="perangkatList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar RPM / Perangkat</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $totalRpm }} RPM</span>
                                <span class="text-muted small ms-2">Disiapkan dari ATP: kelas, mapel, guru, CP/TP, dan JP efektif yang sama.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <select name="academic_year_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <select name="subject_id" class="form-select" style="width:180px" onchange="this.form.submit()">
                                        <option value="">— Semua Mapel —</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#perangkat-modal">
                                    <i class="ri-add-line align-bottom me-1"></i> Buat RPM
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
                        <span class="text-muted small">{{ $serumpunCount }} RPM serumpun dapat dilihat bersama</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Judul</th>
                                <th>Mapel</th>
                                <th>Kelas</th>
                                <th>Guru</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">ATP</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:180px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($perangkatList as $perangkat)
                                @php
                                    $isOwner = $perangkat->teacher_id === $userId || $perangkat->created_by === $userId;
                                    $isSerumpun = ! $isOwner && in_array($perangkat->subject_id, $mySubjectIds ?? [], true);
                                @endphp
                                <tr>
                                    <td class="fw-medium">{{ $perangkat->judul }}</td>
                                    <td>
                                        {{ $perangkat->subject?->name ?? '-' }}
                                        @if($isSerumpun)
                                            <span class="badge bg-info-subtle text-info ms-1" title="RPM bersama untuk mapel & jenjang/fase yang sama">Serumpun</span>
                                        @endif
                                    </td>
                                    <td>{{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? '—' }}</td>
                                    <td class="small">{{ $perangkat->teacher?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $perangkat->tipe === 'agama' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }}">
                                            {{ \App\Models\PerangkatPembelajaran::TIPE_OPTIONS[$perangkat->tipe] ?? $perangkat->tipe }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($perangkat->atp)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $perangkat->atp->total_jp }} JP</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $perangkat->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$perangkat->status] ?? $perangkat->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('user.kurikulum.cetak.rpm', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-sm btn-soft-secondary" title="Cetak PDF" target="_blank">
                                            <i class="ri-printer-line"></i>
                                        </a>
                                        @if($isOwner)
                                            <form action="{{ route('user.kurikulum.perangkat.destroy', ['userId' => $userId, 'id' => $perangkat->id]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus perangkat ini?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-booklet-line fs-1 d-block mb-2"></i>
                                            Belum ada perangkat. Buat dari ATP yang sudah tersusun.
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

    <div class="modal fade zoomIn" id="perangkat-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.perangkat.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-booklet-line me-1"></i> Buat Perangkat dari ATP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($atpOptions->isEmpty())
                            <p class="text-muted small mb-0">
                                Belum ada ATP untuk mapel Anda.
                                <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="link-primary">Susun ATP</a> terlebih dahulu.
                            </p>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">ATP <span class="text-danger">*</span></label>
                                    <select name="atp_id" id="perangkat-atp" class="form-select" required onchange="applyAtpDefaults()">
                                        <option value="">-- Pilih ATP --</option>
                                        @foreach($atpOptions as $atp)
                                            <option value="{{ $atp->id }}"
                                                data-subject="{{ $atp->subject?->name }}"
                                                data-grade="{{ $atp->gradeLevel?->name }}"
                                                data-jp="{{ $atp->total_jp }}"
                                                {{ request('atp_id') === $atp->id ? 'selected' : '' }}>
                                                {{ $atp->subject?->name }} — {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }} ({{ $atp->total_jp }} JP)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Judul Perangkat <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" id="perangkat-judul" class="form-control" required maxlength="255">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Struktur RPM <span class="text-danger">*</span></label>
                                    <select name="tipe" class="form-select" required>
                                        @foreach(\App\Models\PerangkatPembelajaran::TIPE_OPTIONS as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Guru Agama &amp; Guru Umum memiliki struktur RPM yang berbeda — pilih sesuai kebutuhan.</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Kelas</label>
                                    <select name="study_group_id" class="form-select">
                                        <option value="">— Semua Kelas —</option>
                                        @foreach($studyGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
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
                        @if($atpOptions->isNotEmpty())
                            <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Buat</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function applyAtpDefaults() {
            var select = document.getElementById('perangkat-atp');
            var judul = document.getElementById('perangkat-judul');
            if (! select || ! judul) return;
            var opt = select.options[select.selectedIndex];
            if (opt && opt.value && ! judul.value) {
                judul.value = 'Perangkat ' + (opt.dataset.subject || '') + ' — ' + (opt.dataset.grade || 'Semua Jenjang');
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('perangkat-atp');
            if (select && select.value) { applyAtpDefaults(); }
        });
    </script>
@endsection
