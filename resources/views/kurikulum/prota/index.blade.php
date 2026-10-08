@extends('layouts.master')
@section('title', 'PROTA')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') PROTA @endslot
        @slot('title') Program Tahunan @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">PROTA — Program Tahunan</h4>
            <p class="text-muted mb-0 small">
                Dibangun dari ATP (TP + alokasi JP) dan Pekan Efektif (minggu &amp; JP efektif) — tanpa input ulang pekan efektif.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.kurikulum.cetak.kaldik', ['userId' => $userId]) }}" class="btn btn-light btn-sm" target="_blank">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak Kaldik
            </a>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#prota-modal">
                <i class="ri-add-line align-bottom me-1"></i> Susun PROTA
            </button>
        </div>
    </div>

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

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">— Semua Mapel —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                    <a href="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-refresh-line align-bottom"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-calendar-schedule-line text-primary me-1"></i> Daftar PROTA</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $protaList->count() }} PROTA</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mapel</th>
                        <th>Kelas / Fase</th>
                        <th>Guru</th>
                        <th class="text-center">Minggu Efektif</th>
                        <th class="text-center">JP/Minggu</th>
                        <th class="text-center">JP Efektif</th>
                        <th class="text-center">Total JP</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($protaList as $prota)
                        <tr>
                            <td class="fw-medium">
                                {{ $prota->subject?->name ?? '-' }}
                                @if(in_array($prota->id, $staleIds, true))
                                    <span class="badge bg-warning-subtle text-warning ms-1" title="Sumber data berubah — perlu sinkron">
                                        <i class="ri-refresh-line me-1"></i>Perlu diperbarui
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }}
                                @if($prota->fase) <span class="badge bg-info-subtle text-info ms-1">{{ $prota->fase }}</span> @endif
                            </td>
                            <td class="small">{{ $prota->teacher?->name ?? '—' }}</td>
                            <td class="text-center">{{ $prota->minggu_efektif }}</td>
                            <td class="text-center">{{ $prota->jp_per_minggu }}</td>
                            <td class="text-center fw-semibold">{{ $prota->jp_efektif }}</td>
                            <td class="text-center">
                                <span class="badge {{ $prota->total_jp == $prota->jp_efektif ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ $prota->total_jp }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $prota->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ \App\Models\Prota::STATUS_OPTIONS[$prota->status] ?? $prota->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $prota->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.kurikulum.cetak.prota', ['userId' => $userId, 'id' => $prota->id]) }}" class="btn btn-sm btn-soft-secondary" title="Cetak PDF" target="_blank">
                                    <i class="ri-printer-line"></i>
                                </a>
                                <form action="{{ route('user.kurikulum.prota.destroy', ['userId' => $userId, 'id' => $prota->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus PROTA ini? PROSEM turunannya ikut terhapus.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-calendar-schedule-line fs-1 d-block mb-2"></i>
                                    Belum ada PROTA. Susun dari ATP yang sudah terbit.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade zoomIn" id="prota-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.prota.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-calendar-schedule-line me-1"></i> Susun PROTA dari ATP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($atpOptions->isEmpty())
                            <p class="text-muted small mb-0">
                                Belum ada ATP untuk mapel Anda pada tahun ajaran/semester ini.
                                <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="link-primary">Susun ATP</a> terlebih dahulu.
                            </p>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">ATP <span class="text-danger">*</span></label>
                                    <select name="atp_id" class="form-select" required>
                                        <option value="">-- Pilih ATP --</option>
                                        @foreach($atpOptions as $atp)
                                            <option value="{{ $atp->id }}" {{ in_array($atp->id, $existingAtpIds, true) ? 'disabled' : '' }}>
                                                {{ $atp->subject?->name }} — {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                                                ({{ $atp->items_count }} TP · {{ $atp->total_jp }} JP)
                                                {{ in_array($atp->id, $existingAtpIds, true) ? ' — sudah ada PROTA' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Minggu efektif dan JP efektif otomatis diambil dari Pekan Efektif.</small>
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
                            <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Susun PROTA</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
