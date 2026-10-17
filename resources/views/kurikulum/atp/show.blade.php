@extends('layouts.master')
@section('title', 'Detail ATP')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') {{ $atp->subject?->name ?? 'ATP' }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $atp->subject?->name ?? 'Mapel' }}
                @if($atp->fase) <span class="badge bg-info-subtle text-info align-middle">{{ $atp->fase }}</span> @endif
            </h4>
            <p class="text-muted mb-0 small">
                {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                · {{ $atp->academicYear?->name }}
                · Semester {{ ucfirst($atp->semester) }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.kurikulum.cetak.atp', ['userId' => $userId, 'id' => $atp->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
            </a>
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'atp_id' => $atp->id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
               class="btn btn-soft-primary btn-sm">
                <i class="ri-booklet-line align-bottom me-1"></i> Perangkat ({{ $perangkatCount }})
            </a>
            <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
               class="btn btn-light btn-sm">
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

    <div class="row">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i> Pengaturan ATP</h5>
                    <span class="badge {{ $atp->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                        {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$atp->status] ?? $atp->status }}
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kurikulum.atp.update', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-3 align-items-end">
                        @csrf @method('PUT')
                        <div class="col-md-5">
                            <label class="form-label">Guru Penyusun</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">—</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ $atp->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" {{ $atp->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $atp->status === 'published' ? 'selected' : '' }}>Terbit</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control" value="{{ $atp->catatan }}" maxlength="2000">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="ri-save-line align-bottom me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-scales-3-line text-primary me-1"></i> Kesesuaian Alokasi JP</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">JP efektif tersedia</span>
                        <span class="fw-semibold">{{ $jpTersedia }} JP</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">{{ $weeklyHours }} JP/minggu × {{ $mingguEfektif }} minggu efektif</span>
                        <span class="badge {{ ($ringkasan['is_persisted'] ?? false) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" style="font-size:0.65rem">
                            {{ ($ringkasan['is_persisted'] ?? false) ? 'Pekan Efektif tersimpan' : 'Hitung dari Kalender' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">JP teralokasi di ATP</span>
                        <span class="fw-semibold">{{ $jpTerpakai }} JP</span>
                    </div>

                    @php
                        $progress = $jpTersedia > 0 ? min(100, round($jpTerpakai / $jpTersedia * 100)) : 0;
                        $progressClass = $statusAlokasi === 'lebih' ? 'bg-danger' : ($statusAlokasi === 'pas' ? 'bg-success' : 'bg-warning');
                    @endphp
                    <div class="progress" style="height:8px">
                        <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <div class="mt-3">
                        @if($statusAlokasi === 'pas')
                            <span class="badge bg-success-subtle text-success p-2"><i class="ri-check-double-line me-1"></i> Alokasi pas dengan JP efektif</span>
                        @elseif($statusAlokasi === 'lebih')
                            <span class="badge bg-danger-subtle text-danger p-2"><i class="ri-error-warning-line me-1"></i> Melebihi JP efektif sebanyak {{ abs($selisih) }} JP</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning p-2"><i class="ri-information-line me-1"></i> Kurang {{ abs($selisih) }} JP dari JP efektif</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-list-ordered-2 text-primary me-1"></i> Susunan Tujuan Pembelajaran</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $atp->items->count() }} TP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:120px">Urutan</th>
                        <th>TP</th>
                        <th class="text-center" style="width:110px">Realisasi</th>
                        <th style="width:300px">Alokasi JP &amp; Catatan</th>
                        <th class="text-end" style="width:70px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atp->items as $item)
                        <tr>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                        @csrf <input type="hidden" name="direction" value="up">
                                        <button class="btn btn-sm btn-soft-secondary" title="Naik"><i class="ri-arrow-up-line"></i></button>
                                    </form>
                                    <span class="fw-semibold">{{ $item->urutan }}</span>
                                    <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                        @csrf <input type="hidden" name="direction" value="down">
                                        <button class="btn btn-sm btn-soft-secondary" title="Turun"><i class="ri-arrow-down-line"></i></button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran?->kode_tp }}</span>
                                <div class="small mt-1">{{ $item->tujuanPembelajaran?->deskripsi }}</div>
                                @if($item->tujuanPembelajaran?->elemen)
                                    <div class="small text-muted"><i class="ri-price-tag-3-line me-1"></i>{{ $item->tujuanPembelajaran->elemen }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @php $realisasi = (int) ($realisasiByTp[$item->tujuan_pembelajaran_id] ?? 0); @endphp
                                @if($realisasi > 0)
                                    <span class="badge bg-success-subtle text-success" title="Jumlah jurnal pertemuan yang mencatat TP ini">{{ $realisasi }}×</span>
                                @else
                                    <span class="text-muted small">Belum</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('user.kurikulum.atp.items.update', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PUT')
                                    <input type="number" name="jp_alokasi" class="form-control form-control-sm" style="width:80px" min="0" max="100" value="{{ $item->jp_alokasi }}" title="Alokasi JP">
                                    <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $item->catatan }}" placeholder="Catatan">
                                    <button class="btn btn-sm btn-soft-success" title="Simpan"><i class="ri-check-line"></i></button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('user.kurikulum.atp.items.destroy', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST"
                                      onsubmit="return confirm('Keluarkan TP ini dari ATP?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Keluarkan"><i class="ri-close-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-list-ordered-2 fs-1 d-block mb-2"></i>
                                    Belum ada TP. Tambahkan dari daftar di bawah.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-add-circle-line text-primary me-1"></i> Tambah TP ke ATP</h5>
        </div>
        <div class="card-body">
            @if($availableTps->isEmpty())
                <p class="text-muted small mb-0">
                    Tidak ada TP tersedia. Buat TP terlebih dahulu di
                    <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'subject_id' => $atp->subject_id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}" class="link-primary">halaman TP</a>.
                </p>
            @else
                <form method="POST" action="{{ route('user.kurikulum.atp.items.store', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-lg-6">
                        <label class="form-label">Tujuan Pembelajaran</label>
                        <select name="tujuan_pembelajaran_id" id="atp-tp-select" class="form-select" required onchange="applyTpJp()">
                            <option value="">-- Pilih TP --</option>
                            @foreach($availableTps as $tp)
                                <option value="{{ $tp->id }}" data-jp="{{ $tp->alokasi_waktu }}">[{{ $tp->kode_tp }}] {{ \Illuminate\Support\Str::limit($tp->deskripsi, 80) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <label class="form-label">JP</label>
                        <input type="number" name="jp_alokasi" id="atp-tp-jp" class="form-control" min="0" max="100" value="2">
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="catatan" class="form-control" maxlength="255">
                    </div>
                    <div class="col-lg-1 col-sm-2">
                        <button class="btn btn-success w-100" title="Tambahkan"><i class="ri-add-line"></i></button>
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
