@extends('layouts.master')
@section('title') Detail ATP @endsection

@section('css')
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') {{ $atp->subject?->name ?? 'ATP' }} @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

    <div class="row mb-3">
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">{{ $atp->subject?->name ?? 'Mapel' }}</h5>
                            <div class="text-muted small">
                                {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                                @if($atp->fase) · Fase {{ $atp->fase }} @endif
                                · {{ $atp->academicYear?->name }}
                                · Semester {{ ucfirst($atp->semester) }}
                            </div>
                        </div>
                        <span class="badge {{ $atp->status === 'published' ? 'badge-soft-success' : 'badge-soft-warning' }}">
                            {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$atp->status] ?? $atp->status }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('user.kurikulum.atp.update', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-2 mt-3 align-items-end">
                        @csrf @method('PUT')
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Guru Penyusun</label>
                            <select name="teacher_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ $atp->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="draft" {{ $atp->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $atp->status === 'published' ? 'selected' : '' }}>Terbit</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $atp->catatan }}" maxlength="2000">
                        </div>
                        <div class="col-md-12 d-flex gap-2 mt-2">
                            <button class="btn btn-sm btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'atp_id' => $atp->id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="ri-booklet-line me-1"></i> Perangkat ({{ $perangkatCount }})
                            </a>
                            <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
                               class="btn btn-sm btn-light">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3"><i class="ri-scales-3-line me-1"></i> Kesesuaian Alokasi JP</h6>

                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">JP efektif tersedia</span>
                        <strong>{{ $jpTersedia }} JP</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">{{ $weeklyHours }} JP/minggu × {{ $mingguEfektif }} minggu efektif</span>
                        <span class="text-muted">
                            {{ ($ringkasan['is_persisted'] ?? false) ? 'Pekan Efektif tersimpan' : 'Hitung langsung dari Kalender' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between small mt-1">
                        <span class="text-muted">JP teralokasi di ATP</span>
                        <strong>{{ $jpTerpakai }} JP</strong>
                    </div>

                    @php
                        $progress = $jpTersedia > 0 ? min(100, round($jpTerpakai / $jpTersedia * 100)) : 0;
                        $progressClass = $statusAlokasi === 'lebih' ? 'bg-danger' : ($statusAlokasi === 'pas' ? 'bg-success' : 'bg-warning');
                    @endphp
                    <div class="progress mt-2" style="height:8px">
                        <div class="progress-bar {{ $progressClass }}" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="mt-3">
                        @if($statusAlokasi === 'pas')
                            <span class="badge badge-soft-success p-2"><i class="ri-check-line me-1"></i> Alokasi pas dengan JP efektif</span>
                        @elseif($statusAlokasi === 'lebih')
                            <span class="badge badge-soft-danger p-2"><i class="ri-error-warning-line me-1"></i> Melebihi JP efektif sebanyak {{ abs($selisih) }} JP</span>
                        @else
                            <span class="badge badge-soft-warning p-2"><i class="ri-information-line me-1"></i> Kurang {{ abs($selisih) }} JP dari JP efektif</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ri-list-ordered-2 me-1"></i> Susunan Tujuan Pembelajaran</h6>
            <span class="badge bg-secondary-subtle text-secondary">{{ $atp->items->count() }} TP</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:90px">Urutan</th>
                            <th>TP</th>
                            <th style="width:220px">Alokasi JP &amp; Catatan</th>
                            <th class="text-end" style="width:90px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atp->items as $item)
                            <tr>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                            @csrf <input type="hidden" name="direction" value="up">
                                            <button class="btn btn-sm btn-link p-0"><i class="ri-arrow-up-line"></i></button>
                                        </form>
                                        <span class="fw-semibold">{{ $item->urutan }}</span>
                                        <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                            @csrf <input type="hidden" name="direction" value="down">
                                            <button class="btn btn-sm btn-link p-0"><i class="ri-arrow-down-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran?->kode_tp }}</span>
                                    <div class="small mt-1">{{ $item->tujuanPembelajaran?->deskripsi }}</div>
                                    @if($item->tujuanPembelajaran?->elemen)
                                        <div class="small text-muted">{{ $item->tujuanPembelajaran->elemen }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('user.kurikulum.atp.items.update', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST" class="d-flex gap-1">
                                        @csrf @method('PUT')
                                        <input type="number" name="jp_alokasi" class="form-control form-control-sm" style="width:70px" min="0" max="100" value="{{ $item->jp_alokasi }}">
                                        <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $item->catatan }}" placeholder="Catatan">
                                        <button class="btn btn-sm btn-outline-success"><i class="ri-check-line"></i></button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('user.kurikulum.atp.items.destroy', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST"
                                          onsubmit="return confirm('Keluarkan TP ini dari ATP?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="ri-close-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada TP. Tambahkan dari daftar di bawah.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ri-add-circle-line me-1"></i> Tambah TP ke ATP</h6>
        </div>
        <div class="card-body">
            @if($availableTps->isEmpty())
                <p class="text-muted small mb-0">
                    Tidak ada TP tersedia. Buat TP terlebih dahulu di
                    <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'subject_id' => $atp->subject_id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}">halaman TP</a>.
                </p>
            @else
                <form method="POST" action="{{ route('user.kurikulum.atp.items.store', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Tujuan Pembelajaran</label>
                        <select name="tujuan_pembelajaran_id" id="atp-tp-select" class="form-select form-select-sm" required onchange="applyTpJp()">
                            <option value="">-- Pilih TP --</option>
                            @foreach($availableTps as $tp)
                                <option value="{{ $tp->id }}" data-jp="{{ $tp->alokasi_waktu }}">[{{ $tp->kode_tp }}] {{ \Illuminate\Support\Str::limit($tp->deskripsi, 80) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">JP</label>
                        <input type="number" name="jp_alokasi" id="atp-tp-jp" class="form-control form-control-sm" min="0" max="100" value="2">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-success w-100"><i class="ri-add-line"></i></button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        function applyTpJp() {
            var select = document.getElementById('atp-tp-select');
            if (! select) return;
            var opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.jp) {
                document.getElementById('atp-tp-jp').value = opt.dataset.jp;
            }
        }
    </script>
@endsection
