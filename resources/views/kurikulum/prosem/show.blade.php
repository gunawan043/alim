@extends('layouts.master')
@section('title', 'Detail PROSEM')

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $monthLabel = function ($date) {
            if (! $date) {
                return null;
            }

            return \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('F Y');
        };
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}">PROSEM</a> @endslot
        @slot('title') {{ $prosem->subject?->name ?? 'PROSEM' }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $prosem->subject?->name ?? 'Mapel' }}
                @if($prosem->gradeLevel?->fase) <span class="badge bg-info-subtle text-info align-middle">{{ $prosem->gradeLevel->fase }}</span> @endif
            </h4>
            <p class="text-muted mb-0 small">
                {{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }}
                · {{ $prosem->academicYear?->name }}
                · Semester {{ ucfirst($prosem->semester) }}
                · Guru: {{ $prosem->teacher?->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.kurikulum.cetak.prosem', ['userId' => $userId, 'id' => $prosem->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
            </a>
            @if($prosem->prota)
                <a href="{{ route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $prosem->prota->id]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-calendar-schedule-line align-bottom me-1"></i> Buka PROTA
                </a>
            @endif
            <a href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId, 'academic_year_id' => $prosem->academic_year_id, 'semester' => $prosem->semester]) }}" class="btn btn-light btn-sm">
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
                    <h6 class="alert-heading mb-1"><i class="ri-refresh-line me-1"></i> PROSEM perlu diperbarui</h6>
                    <ul class="mb-0 small ps-3">
                        @foreach($stale['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
                <form method="POST" action="{{ route('user.kurikulum.prosem.sync', ['userId' => $userId, 'id' => $prosem->id]) }}">
                    @csrf
                    <button class="btn btn-sm btn-warning"><i class="ri-refresh-line me-1"></i> Sinkronkan Distribusi</button>
                </form>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Total JP Terdistribusi</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-primary">{{ $totalJp }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-list-ordered-2 text-primary"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Pekan Efektif</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-success">{{ $effectiveWeeks }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-calendar-check-line text-success"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Sumber Pekan</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-16 fw-semibold mb-0">{{ $prosem->academicYear?->name }} · Smt {{ ucfirst($prosem->semester) }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="ri-calendar-event-line text-info"></i></span></div>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Dari Kalender Pendidikan — tanpa kalender kedua.</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Status Distribusi</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 {{ $overflowJp ? 'text-danger' : 'text-success' }}">
                            {{ $overflowJp ? 'Lebih' : 'Aman' }}
                        </h4>
                        <div class="avatar-sm">
                            <span class="avatar-title {{ $overflowJp ? 'bg-danger-subtle' : 'bg-success-subtle' }} rounded fs-3">
                                <i class="{{ $overflowJp ? 'ri-error-warning-line text-danger' : 'ri-check-double-line text-success' }}"></i>
                            </span>
                        </div>
                    </div>
                    @if($overflowJp)
                        <p class="text-danger small mb-0 mt-2">{{ $overflowJp }} baris melebihi pekan efektif.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i> Pengaturan PROSEM</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.kurikulum.prosem.update', ['userId' => $userId, 'id' => $prosem->id]) }}" class="row g-3 align-items-end">
                @csrf @method('PUT')
                <div class="col-lg-4">
                    <label class="form-label">Guru Pengampu</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">—</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $prosem->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" {{ $prosem->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="final" {{ $prosem->status === 'final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="catatan" class="form-control" value="{{ $prosem->catatan }}" maxlength="2000">
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-success w-100"><i class="ri-save-line align-bottom me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Distribusi TP / Materi ke Pekan &amp; Bulan</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $prosem->items->count() }} baris</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:50px">No</th>
                        <th>TP / Materi</th>
                        <th class="text-center" style="width:80px">JP</th>
                        <th style="width:200px">Bulan</th>
                        <th style="width:140px">Pekan Ke</th>
                        <th style="width:140px">Realisasi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prosem->items as $item)
                        @php
                            $startWeek = $weeks[$item->mulai_minggu_ke] ?? null;
                            $endWeek = $weeks[$item->selesai_minggu_ke] ?? null;
                            $startMonth = $monthLabel($startWeek?->tanggal_mulai);
                            $endMonth = $monthLabel($endWeek?->tanggal_selesai);
                            $bulan = $startMonth ? ($endMonth && $endMonth !== $startMonth ? $startMonth.' – '.$endMonth : $startMonth) : '—';
                            $pekan = $item->mulai_minggu_ke > 0
                                ? 'Pekan '.$item->mulai_minggu_ke.($item->selesai_minggu_ke > $item->mulai_minggu_ke ? '–'.$item->selesai_minggu_ke : '')
                                : '—';
                            $rc = $realisasiCounts[$item->id] ?? null;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $item->urutan }}</td>
                            <td>
                                @if($item->tujuanPembelajaran)
                                    <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran->kode_tp }}</span>
                                @endif
                                <div class="small mt-1">{{ $item->tujuanPembelajaran?->deskripsi ?? $item->protaItem?->materi ?? '—' }}</div>
                            </td>
                            <td class="text-center fw-semibold">{{ $item->jp }}</td>
                            <td class="small">{{ $bulan }}</td>
                            <td class="small">{{ $pekan }}</td>
                            <td class="small">
                                @if($rc)
                                    <span class="badge bg-success-subtle text-success">{{ $rc->total }} pertemuan</span>
                                    <span class="text-muted">· {{ $rc->books }} kelas</span>
                                @else
                                    <span class="text-muted">Belum tercatat</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $item->keterangan ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-calendar-2-line fs-1 d-block mb-2"></i>
                                    Belum ada distribusi. Klik <strong>Sinkronkan Distribusi</strong> untuk menghitung dari Pekan Efektif.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('user.kurikulum.prosem.destroy', ['userId' => $userId, 'id' => $prosem->id]) }}"
          onsubmit="return confirm('Hapus PROSEM ini?');" class="mb-4">
        @csrf @method('DELETE')
        <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line align-bottom me-1"></i> Hapus PROSEM</button>
    </form>
@endsection
