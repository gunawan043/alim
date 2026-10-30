@extends('layouts.master')
@section('title', 'Detail ATP')

@section('css')
    @include('kurikulum._styles')
@endsection

@php
    $userId = $userId ?? auth()->id();
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') {{ $atp->subject?->name ?? 'ATP' }} @endslot
    @endcomponent

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">
                        {{ $atp->subject?->name ?? 'Mapel' }}
                        @if($atp->fase)
                            <span class="badge bg-info-subtle text-info ms-1">{{ $atp->fase }}</span>
                        @endif
                    </h5>
                    <p class="text-muted mb-0 small">
                        {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                        · {{ $atp->academicYear?->name }}
                        · Semester {{ ucfirst($atp->semester) }}
                    </p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.kurikulum.cetak.atp', ['userId' => $userId, 'id' => $atp->id]) }}"
                           class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="ri-printer-line me-1"></i> Cetak PDF
                        </a>
                        <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'atp_id' => $atp->id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="ri-booklet-line me-1"></i> Perangkat ({{ $perangkatCount }})
                        </a>
                        <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ROW: PENGATURAN ATP + KESESUAIAN ALOKASI JP --}}
    <div class="row mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i>Pengaturan ATP</h5>
                        </div>
                        <div class="col-sm-auto">
                            <span class="badge {{ $atp->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} px-3 py-2">
                                {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$atp->status] ?? $atp->status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kurikulum.atp.update', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Guru Penyusun</label>
                            <select name="teacher_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ $atp->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="draft" {{ $atp->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $atp->status === 'published' ? 'selected' : '' }}>Terbit</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $atp->catatan }}" maxlength="2000">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-3-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-scales-3-line text-primary me-1"></i>Kesesuaian Alokasi JP</h5>
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

    {{-- CARD: SUSUNAN TUJUAN PEMBELAJARAN --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-list-ordered-2 text-primary me-1"></i>Susunan Tujuan Pembelajaran</h5>
                    <p class="text-muted mb-0 small">Urutkan dan atur alokasi JP per TP.</p>
                </div>
                <div class="col-sm-auto">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $atp->items->count() }} TP</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:130px;">Urutan</th>
                            <th>TP</th>
                            <th class="text-center" style="width:100px;">Realisasi</th>
                            <th style="width:340px;">Alokasi JP &amp; Catatan</th>
                            <th class="text-center" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atp->items as $item)
                            <tr>
                                {{-- URUTAN --}}
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Naik">
                                                <i class="ri-arrow-up-line"></i>
                                            </button>
                                        </form>
                                        <span class="fw-semibold">{{ $item->urutan }}</span>
                                        <form action="{{ route('user.kurikulum.atp.items.move', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Turun">
                                                <i class="ri-arrow-down-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- TP --}}
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran?->kode_tp }}</span>
                                    <div class="small mt-1">{{ $item->tujuanPembelajaran?->deskripsi }}</div>
                                    @if($item->tujuanPembelajaran?->elemen)
                                        <div class="small text-muted">
                                            <i class="ri-price-tag-3-line me-1"></i>{{ $item->tujuanPembelajaran->elemen }}
                                        </div>
                                    @endif
                                </td>

                                {{-- REALISASI --}}
                                <td class="text-center">
                                    @php $realisasi = (int) ($realisasiByTp[$item->tujuan_pembelajaran_id] ?? 0); @endphp
                                    @if($realisasi > 0)
                                        <span class="badge bg-success-subtle text-success" title="Jumlah jurnal pertemuan yang mencatat TP ini">{{ $realisasi }}×</span>
                                    @else
                                        <span class="text-muted small">Belum</span>
                                    @endif
                                </td>

                                {{-- ALOKASI JP & CATATAN (tanpa tombol) --}}
                                <td>
                                    <form id="atp-item-form-{{ $item->id }}"
                                          action="{{ route('user.kurikulum.atp.items.update', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}"
                                          method="POST"
                                          class="d-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="jp_alokasi" class="form-control form-control-sm flex-shrink-0"
                                               style="width:80px;" min="0" max="100" value="{{ $item->jp_alokasi }}"
                                               title="Alokasi JP" required>
                                        <input type="text" name="catatan" class="form-control form-control-sm flex-grow-1"
                                               value="{{ $item->catatan }}" placeholder="Catatan">
                                    </form>
                                </td>

                                {{-- AKSI: centang + silang --}}
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <button type="submit" form="atp-item-form-{{ $item->id }}"
                                                class="btn btn-sm btn-outline-success" title="Simpan">
                                            <i class="ri-check-line"></i>
                                        </button>
                                        <form action="{{ route('user.kurikulum.atp.items.destroy', ['userId' => $userId, 'id' => $atp->id, 'itemId' => $item->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('Keluarkan TP ini dari ATP?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Keluarkan">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
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
    </div>

    {{-- CARD: TAMBAH TP --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-add-circle-line text-primary me-1"></i>Tambah TP ke ATP</h5>
        </div>
        <div class="card-body">
            @if($availableTps->isEmpty())
                <p class="text-muted small mb-0">
                    Tidak ada TP tersedia. Buat TP terlebih dahulu di
                    <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'subject_id' => $atp->subject_id, 'academic_year_id' => $atp->academic_year_id, 'semester' => $atp->semester]) }}" class="link-primary">halaman TP</a>.
                </p>
            @else
                <form method="POST" action="{{ route('user.kurikulum.atp.items.store', ['userId' => $userId, 'id' => $atp->id]) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-lg-6">
                        <label class="form-label small mb-1">Tujuan Pembelajaran</label>
                        <select name="tujuan_pembelajaran_id" id="atp-tp-select" class="form-select form-select-sm" data-searchable required onchange="applyTpJp()">
                            <option value="">— Pilih TP —</option>
                            @foreach($availableTps as $tp)
                                <option value="{{ $tp->id }}" data-jp="{{ $tp->alokasi_waktu }}">[{{ $tp->kode_tp }}] {{ \Illuminate\Support\Str::limit($tp->deskripsi, 80) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <label class="form-label small mb-1">JP</label>
                        <input type="number" name="jp_alokasi" id="atp-tp-jp" class="form-control form-control-sm" min="0" max="100" value="2">
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <label class="form-label small mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255">
                    </div>
                    <div class="col-lg-1 col-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100" title="Tambahkan">
                            <i class="ri-add-line"></i>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
function applyTpJp() {
    var select = document.getElementById('atp-tp-select');
    if (!select) return;
    var opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.jp) {
        document.getElementById('atp-tp-jp').value = opt.dataset.jp;
    }
}
</script>
@endpush