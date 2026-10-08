@extends('layouts.master')
@section('title', 'PROSEM')

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $monthLabel = function ($date) {
            return $date ? \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('F Y') : null;
        };

        $jenisLabels = \App\Models\PekanEfektif::JENIS_OPTIONS;

        $weekGroupsJson = $weekGroups->map(function ($group) {
            return [
                'label' => $group['label'],
                'weeks' => collect($group['weeks'])->map(fn ($w) => [
                    'pekan_ke' => (int) $w->minggu_ke,
                    'tanggal' => ($w->tanggal_mulai?->format('j') ?? '').'–'.($w->tanggal_selesai?->format('j M') ?? ''),
                    'efektif' => (int) $w->hari_efektif > 0,
                    'jenis_label' => \App\Models\PekanEfektif::JENIS_OPTIONS[$w->jenis] ?? $w->jenis,
                ])->values(),
            ];
        })->values();

        $statusMeta = [
            'otomatis' => ['label' => 'Otomatis', 'class' => 'bg-secondary-subtle text-secondary', 'icon' => 'ri-magic-line'],
            'disesuaikan' => ['label' => 'Disesuaikan', 'class' => 'bg-primary-subtle text-primary', 'icon' => 'ri-equalizer-line'],
            'tidak_valid' => ['label' => 'Tidak Valid', 'class' => 'bg-danger-subtle text-danger', 'icon' => 'ri-error-warning-line'],
            'perlu_diperbarui' => ['label' => 'Perlu Diperbarui', 'class' => 'bg-warning-subtle text-warning', 'icon' => 'ri-refresh-line'],
        ];
        $status = $statusMeta[$headerStatus] ?? $statusMeta['otomatis'];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}">PROSEM</a> @endslot
        @slot('title') {{ $prosem->subject?->name ?? 'PROSEM' }} @endslot
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

    {{-- ═══ Header: PROGRAM SEMESTER ═══ --}}
    <div class="card border-primary-subtle mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 text-uppercase">Program Semester</h4>
                    <div class="text-muted small">
                        {{ $prosem->school?->name ?? 'Satuan Pendidikan' }}
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small">
                        <div><span class="text-muted">Mata Pelajaran:</span> <strong>{{ $prosem->subject?->name ?? '-' }}</strong></div>
                        <div><span class="text-muted">Kelas/Fase:</span> <strong>{{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }}{{ $prosem->gradeLevel?->fase ? ' / '.$prosem->gradeLevel->fase : '' }}</strong></div>
                        <div><span class="text-muted">Semester:</span> <strong>{{ ucfirst($prosem->semester) }}</strong></div>
                        <div><span class="text-muted">Tahun Ajaran:</span> <strong>{{ $prosem->academicYear?->name ?? '-' }}</strong></div>
                        <div><span class="text-muted">Guru/Penyusun:</span> <strong>{{ $prosem->teacher?->name ?? '—' }}</strong></div>
                    </div>
                    <div class="mt-2">
                        <span class="badge {{ $status['class'] }} p-2">
                            <i class="{{ $status['icon'] }} me-1"></i>{{ $status['label'] }}
                        </span>
                        @if($prosem->adjusted_at)
                            <span class="text-muted small ms-1">Terakhir disesuaikan {{ $prosem->adjusted_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($canEdit)
                        <form method="POST" action="{{ route('user.kurikulum.prosem.sync', ['userId' => $userId, 'id' => $prosem->id]) }}"
                              onsubmit="return confirm('Sinkronkan PROSEM dengan PROTA & Pekan Efektif?\nDistribusi otomatis akan diperbarui; penyesuaian manual dipertahankan.');">
                            @csrf
                            <button class="btn btn-soft-warning btn-sm"><i class="ri-refresh-line align-bottom me-1"></i> Sinkron dari PROTA</button>
                        </form>
                        @if($prosem->items->isNotEmpty())
                            <button type="button" class="btn btn-primary btn-sm" onclick="openFirstAdjust()">
                                <i class="ri-equalizer-line align-bottom me-1"></i> Atur Distribusi
                            </button>
                        @endif
                    @endif
                    <a href="{{ route('user.kurikulum.cetak.prosem', ['userId' => $userId, 'id' => $prosem->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank">
                        <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Alert perubahan sumber ═══ --}}
    @if($stale['stale'] || $manualInvalidCount > 0)
        <div class="alert alert-warning" role="alert">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h6 class="alert-heading mb-1"><i class="ri-refresh-line me-1"></i> Perencanaan perlu diperbarui</h6>
                    <p class="mb-1 small">Pekan Efektif atau sumber perencanaan berubah sejak PROSEM terakhir disinkronkan.</p>
                    @if($manualInvalidCount > 0)
                        <p class="mb-1 small text-danger">
                            <i class="ri-error-warning-line me-1"></i>{{ $manualInvalidCount }} penyesuaian manual terdampak perubahan kalender — tinjau dan atur ulang.
                        </p>
                    @endif
                    @if(! empty($stale['reasons']))
                        <ul class="mb-0 small ps-3">
                            @foreach($stale['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="#tabel-distribusi" class="btn btn-sm btn-outline-warning"><i class="ri-search-eye-line me-1"></i> Tinjau Perubahan</a>
                    @if($canEdit)
                        <form method="POST" action="{{ route('user.kurikulum.prosem.sync', ['userId' => $userId, 'id' => $prosem->id]) }}">
                            @csrf
                            <button class="btn btn-sm btn-warning"><i class="ri-refresh-line me-1"></i> Sinkronkan</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ Summary ═══ --}}
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Pekan Efektif</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-success">{{ $summary['pekan_efektif'] }} <span class="fs-13 text-muted">Pekan</span></h4>
                        <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-calendar-check-line text-success"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">JP Tersedia</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-primary">{{ $summary['jp_tersedia'] }} <span class="fs-13 text-muted">JP</span></h4>
                        <div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-scales-3-line text-primary"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">JP Terencana</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 {{ $summary['jp_terencana'] == $summary['jp_tersedia'] ? 'text-success' : 'text-warning' }}">
                            {{ $summary['jp_terencana'] }} <span class="fs-13 text-muted">JP</span>
                        </h4>
                        <div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-list-ordered-2 text-warning"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Status</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <span class="badge {{ $status['class'] }} p-2 fs-13"><i class="{{ $status['icon'] }} me-1"></i>{{ $status['label'] }}</span>
                        <div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="ri-information-line text-info"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Pengaturan PROSEM ═══ --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i> Pengaturan PROSEM</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.kurikulum.prosem.update', ['userId' => $userId, 'id' => $prosem->id]) }}" class="row g-3 align-items-end">
                @csrf @method('PUT')
                <div class="col-lg-4">
                    <label class="form-label">Guru Pengampu</label>
                    <select name="teacher_id" class="form-select" {{ $canEdit ? '' : 'disabled' }}>
                        <option value="">—</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $prosem->teacher_id === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" {{ $canEdit ? '' : 'disabled' }}>
                        <option value="draft" {{ $prosem->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="final" {{ $prosem->status === 'final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="catatan" class="form-control" value="{{ $prosem->catatan }}" maxlength="2000" {{ $canEdit ? '' : 'disabled' }}>
                </div>
                @if($canEdit)
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-success w-100"><i class="ri-save-line align-bottom me-1"></i> Simpan</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ Tabel Distribusi ═══ --}}
    <div class="card" id="tabel-distribusi">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Distribusi TP / Materi</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $prosem->items->count() }} baris · {{ $totalJp }} JP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:50px">No</th>
                        <th>CP / TP / Materi</th>
                        <th class="text-center" style="width:70px">JP</th>
                        <th style="width:280px">Distribusi</th>
                        <th class="text-center" style="width:120px">Realisasi</th>
                        <th class="text-center" style="width:120px">Status</th>
                        <th class="text-end" style="width:170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prosem->items as $item)
                        @php
                            $state = $itemStates[$item->id] ?? ['sum' => 0, 'valid' => false, 'invalid_weeks' => [], 'sumber' => $item->sumber];
                            $startWeek = $weeks[$item->mulai_minggu_ke] ?? null;
                            $endWeek = $weeks[$item->selesai_minggu_ke] ?? null;
                            $startMonth = $monthLabel($startWeek?->tanggal_mulai);
                            $endMonth = $monthLabel($endWeek?->tanggal_selesai);
                            $bulan = $startMonth ? ($endMonth && $endMonth !== $startMonth ? $startMonth.' – '.$endMonth : $startMonth) : '—';
                            $pekan = $item->mulai_minggu_ke > 0
                                ? 'Pekan '.$item->mulai_minggu_ke.($item->selesai_minggu_ke > $item->mulai_minggu_ke ? '–'.$item->selesai_minggu_ke : '')
                                : '—';
                            $rc = $realisasiCounts[$item->id] ?? null;

                            if ($item->isManual() && ! $state['valid'] && $state['invalid_weeks'] !== []) {
                                $itemStatus = $statusMeta['tidak_valid'];
                            } elseif ($item->isManual()) {
                                $itemStatus = $statusMeta['disesuaikan'];
                            } elseif (! $state['valid'] && $item->weeks->isNotEmpty()) {
                                $itemStatus = $statusMeta['perlu_diperbarui'];
                            } else {
                                $itemStatus = $statusMeta['otomatis'];
                            }

                            $itemJson = json_encode([
                                'id' => $item->id,
                                'kode' => $item->tujuanPembelajaran?->kode_tp,
                                'materi' => $item->tujuanPembelajaran?->deskripsi ?? $item->protaItem?->materi,
                                'bab' => $item->protaItem?->bab,
                                'jp' => (int) $item->jp,
                                'sumber' => $item->sumber,
                                'weeks' => (object) $item->weeks->mapWithKeys(fn ($w) => [(string) $w->pekan_ke => (int) $w->jp])->all(),
                                'invalid_weeks' => $state['invalid_weeks'],
                            ]);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $item->urutan }}</td>
                            <td>
                                @if($item->protaItem?->bab)
                                    <div class="small text-muted text-uppercase">{{ $item->protaItem->bab }}</div>
                                @endif
                                @if($item->tujuanPembelajaran)
                                    <span class="badge bg-primary-subtle text-primary">{{ $item->tujuanPembelajaran->kode_tp }}</span>
                                @endif
                                <div class="small mt-1">{{ $item->tujuanPembelajaran?->deskripsi ?? $item->protaItem?->materi ?? '—' }}</div>
                            </td>
                            <td class="text-center fw-semibold">{{ $item->jp }}</td>
                            <td class="small">
                                <div>{{ $pekan }} · {{ $bulan }}</div>
                                @if($item->isManual() && $item->weeks->isNotEmpty())
                                    <div class="text-muted" style="font-size:.7rem">
                                        @foreach($item->weeks as $w)
                                            Pekan {{ $w->pekan_ke }}: {{ $w->jp }} JP{{ ! $loop->last ? ' · ' : '' }}
                                        @endforeach
                                    </div>
                                @endif
                                @if($state['invalid_weeks'] !== [])
                                    <div class="text-danger" style="font-size:.7rem">
                                        <i class="ri-error-warning-line me-1"></i>Pekan {{ implode(', ', $state['invalid_weeks']) }} tidak lagi tersedia (libur berdasarkan Kaldik)
                                    </div>
                                @endif
                                @if($item->keterangan)
                                    <div class="text-muted" style="font-size:.7rem">{{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td class="text-center small">
                                @if($rc)
                                    <span class="badge bg-success-subtle text-success">{{ $rc->total }} pertemuan</span>
                                    <div class="text-muted" style="font-size:.7rem">{{ $rc->books }} kelas</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $itemStatus['class'] }}">
                                    <i class="{{ $itemStatus['icon'] }} me-1"></i>{{ $itemStatus['label'] }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($canEdit)
                                    <button type="button" class="btn btn-sm btn-soft-primary btn-adjust"
                                            data-item="{{ $itemJson }}" onclick="openAdjust(this)"
                                            title="{{ $itemStatus['label'] === 'Tidak Valid' ? 'Atur Ulang' : 'Atur' }}">
                                        <i class="ri-equalizer-line"></i> {{ $itemStatus['label'] === 'Tidak Valid' ? 'Atur Ulang' : 'Atur' }}
                                    </button>
                                    @if($item->isManual())
                                        <form method="POST" action="{{ route('user.kurikulum.prosem.items.reset', ['userId' => $userId, 'id' => $prosem->id, 'itemId' => $item->id]) }}"
                                              class="d-inline" onsubmit="return confirm('Kembalikan distribusi TP ini ke otomatis?');">
                                            @csrf
                                            <button class="btn btn-sm btn-soft-secondary" title="Kembalikan ke otomatis"><i class="ri-arrow-go-back-line"></i></button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-calendar-2-line fs-1 d-block mb-2"></i>
                                    Belum ada distribusi. Klik <strong>Sinkron dari PROTA</strong> untuk menghitung dari Pekan Efektif.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canEdit)
        <form method="POST" action="{{ route('user.kurikulum.prosem.destroy', ['userId' => $userId, 'id' => $prosem->id]) }}"
              onsubmit="return confirm('Hapus PROSEM ini?');" class="mb-4">
            @csrf @method('DELETE')
            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line align-bottom me-1"></i> Hapus PROSEM</button>
        </form>
    @endif

    {{-- ═══ Modal Atur Distribusi ═══ --}}
    <div class="modal fade zoomIn" id="adjust-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="adjust-form" action="">
                    @csrf @method('PUT')
                    <div id="adjust-hidden-inputs"></div>

                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-equalizer-line me-1"></i> Atur Distribusi Pembelajaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Identitas TP --}}
                        <div class="border rounded p-3 mb-3 bg-light-subtle">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold" id="adjust-materi">—</div>
                                    <div class="small text-muted">
                                        <span id="adjust-kode" class="badge bg-primary-subtle text-primary me-1">—</span>
                                        <span id="adjust-bab"></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted">Total Alokasi</div>
                                    <div class="fw-semibold"><span id="adjust-jp-total">0</span> JP</div>
                                </div>
                            </div>
                        </div>

                        {{-- Validasi realtime --}}
                        <div class="border rounded p-3 mb-3" id="adjust-totals-box">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Terdistribusi</span>
                                <span class="fw-semibold"><span id="adjust-jp-sum">0</span> / <span id="adjust-jp-target">0</span> JP</span>
                            </div>
                            <div id="adjust-warning" class="small mt-2 text-muted">—</div>
                        </div>

                        {{-- Baris distribusi --}}
                        <label class="form-label fw-semibold small text-uppercase text-muted">Pekan &amp; Alokasi JP</label>
                        <div id="adjust-rows"></div>
                        <button type="button" class="btn btn-sm btn-soft-primary mt-1" onclick="addRow()">
                            <i class="ri-add-line me-1"></i> Tambah Pekan
                        </button>

                        {{-- Referensi pekan per bulan (dari Pekan Efektif — bukan kalender kedua) --}}
                        <div class="mt-4">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Ketersediaan Pekan</label>
                            <div class="border rounded p-2" style="max-height:220px; overflow-y:auto;">
                                @foreach($weekGroups as $group)
                                    <div class="small fw-semibold text-muted mt-2 mb-1 text-uppercase">{{ $group['label'] }}</div>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($group['weeks'] as $w)
                                            @php
                                                $efektif = (int) $w->hari_efektif > 0;
                                                $badgeClass = ! $efektif ? 'bg-secondary-subtle text-secondary' : ($w->jenis === 'ujian' ? 'bg-warning-subtle text-warning' : ($w->jenis === 'kegiatan_sekolah' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success'));
                                            @endphp
                                            <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom pb-1">
                                                <span class="small">
                                                    <strong>Pekan {{ $w->minggu_ke }}</strong> ·
                                                    {{ $w->tanggal_mulai?->format('j') }}–{{ $w->tanggal_selesai?->format('j M') }}
                                                </span>
                                                <span>
                                                    @if($efektif)
                                                        <span class="badge {{ $badgeClass }}"><i class="ri-check-line me-1"></i>{{ $jenisLabels[$w->jenis] ?? 'Efektif' }}</span>
                                                    @else
                                                        <span class="badge {{ $badgeClass }}"><i class="ri-lock-line me-1"></i>Libur — tidak dapat dipilih</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div id="adjust-confirm" class="alert alert-warning small w-100 mb-0 d-none">
                            <strong>Simpan Penyesuaian?</strong><br>
                            Distribusi otomatis akan digantikan oleh distribusi manual untuk TP ini.
                        </div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btn-save-adjust" class="btn btn-success" onclick="askConfirm()" disabled>
                            <i class="ri-save-line align-bottom me-1"></i> Simpan Penyesuaian
                        </button>
                        <button type="button" id="btn-confirm-adjust" class="btn btn-success d-none" onclick="submitAdjust()">
                            <i class="ri-check-line align-bottom me-1"></i> Ya, Simpan
                        </button>
                        <button type="button" id="btn-back-adjust" class="btn btn-light d-none" onclick="backToEdit()">Kembali</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var WEEK_GROUPS = @json($weekGroupsJson);
        var ADJUST_URL = @json($adjustUrlTemplate);
        var currentItem = null;

        function weekOptions(selectedPekan) {
            var html = '<option value="">— Pilih Pekan —</option>';
            var found = false;

            WEEK_GROUPS.forEach(function (group) {
                html += '<optgroup label="' + group.label + '">';
                group.weeks.forEach(function (w) {
                    var label = 'Pekan ' + w.pekan_ke + ' · ' + w.tanggal + (w.efektif ? '' : ' — Libur');
                    var selected = String(w.pekan_ke) === String(selectedPekan);
                    if (selected) { found = true; }
                    html += '<option value="' + w.pekan_ke + '"' + (w.efektif ? '' : ' disabled') + (selected ? ' selected' : '') + '>' + label + '</option>';
                });
                html += '</optgroup>';
            });

            // Pekan yang sudah tidak ada di Pekan Efektif (mis. kalender berubah).
            if (selectedPekan && ! found) {
                html = '<option value="' + selectedPekan + '" selected>Pekan ' + selectedPekan + ' · Tidak lagi tersedia</option>' + html;
            }

            return html;
        }

        function rowHtml(pekan, jp) {
            var div = document.createElement('div');
            div.className = 'row g-2 align-items-center adjust-row mb-2';
            div.innerHTML =
                '<div class="col-12 col-md-7">' +
                    '<select class="form-select form-select-sm adjust-week" onchange="recalc()">' + weekOptions(pekan) + '</select>' +
                '</div>' +
                '<div class="col-8 col-md-3">' +
                    '<input type="number" class="form-control form-control-sm adjust-jp" min="1" max="200" value="' + (jp || 1) + '" onchange="recalc()" oninput="recalc()">' +
                '</div>' +
                '<div class="col-4 col-md-2 text-end">' +
                    '<button type="button" class="btn btn-sm btn-soft-danger" onclick="removeRow(this)" title="Hapus baris"><i class="ri-close-line"></i></button>' +
                '</div>';
            return div;
        }

        function renderRows(weeks) {
            var container = document.getElementById('adjust-rows');
            container.innerHTML = '';

            var entries = Object.entries(weeks || {}).sort(function (a, b) { return Number(a[0]) - Number(b[0]); });
            if (entries.length === 0) {
                container.appendChild(rowHtml('', 1));
            } else {
                entries.forEach(function (entry) { container.appendChild(rowHtml(entry[0], entry[1])); });
            }
            recalc();
        }

        function addRow() {
            document.getElementById('adjust-rows').appendChild(rowHtml('', 1));
            recalc();
        }

        function removeRow(btn) {
            var rows = document.querySelectorAll('#adjust-rows .adjust-row');
            if (rows.length <= 1) return;
            btn.closest('.adjust-row').remove();
            recalc();
        }

        function collectRows() {
            var rows = [];
            document.querySelectorAll('#adjust-rows .adjust-row').forEach(function (row) {
                var select = row.querySelector('.adjust-week');
                var jp = row.querySelector('.adjust-jp');
                if (select && select.value) {
                    rows.push({ pekan: Number(select.value), jp: Number(jp.value || 0) });
                }
            });
            return rows;
        }

        function recalc() {
            if (! currentItem) return;

            var rows = collectRows();
            var sum = rows.reduce(function (total, r) { return total + r.jp; }, 0);
            var target = Number(currentItem.jp);
            var seen = {};
            var duplicate = false;
            var invalidCount = 0;

            rows.forEach(function (r) {
                if (seen[r.pekan]) { duplicate = true; }
                seen[r.pekan] = true;
                if ((currentItem.invalid_weeks || []).indexOf(r.pekan) !== -1) { invalidCount++; }
            });

            document.getElementById('adjust-jp-sum').textContent = sum;
            document.getElementById('adjust-jp-target').textContent = target;

            var warning = document.getElementById('adjust-warning');
            var valid = true;

            if (duplicate) {
                warning.className = 'small mt-2 text-danger';
                warning.innerHTML = '<i class="ri-error-warning-line me-1"></i>Ada pekan yang dipilih lebih dari satu kali.';
                valid = false;
            } else if (invalidCount > 0) {
                warning.className = 'small mt-2 text-danger';
                warning.innerHTML = '<i class="ri-error-warning-line me-1"></i>' + invalidCount + ' pekan tidak lagi tersedia (libur berdasarkan Kaldik). Hapus atau ganti pekan tersebut.';
                valid = false;
            } else if (sum < target) {
                warning.className = 'small mt-2 text-warning';
                warning.innerHTML = '<i class="ri-error-warning-line me-1"></i>Masih kurang ' + (target - sum) + ' JP.';
                valid = false;
            } else if (sum > target) {
                warning.className = 'small mt-2 text-danger';
                warning.innerHTML = '<i class="ri-error-warning-line me-1"></i>Melebihi alokasi ' + (sum - target) + ' JP.';
                valid = false;
            } else {
                warning.className = 'small mt-2 text-success';
                warning.innerHTML = '<i class="ri-check-double-line me-1"></i>Total sesuai — ' + sum + ' / ' + target + ' JP.';
            }

            document.getElementById('btn-save-adjust').disabled = ! valid;
        }

        function openAdjust(btn) {
            currentItem = JSON.parse(btn.dataset.item);

            document.getElementById('adjust-materi').textContent = currentItem.materi || '—';
            document.getElementById('adjust-kode').textContent = currentItem.kode || 'TP';
            document.getElementById('adjust-bab').textContent = currentItem.bab ? '· ' + currentItem.bab : '';
            document.getElementById('adjust-jp-total').textContent = currentItem.jp;

            document.getElementById('adjust-form').action = ADJUST_URL.replace('__ITEM__', currentItem.id);

            backToEdit();
            renderRows(currentItem.weeks);

            new bootstrap.Modal(document.getElementById('adjust-modal')).show();
        }

        function openFirstAdjust() {
            var btn = document.querySelector('.btn-adjust');
            if (btn) { btn.click(); }
        }

        function askConfirm() {
            document.getElementById('adjust-confirm').classList.remove('d-none');
            document.getElementById('btn-save-adjust').classList.add('d-none');
            document.getElementById('btn-confirm-adjust').classList.remove('d-none');
            document.getElementById('btn-back-adjust').classList.remove('d-none');
        }

        function backToEdit() {
            document.getElementById('adjust-confirm').classList.add('d-none');
            document.getElementById('btn-save-adjust').classList.remove('d-none');
            document.getElementById('btn-confirm-adjust').classList.add('d-none');
            document.getElementById('btn-back-adjust').classList.add('d-none');
        }

        function submitAdjust() {
            var container = document.getElementById('adjust-hidden-inputs');
            container.innerHTML = '';

            collectRows().forEach(function (r) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'weeks[' + r.pekan + ']';
                input.value = r.jp;
                container.appendChild(input);
            });

            document.getElementById('adjust-form').submit();
        }
    </script>
@endsection
