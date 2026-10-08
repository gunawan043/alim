@extends('layouts.master')
@section('title', 'Detail PROTA')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}">PROTA</a> @endslot
        @slot('title') {{ $prota->subject?->name ?? 'PROTA' }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $prota->subject?->name ?? 'Mapel' }}
                @if($prota->fase) <span class="badge bg-info-subtle text-info align-middle">{{ $prota->fase }}</span> @endif
            </h4>
            <p class="text-muted mb-0 small">
                {{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }}
                · {{ $prota->academicYear?->name }}
                · Semester {{ ucfirst($prota->semester) }}
                · Guru: {{ $prota->teacher?->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.kurikulum.cetak.prota', ['userId' => $userId, 'id' => $prota->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
            </a>
            @if($prosem)
                <a href="{{ route('user.kurikulum.prosem.show', ['userId' => $userId, 'id' => $prosem->id]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-calendar-2-line align-bottom me-1"></i> Buka PROSEM
                </a>
            @else
                <form method="POST" action="{{ route('user.kurikulum.prosem.store', ['userId' => $userId]) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="prota_id" value="{{ $prota->id }}">
                    <button class="btn btn-primary btn-sm"><i class="ri-magic-line align-bottom me-1"></i> Susun PROSEM</button>
                </form>
            @endif
            <a href="{{ route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $prota->academic_year_id, 'semester' => $prota->semester]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali
            </a>
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

    @if($stale['stale'])
        <div class="alert alert-warning" role="alert">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h6 class="alert-heading mb-1"><i class="ri-refresh-line me-1"></i> PROTA perlu diperbarui</h6>
                    <ul class="mb-0 small ps-3">
                        @foreach($stale['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('user.kurikulum.prota.sync', ['userId' => $userId, 'id' => $prota->id]) }}">
                        @csrf
                        <button class="btn btn-sm btn-warning">
                            <i class="ri-refresh-line me-1"></i> Sinkronkan Snapshot
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.kurikulum.prota.sync', ['userId' => $userId, 'id' => $prota->id]) }}"
                          onsubmit="return confirm('Item PROTA akan dibangun ulang dari ATP. Perubahan manual pada baris akan hilang. Lanjutkan?');">
                        @csrf
                        <input type="hidden" name="rebuild" value="1">
                        <button class="btn btn-sm btn-outline-warning">
                            <i class="ri-loop-right-line me-1"></i> Bangun Ulang dari ATP
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Minggu Efektif</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-success">{{ $prota->minggu_efektif }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-calendar-check-line text-success"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">JP / Minggu</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-primary">{{ $prota->jp_per_minggu }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-timer-line text-primary"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">JP Efektif (Kalender)</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-info">{{ $prota->jp_efektif }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="ri-scales-3-line text-info"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Total JP PROTA</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 {{ $itemsTotal == $prota->jp_efektif ? 'text-success' : 'text-warning' }}">{{ $itemsTotal }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-list-ordered-2 text-warning"></i></span></div>
                    </div>
                    @if($itemsTotal != $prota->jp_efektif)
                        <p class="text-warning small mb-0 mt-2">
                            Selisih {{ abs($itemsTotal - $prota->jp_efektif) }} JP dari JP efektif.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i> Pengaturan PROTA</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.kurikulum.prota.update', ['userId' => $userId, 'id' => $prota->id]) }}" class="row g-3 align-items-end">
                @csrf @method('PUT')
                <div class="col-lg-4">
                    <label class="form-label">Guru Pengampu</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">—</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $prota->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" {{ $prota->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="final" {{ $prota->status === 'final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="catatan" class="form-control" value="{{ $prota->catatan }}" maxlength="2000">
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-success w-100"><i class="ri-save-line align-bottom me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-list-ordered-2 text-primary me-1"></i> Rincian TP / BAB / Materi</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $prota->items->count() }} baris · {{ $itemsTotal }} JP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:50px">No</th>
                        <th style="width:180px">BAB / Elemen</th>
                        <th>TP / Materi</th>
                        <th style="width:150px">Alokasi JP</th>
                        <th style="width:180px">Keterangan</th>
                        <th class="text-end" style="width:60px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prota->items as $item)
                        <tr>
                            <td class="text-center">{{ $item->urutan }}</td>
                            <td class="small">{{ $item->bab ?: '—' }}</td>
                            <td>
                                @if($item->tujuanPembelajaran)
                                    <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran->kode_tp }}</span>
                                @endif
                                <div class="small mt-1">{{ $item->materi }}</div>
                            </td>
                            <td>
                                <form action="{{ route('user.kurikulum.prota.items.update', ['userId' => $userId, 'id' => $prota->id, 'itemId' => $item->id]) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PUT')
                                    <input type="text" name="bab" class="form-control form-control-sm" value="{{ $item->bab }}" placeholder="BAB" style="min-width:80px">
                                    <input type="number" name="alokasi_jp" class="form-control form-control-sm" style="width:70px" min="0" max="200" value="{{ $item->alokasi_jp }}">
                                    <input type="hidden" name="materi" value="{{ $item->materi }}">
                                    <input type="hidden" name="keterangan" value="{{ $item->keterangan }}">
                                    <button class="btn btn-sm btn-soft-success" title="Simpan"><i class="ri-check-line"></i></button>
                                </form>
                            </td>
                            <td class="small text-muted">{{ $item->keterangan ?: '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('user.kurikulum.prota.items.destroy', ['userId' => $userId, 'id' => $prota->id, 'itemId' => $item->id]) }}" method="POST"
                                      onsubmit="return confirm('Hapus baris ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-close-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-list-ordered-2 fs-1 d-block mb-2"></i>
                                    Belum ada baris. Sinkronkan ulang dari ATP atau tambah manual.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            <form method="POST" action="{{ route('user.kurikulum.prota.items.store', ['userId' => $userId, 'id' => $prota->id]) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">BAB / Elemen</label>
                    <input type="text" name="bab" class="form-control form-control-sm" maxlength="150">
                </div>
                <div class="col-lg-4">
                    <label class="form-label small text-muted mb-1">TP / Materi <span class="text-danger">*</span></label>
                    <input type="text" name="materi" class="form-control form-control-sm" required maxlength="1000">
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Alokasi JP <span class="text-danger">*</span></label>
                    <input type="number" name="alokasi_jp" class="form-control form-control-sm" min="0" max="200" value="2" required>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255">
                </div>
                <div class="col-lg-1">
                    <button class="btn btn-sm btn-success w-100"><i class="ri-add-line"></i></button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('user.kurikulum.prota.destroy', ['userId' => $userId, 'id' => $prota->id]) }}"
          onsubmit="return confirm('Hapus PROTA ini beserta PROSEM turunannya?');" class="mb-4">
        @csrf @method('DELETE')
        <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line align-bottom me-1"></i> Hapus PROTA</button>
    </form>
@endsection
