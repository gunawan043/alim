@extends('layouts.master')
@section('title', 'Input Setoran Harian')
@push('styles')
<style>
    /* ── Setoran harian: form bertahap per santri, bukan tabel lebar ────── */
    .ss-item {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 12px; background: var(--bs-body-bg);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ss-item + .ss-item { margin-top: .75rem; }
    .ss-item.is-filled { border-color: var(--bs-success-border-subtle); }
    .ss-item.is-open { box-shadow: 0 4px 16px rgba(0,0,0,.06); }

    .ss-head {
        display: flex; align-items: center; gap: .75rem;
        padding: .8rem 1rem; cursor: pointer; user-select: none;
    }
    .ss-avatar {
        width: 38px; height: 38px; flex-shrink: 0; border-radius: 50%;
        display: grid; place-items: center; font-weight: 600; font-size: .85rem;
        background: var(--bs-secondary-bg); color: var(--bs-secondary-color);
    }
    .ss-item.is-filled .ss-avatar { background: var(--bs-success-bg-subtle); color: var(--bs-success); }
    .ss-name { font-weight: 600; line-height: 1.25; }
    .ss-meta { font-size: .78rem; color: var(--bs-secondary-color); }
    .js-caret { transition: transform .15s ease; }
    .ss-item.is-open .js-caret { transform: rotate(180deg); }

    .ss-body { display: none; padding: 0 1rem 1rem; }
    .ss-item.is-open .ss-body { display: block; }
    .ss-body::before {
        content: ''; display: block; height: 1px; margin-bottom: 1rem;
        background: var(--bs-border-color-translucent);
    }

    /* Bagian bernomor supaya urutan pengisian jelas */
    .ss-step { display: flex; gap: .75rem; }
    .ss-step + .ss-step { margin-top: 1.1rem; padding-top: 1.1rem; border-top: 1px dashed var(--bs-border-color-translucent); }
    .ss-step-no {
        flex-shrink: 0; width: 24px; height: 24px; border-radius: 50%;
        display: grid; place-items: center; font-size: .72rem; font-weight: 700;
        background: var(--bs-primary-bg-subtle); color: var(--bs-primary);
    }
    .ss-step-body { flex: 1 1 auto; min-width: 0; }
    .ss-step-title { font-size: .85rem; font-weight: 600; margin-bottom: .55rem; }

    .ss-label {
        display: block; font-size: .75rem; font-weight: 600;
        color: var(--bs-secondary-color); margin-bottom: .3rem;
    }

    /* Tiga pilihan jenis: tombol besar, nyaman di ponsel */
    .ss-jenis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .ss-jenis input { position: absolute; opacity: 0; pointer-events: none; }
    .ss-jenis label {
        display: flex; align-items: center; justify-content: center; gap: .35rem;
        padding: .55rem .4rem; border: 1px solid var(--bs-border-color);
        border-radius: 9px; font-size: .84rem; font-weight: 600;
        color: var(--bs-secondary-color); cursor: pointer; margin: 0;
        transition: background .12s ease, color .12s ease, border-color .12s ease;
    }
    .ss-jenis label:hover { background: var(--bs-tertiary-bg); }
    .ss-jenis input:checked + label[data-v="ziyadah"] {
        background: var(--bs-success); border-color: var(--bs-success); color: #fff;
    }
    .ss-jenis input:checked + label[data-v="robet"] {
        background: var(--bs-primary); border-color: var(--bs-primary); color: #fff;
    }
    .ss-jenis input:checked + label[data-v=""] {
        background: var(--bs-secondary-bg); border-color: var(--bs-secondary); color: var(--bs-body-color);
    }

    /* Bagian materi & penilaian hanya berlaku setelah jenis dipilih */
    .ss-steps { display: none; }
    .ss-item.is-filled .ss-steps { display: block; }
    .ss-only-ziyadah, .ss-only-rabt { display: none; }
    .ss-item.ziyadah-mode .ss-only-ziyadah { display: block; }
    .ss-item.rabt-mode .ss-only-rabt { display: block; }
    .ss-item.ziyadah-mode .ss-only-ziyadah.form-check,
    .ss-item.rabt-mode .ss-only-rabt.form-check { display: flex; }

    .ss-page-note {
        font-size: .75rem; color: var(--bs-secondary-color);
        display: flex; align-items: flex-start; gap: .35rem; margin-top: .45rem;
    }

    @media (max-width: 575.98px) {
        .ss-head { padding: .7rem .8rem; gap: .6rem; }
        .ss-body { padding: 0 .8rem .9rem; }
        .ss-step { gap: .6rem; }
    }
</style>
@endpush
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $selectedGroupId = $group?->id;
        $dateValue = $date ?? now()->toDateString();
        $statusLabels = ['lulus' => 'Lulus', 'ulang' => 'Ulang', 'ditunda' => 'Ditunda'];
        $rabtLabels = ['siap' => 'Siap', 'belum_siap' => 'Belum siap', 'perlu_diulang' => 'Perlu diulang'];
        $filledCount = $rows->filter(fn ($row) => $row['ziyadah'] || $row['rabt'])->count();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Input Setoran Harian @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-warning alert-dismissible fade show"><i class="ri-information-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show"><strong>Periksa kembali data setoran.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-file-add-line text-primary me-1"></i>Input Setoran Harian</h5>
                <p class="text-muted mb-0 small">Pilih santri, lalu isi setoran dalam tiga langkah singkat.</p>
            </div>
            <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId, 'group_id' => $selectedGroupId]) }}" class="btn btn-soft-secondary"><i class="ri-history-line me-1"></i>Riwayat Setoran</a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Halaqah</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()" @disabled($groups->isEmpty())>
                        @forelse($groups as $g)
                            <option value="{{ $g->id }}" @selected($selectedGroupId === $g->id)>{{ $g->name }} — {{ $g->school?->name }}</option>
                        @empty
                            <option>Tidak ada halaqah</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tanggal Setoran</label>
                    <input type="date" name="date" value="{{ $dateValue }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-md-4 d-flex justify-content-md-end gap-2">
                    <span class="badge bg-light text-body align-self-center" id="bulkCounter">{{ $filledCount }} dari {{ $rows->count() }} santri terisi</span>
                </div>
            </form>
        </div>

        @if($group)
            {{-- Satu halaman untuk dua pekerjaan harian guru: setoran hafalan
                 dan mutaba'ah ibadah. Jangan dipecah ke menu terpisah. --}}
            <ul class="nav nav-tabs nav-success" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $tab === 'setoran' ? 'active' : '' }}" role="tab"
                       href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id, 'date' => $dateValue, 'tab' => 'setoran']) }}">
                        <i class="ri-book-read-line me-1"></i>Setoran Hafalan
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $tab === 'mutabaah' ? 'active' : '' }}" role="tab"
                       href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id, 'date' => $dateValue, 'tab' => 'mutabaah']) }}">
                        <i class="ri-calendar-check-line me-1"></i>Mutaba’ah Harian
                    </a>
                </li>
            </ul>
        @endif

        @if(! $group)
            <div class="card-body text-center text-muted py-5">
                <i class="ri-team-line fs-2 d-block mb-2"></i>
                Belum ada halaqah yang dapat Anda input. Hubungi koordinator Tahfidz sekolah.
            </div>
        @elseif($rows->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                Belum ada santri aktif di halaqah ini.
            </div>
        @elseif($tab === 'setoran')
            <form method="POST" action="{{ route('user.tahfidz.setoran-bulk.store', ['userId' => $userId]) }}" id="bulkForm">
                @csrf
                <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}">
                <input type="hidden" name="setoran_date" value="{{ $dateValue }}">

                <div class="card-body">
                    @foreach($rows as $index => $row)
                        @php
                            $student = $row['student'];
                            $base = 'entries['.$index.']';
                            $ziyadah = $row['ziyadah'];
                            $rabt = $row['rabt'];
                            $active = $ziyadah ?? $rabt;
                            $initial = mb_strtoupper(mb_substr($student->name, 0, 1));
                            $last = $row['last'];
                        @endphp
                        <div class="ss-item {{ $active ? 'is-filled' : '' }} {{ $rabt ? 'rabt-mode' : ($ziyadah ? 'ziyadah-mode' : '') }}"
                             data-row="{{ $index }}"
                             data-last="{{ json_encode($last, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                            <input type="hidden" name="{{ $base }}[student_id]" value="{{ $student->id }}">

                            <div class="ss-head js-toggle" role="button" tabindex="0"
                                 aria-expanded="{{ $active ? 'true' : 'false' }}">
                                <span class="ss-avatar">{{ $initial }}</span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="ss-name text-truncate">{{ $student->name }}</div>
                                    <div class="ss-meta">
                                        {{ $student->nis }}
                                        <span class="js-summary">
                                            @if($rabt)
                                                · Rabt{{ $rabt->surah_start_name ? ' '.$rabt->surah_start_name : '' }}
                                            @elseif($ziyadah)
                                                · Ziyadah{{ $ziyadah->surah_start_name ? ' '.$ziyadah->surah_start_name : '' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <span class="badge js-state {{ $active ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">
                                    {{ $active ? 'Terisi' : 'Belum' }}
                                </span>
                                <i class="ri-arrow-down-s-line fs-5 text-muted js-caret"></i>
                            </div>

                            <div class="ss-body">
                                {{-- Langkah 1: jenis setoran --}}
                                <div class="ss-step">
                                    <span class="ss-step-no">1</span>
                                    <div class="ss-step-body">
                                        <div class="ss-step-title">Jenis setoran</div>
                                        <div class="ss-jenis" role="group" aria-label="Kegiatan {{ $student->name }}">
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-none" value="" class="js-jenis" @checked(! $active)>
                                            <label for="jenis-{{ $index }}-none" data-v="">—</label>
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-z" value="ziyadah" class="js-jenis" @checked($ziyadah)>
                                            <label for="jenis-{{ $index }}-z" data-v="ziyadah">Ziyadah</label>
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-r" value="robet" class="js-jenis" @checked($rabt)>
                                            <label for="jenis-{{ $index }}-r" data-v="robet">Rabt</label>
                                        </div>
                                        <div class="form-text mt-2">
                                            Ziyadah = hafalan baru. Rabt = mengikat hafalan lama agar tetap terjaga.
                                            Pilih <strong>—</strong> bila santri tidak setor.
                                        </div>
                                    </div>
                                </div>

                                <div class="ss-steps">
                                    {{-- Langkah 2: materi yang disetorkan --}}
                                    <div class="ss-step">
                                        <span class="ss-step-no">2</span>
                                        <div class="ss-step-body">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                                <div class="ss-step-title mb-0">Materi setoran</div>
                                                @if($last)
                                                    <button type="button" class="btn btn-link btn-sm p-0 js-use-last" title="Lanjutkan dari setoran ziyadah terakhir">
                                                        <i class="ri-history-line me-1"></i>Ambil materi terakhir
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12 col-sm-7">
                                                    <label class="ss-label">Surah awal</label>
                                                    <select class="form-select form-select-sm js-surah-start" data-field="surah_start_name">
                                                        <option value="">— Pilih surah —</option>
                                                        @foreach($surahs as $surah)
                                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(($ziyadah?->surah_start_name ?? $rabt?->surah_start_name) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-6 col-sm-2">
                                                    <label class="ss-label">Ayat</label>
                                                    <input type="number" min="1" class="form-control form-control-sm js-ayat-start" data-field="ayat_start" placeholder="Awal" value="{{ $ziyadah?->ayat_start ?? $rabt?->ayat_start }}">
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="ss-label">Juz</label>
                                                    <input type="number" min="1" max="30" class="form-control form-control-sm js-field" data-field="juz" value="{{ $ziyadah?->juz ?? $rabt?->juz }}">
                                                </div>
                                                <div class="col-12 col-sm-7">
                                                    <label class="ss-label">Surah akhir</label>
                                                    <select class="form-select form-select-sm js-surah-end" data-field="surah_end_name">
                                                        <option value="">— Sama dengan surah awal —</option>
                                                        @foreach($surahs as $surah)
                                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(($ziyadah?->surah_end_name ?? $rabt?->surah_end_name) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-6 col-sm-2">
                                                    <label class="ss-label">Ayat</label>
                                                    <input type="number" min="1" class="form-control form-control-sm js-ayat-end" data-field="ayat_end" placeholder="Akhir" value="{{ $ziyadah?->ayat_end ?? $rabt?->ayat_end }}">
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="ss-label">Halaman</label>
                                                    <input type="number" step="1" min="1" max="604" class="form-control form-control-sm js-field" data-field="halaman_start" placeholder="Awal" value="{{ $ziyadah?->halaman_start ?? $rabt?->halaman_start }}">
                                                </div>
                                                <div class="col-6 col-sm-2">
                                                    <label class="ss-label">Baris</label>
                                                    <input type="number" min="1" max="15" class="form-control form-control-sm js-field" data-field="baris_start" placeholder="Awal" value="{{ $ziyadah?->baris_start ?? $rabt?->baris_start }}">
                                                </div>
                                                <div class="col-6 col-sm-2">
                                                    <label class="ss-label">Hal. akhir</label>
                                                    <input type="number" step="1" min="1" max="604" class="form-control form-control-sm js-field" data-field="halaman_end" placeholder="Akhir" value="{{ $ziyadah?->halaman_end ?? $rabt?->halaman_end }}">
                                                </div>
                                                <div class="col-6 col-sm-2">
                                                    <label class="ss-label">Baris akhir</label>
                                                    <input type="number" min="1" max="15" class="form-control form-control-sm js-field" data-field="baris_end" placeholder="Akhir" value="{{ $ziyadah?->baris_end ?? $rabt?->baris_end }}">
                                                </div>
                                                <div class="col-12">
                                                    <div class="ss-page-note">
                                                        <i class="ri-bookmark-line js-page-icon"></i>
                                                        <span class="js-page-badge">Halaman dan baris terisi otomatis dari Mushaf Madinah setelah surah dan ayat dipilih.</span>
                                                    </div>
                                                    <div class="ss-page-note text-warning d-none js-page-hint"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Langkah 3: penilaian --}}
                                    <div class="ss-step">
                                        <span class="ss-step-no">3</span>
                                        <div class="ss-step-body">
                                            <div class="ss-step-title">Penilaian</div>
                                            <div class="row g-2">
                                                <div class="col-6 col-sm-3 ss-only-ziyadah">
                                                    <label class="ss-label">Nilai hafalan</label>
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-field" data-field="hasil_hafalan" placeholder="0–100" value="{{ $ziyadah?->hasil_hafalan }}">
                                                </div>
                                                <div class="col-6 col-sm-3 ss-only-rabt">
                                                    <label class="ss-label">Nilai rabt</label>
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-field" data-field="nilai_rabt" placeholder="0–100" value="{{ $rabt?->nilai_setoran }}">
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="ss-label">Nilai tikrar</label>
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-field" data-field="nilai_tikrar" placeholder="0–100" value="{{ $ziyadah?->nilai_tikrar ?? $rabt?->nilai_tikrar }}">
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <label class="ss-label">Status</label>
                                                    <select class="form-select form-select-sm js-field ss-only-ziyadah" data-field="status">
                                                        @foreach($statusLabels as $value => $label)
                                                            <option value="{{ $value }}" @selected(($ziyadah?->status ?? 'lulus') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select class="form-select form-select-sm js-field ss-only-rabt" data-field="robet_status">
                                                        <option value="">—</option>
                                                        @foreach($rabtLabels as $value => $label)
                                                            <option value="{{ $value }}" @selected(($rabt?->robet_status ?? null) === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-text">Nilai tikrar dipakai bersama oleh Ziyadah dan Rabt.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Langkah 4: catatan --}}
                                    <div class="ss-step">
                                        <span class="ss-step-no">4</span>
                                        <div class="ss-step-body">
                                            <label class="ss-step-title d-block" for="catatan-{{ $index }}">Catatan guru <span class="fw-normal text-muted">(opsional)</span></label>
                                            <input type="text" id="catatan-{{ $index }}" class="form-control form-control-sm js-field" data-field="catatan_guru" maxlength="3000" value="{{ $ziyadah?->catatan_guru ?? $rabt?->catatan_guru }}" placeholder="Misal: perlu pengulangan ayat 5–7">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2 sticky-bottom bg-body border-top">
                    <span class="text-muted small"><i class="ri-information-line me-1"></i>Santri yang dibiarkan “—” tidak disimpan.</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id, 'date' => $dateValue]) }}" class="btn btn-light">Muat Ulang</a>
                        <button class="btn btn-success" type="submit"><i class="ri-save-line me-1"></i>Simpan Setoran</button>
                    </div>
                </div>
            </form>
        @else
            @include('tahfidz.setoran._mutabaah-grid')
        @endif
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('bulkForm');
        if (! form) return;

        const positionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));
        const rows = Array.from(document.querySelectorAll('.ss-item'));
        const counter = document.getElementById('bulkCounter');
        const total = rows.length;

        function currentType(row) {
            const checked = row.querySelector('.js-jenis:checked');
            return checked ? checked.value : '';
        }

        function setType(row, type) {
            row.classList.toggle('is-filled', type !== '');
            row.classList.toggle('rabt-mode', type === 'robet');
            row.classList.toggle('ziyadah-mode', type === 'ziyadah');
            if (type !== '') row.classList.add('is-open');

            const state = row.querySelector('.js-state');
            if (state) {
                state.textContent = type === '' ? 'Belum' : 'Terisi';
                state.className = 'badge js-state ' + (type === '' ? 'bg-light text-muted' : 'bg-success-subtle text-success');
            }
            const head = row.querySelector('.js-toggle');
            if (head) head.setAttribute('aria-expanded', type !== '' ? 'true' : 'false');

            syncRowNames(row);
            updateCounter();
        }

        // Nama field mengikuti jenis kegiatan supaya data terkirim berkelompok
        // per ziyadah / rabt.
        function syncRowNames(row) {
            const index = row.dataset.row;
            const type = currentType(row);
            // Kunci 'rabt' tidak dipakai: di form, Laravel dan jQuery
            // memperlakukannya sebagai entri array. Nama wire memakai 'robet'.
            const prefix = type ? `entries[${index}][${type}]` : null;
            row.querySelectorAll('[data-field]').forEach(function (field) {
                field.disabled = ! prefix;
                if (prefix) {
                    field.name = `${prefix}[${field.dataset.field}]`;
                } else {
                    field.removeAttribute('name');
                }
            });
        }

        function updateCounter() {
            if (! counter) return;
            const filled = rows.filter(function (row) { return currentType(row) !== ''; }).length;
            counter.textContent = filled + ' dari ' + total + ' santri terisi';
        }

        async function fillPosition(row) {
            const startSelect = row.querySelector('.js-surah-start');
            const endSelect = row.querySelector('.js-surah-end');
            const ayatStart = row.querySelector('.js-ayat-start');
            const ayatEnd = row.querySelector('.js-ayat-end');
            const hint = row.querySelector('.js-page-hint');
            const badge = row.querySelector('.js-page-badge');

            const s = startSelect?.selectedOptions?.[0]?.dataset?.number;
            const a = ayatStart?.value;
            if (! s || ! a) return;

            const es = endSelect?.selectedOptions?.[0]?.dataset?.number || s;
            const ea = ayatEnd?.value || a;
            const url = new URL(positionUrl, window.location.origin);
            url.searchParams.set('surah_start', s);
            url.searchParams.set('ayah_start', a);
            url.searchParams.set('surah_end', es);
            url.searchParams.set('ayah_end', ea);

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (! data.found || ! data.position) {
                    if (hint) {
                        hint.textContent = 'Posisi ayat tidak ditemukan, isi halaman dan baris manual.';
                        hint.classList.remove('d-none');
                    }
                    return;
                }
                row.querySelector('[data-field="halaman_start"]').value = data.position.page_start;
                row.querySelector('[data-field="baris_start"]').value = data.position.line_start;
                row.querySelector('[data-field="halaman_end"]').value = data.position.page_end;
                row.querySelector('[data-field="baris_end"]').value = data.position.line_end;
                if (badge) {
                    badge.textContent = `Halaman ${data.position.page_start}–${data.position.page_end} · baris ${data.position.line_start}–${data.position.line_end}`;
                }
                hint?.classList.add('d-none');
            } catch (e) {
                if (hint) {
                    hint.textContent = 'Gagal mengambil posisi ayat, isi halaman dan baris manual.';
                    hint.classList.remove('d-none');
                }
            }
        }

        rows.forEach(function (row) {
            const toggle = function () { row.classList.toggle('is-open'); };
            row.querySelector('.js-toggle')?.addEventListener('click', toggle);
            row.querySelector('.js-toggle')?.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggle();
                }
            });

            row.querySelectorAll('.js-jenis').forEach(function (radio) {
                radio.addEventListener('change', function () { setType(row, this.value); });
            });

            ['change', 'input'].forEach(function (eventName) {
                row.querySelector('.js-surah-start')?.addEventListener(eventName, function () { fillPosition(row); });
                row.querySelector('.js-surah-end')?.addEventListener(eventName, function () { fillPosition(row); });
                row.querySelector('.js-ayat-start')?.addEventListener(eventName, function () { fillPosition(row); });
                row.querySelector('.js-ayat-end')?.addEventListener(eventName, function () { fillPosition(row); });
            });

            row.querySelector('.js-use-last')?.addEventListener('click', function () {
                let last = null;
                try { last = JSON.parse(row.dataset.last || 'null'); } catch (e) { last = null; }
                if (! last) return;

                if (! currentType(row)) {
                    row.querySelector('.js-jenis[value="ziyadah"]').checked = true;
                    setType(row, 'ziyadah');
                }

                if (last.juz) row.querySelector('[data-field="juz"]').value = last.juz;
                if (last.surah_start_name) {
                    row.querySelector('.js-surah-start').value = last.surah_start_name;
                    row.querySelector('.js-surah-end').value = last.surah_start_name;
                }
                if (last.ayat_start) {
                    row.querySelector('.js-ayat-start').value = last.ayat_start;
                    row.querySelector('.js-ayat-end').value = last.ayat_start;
                }
                if (last.halaman_start) {
                    row.querySelector('[data-field="halaman_start"]').value = last.halaman_start;
                }
                fillPosition(row);
            });

            setType(row, currentType(row));
        });

        updateCounter();
    });
</script>
@endpush
