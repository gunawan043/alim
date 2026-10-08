@extends('layouts.master')
@section('title', 'PROSEM')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') PROSEM @endslot
        @slot('title') Program Semester @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">PROSEM — Program Semester</h4>
            <p class="text-muted mb-0 small">
                Distribusi PROTA/ATP ke bulan &amp; pekan efektif dari Kalender Pendidikan — minggu libur, ujian, dan kegiatan dikenali otomatis.
            </p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#prosem-modal">
            <i class="ri-add-line align-bottom me-1"></i> Susun PROSEM
        </button>
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
            <form method="GET" action="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
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
                <div class="col-xxl-2 col-sm-6">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Daftar PROSEM</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $prosemList->count() }} PROSEM</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
