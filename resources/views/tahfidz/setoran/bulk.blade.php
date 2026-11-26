@extends('layouts.master')
@section('title', 'Input Setoran Harian')
@push('styles')
<style>
    .bulk-table td, .bulk-table th { vertical-align: middle; }
    .bulk-table { min-width: 1080px; }
    .bulk-row.is-empty { background: #fff; }
    .bulk-row.is-filled { background: var(--bs-success-bg-subtle); }
    .bulk-type { min-width: 110px; }
    .bulk-num { width: 76px; }
    .bulk-page { width: 88px; }
    .bulk-segment { display: inline-flex; border: 1px solid var(--bs-border-color); border-radius: .375rem; overflow: hidden; }
    .bulk-segment input { position: absolute; opacity: 0; pointer-events: none; }
    .bulk-segment label { padding: .2rem .55rem; font-size: .75rem; font-weight: 600; cursor: pointer; margin: 0; color: var(--bs-secondary-color); }
    .bulk-segment input:checked + label { background: var(--bs-primary); color: #fff; }
    .bulk-segment label.seg-rabt { border-left: 1px solid var(--bs-border-color); }
    .bulk-sub { font-size: .7rem; letter-spacing: .02em; }
    .bulk-extra { display: none; }
    .bulk-row.is-filled .bulk-extra { display: table-cell; }
    /* Kolom yang tidak relevan dengan jenis terpilih disembunyikan. */
    .rabt-mode .only-ziyadah { display: none; }
    .ziyadah-mode .only-rabt { display: none; }
    .bulk-hint { font-size: .7rem; color: var(--bs-secondary-color); }
</style>
@endpush
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $selectedGroupId = $group?->id;
        $dateValue = $date ?? now()->toDateString();
        $statusLabels = ['lulus' => 'Lulus', 'ulang' => 'Ulang', 'ditunda' => 'Ditunda'];
        $rabtLabels = ['siap' => 'Siap', 'belum_siap' => 'Belum siap', 'perlu_diulang' => 'Perlu diulang'];
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
                <p class="text-muted mb-0 small">Satu layar untuk seluruh halaqah. Pilih <strong>Ziyadah</strong> (hafalan baru) atau <strong>Rabt</strong> (mengikat hafalan lama) di setiap baris. Kosongkan baris bila santri tidak setor.</p>
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
                    <span class="badge bg-light text-muted align-self-center" id="bulkCounter">0 santri terisi</span>
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
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 bulk-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:44px" class="text-center">#</th>
                                <th>Santri</th>
                                <th class="text-center">Kegiatan</th>
                                <th>Surah &amp; Ayat</th>
                                <th class="text-center">Juz</th>
                                <th class="text-center">Halaman</th>
                                <th class="text-center">Baris</th>
                                <th class="text-center only-ziyadah">Nilai Hafalan</th>
                                <th class="text-center only-rabt">Nilai Rabt</th>
                                <th class="text-center">Nilai Tikrar</th>
                                <th class="text-center">Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                @php
                                    $student = $row['student'];
                                    $base = 'entries['.$index.']';
                                    $ziyadah = $row['ziyadah'];
                                    $rabt = $row['rabt'];
                                @endphp
                                <tr class="bulk-row {{ $ziyadah || $rabt ? 'is-filled' : '' }} {{ $rabt ? 'rabt-mode' : ($ziyadah ? 'ziyadah-mode' : '') }}"
                                    data-row="{{ $index }}"
                                    data-last="{{ json_encode($row['last'], JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="{{ $base }}[student_id]" value="{{ $student->id }}">
                                        <div class="fw-medium">{{ $student->name }}</div>
                                        <div class="text-muted small">{{ $student->nis }}</div>
                                        @if($row['last'])
                                            <button type="button" class="btn btn-link btn-sm p-0 bulk-sub only-ziyadah js-use-last" title="Lanjutkan dari setoran ziyadah terakhir">Ambil materi terakhir</button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="bulk-segment" role="group" aria-label="Kegiatan {{ $student->name }}">
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-none" value="" class="js-jenis" @checked(! $ziyadah && ! $rabt)>
                                            <label for="jenis-{{ $index }}-none">—</label>
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-z" value="ziyadah" class="js-jenis" @checked($ziyadah)>
                                            <label for="jenis-{{ $index }}-z">Ziyadah</label>
                                            <input type="radio" name="{{ $base }}[jenis]" id="jenis-{{ $index }}-r" value="robet" class="js-jenis" @checked($rabt)>
                                            <label for="jenis-{{ $index }}-r" class="seg-rabt">Rabt</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 mb-1">
                                            <select class="form-select form-select-sm js-surah-start" data-field="surah_start_name">
                                                <option value="">— Surah awal —</option>
                                                @foreach($surahs as $surah)
                                                    <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(($ziyadah?->surah_start_name ?? $rabt?->surah_start_name) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" min="1" class="form-control form-control-sm bulk-num js-ayat-start" data-field="ayat_start" placeholder="Ayat" value="{{ $ziyadah?->ayat_start ?? $rabt?->ayat_start }}">
                                        </div>
                                        <div class="d-flex gap-1">
                                            <select class="form-select form-select-sm js-surah-end" data-field="surah_end_name">
                                                <option value="">— Surah akhir —</option>
                                                @foreach($surahs as $surah)
                                                    <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(($ziyadah?->surah_end_name ?? $rabt?->surah_end_name) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" min="1" class="form-control form-control-sm bulk-num js-ayat-end" data-field="ayat_end" placeholder="Ayat" value="{{ $ziyadah?->ayat_end ?? $rabt?->ayat_end }}">
                                        </div>
                                        <div class="d-block">
                                            <span class="badge bg-light text-body js-page-badge mt-1">Halaman &amp; baris otomatis</span>
                                            <div class="bulk-hint js-page-hint d-none"></div>
                                        </div>
                                    </td>
                                    <td class="text-center"><input type="number" min="1" max="30" class="form-control form-control-sm bulk-num js-field" data-field="juz" value="{{ $ziyadah?->juz ?? $rabt?->juz }}"></td>
                                    <td class="text-center text-nowrap">
                                        <input type="number" step="1" min="1" max="604" class="form-control form-control-sm bulk-page js-field" data-field="halaman_start" placeholder="Awal" value="{{ $ziyadah?->halaman_start ?? $rabt?->halaman_start }}">
                                        <input type="number" step="1" min="1" max="604" class="form-control form-control-sm bulk-page js-field mt-1" data-field="halaman_end" placeholder="Akhir" value="{{ $ziyadah?->halaman_end ?? $rabt?->halaman_end }}">
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <input type="number" min="1" max="15" class="form-control form-control-sm bulk-num js-field" data-field="baris_start" placeholder="Awal" value="{{ $ziyadah?->baris_start ?? $rabt?->baris_start }}">
                                        <input type="number" min="1" max="15" class="form-control form-control-sm bulk-num js-field mt-1" data-field="baris_end" placeholder="Akhir" value="{{ $ziyadah?->baris_end ?? $rabt?->baris_end }}">
                                    </td>
                                    <td class="text-center only-ziyadah"><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm bulk-num js-field" data-field="hasil_hafalan" value="{{ $ziyadah?->hasil_hafalan }}"></td>
                                    <td class="text-center only-rabt"><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm bulk-num js-field" data-field="nilai_rabt" value="{{ $rabt?->nilai_setoran }}"></td>
                                    <td class="text-center"><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm bulk-num js-field" data-field="nilai_tikrar" value="{{ $ziyadah?->nilai_tikrar ?? $rabt?->nilai_tikrar }}"></td>
                                    <td class="text-center">
                                        <select class="form-select form-select-sm js-field only-ziyadah" data-field="status">
                                            @foreach($statusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($ziyadah?->status ?? 'lulus') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select class="form-select form-select-sm js-field only-rabt" data-field="robet_status">
                                            <option value="">—</option>
                                            @foreach($rabtLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($rabt?->robet_status ?? null) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm js-field" data-field="catatan_guru" maxlength="3000" value="{{ $ziyadah?->catatan_guru ?? $rabt?->catatan_guru }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="text-muted small"><i class="ri-information-line me-1"></i>Baris tanpa surah dianggap tidak setor dan tidak disimpan.</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-light">Muat Ulang</a>
                        <button class="btn btn-success" type="submit"><i class="ri-save-line me-1"></i>Simpan Semua</button>
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
        const rows = Array.from(document.querySelectorAll('.bulk-row'));
        const counter = document.getElementById('bulkCounter');

        function currentType(row) {
            const checked = row.querySelector('.js-jenis:checked');
            return checked ? checked.value : '';
        }

        function setType(row, type) {
            row.classList.toggle('is-filled', type !== '');
            row.classList.toggle('rabt-mode', type === 'robet');
            row.classList.toggle('ziyadah-mode', type === 'ziyadah');
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
            counter.textContent = filled + ' santri terisi';
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

            if (! s || ! a) {
                return;
            }

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
                    if (badge) badge.classList.add('d-none');
                    return;
                }
                row.querySelector('[data-field="halaman_start"]').value = data.position.page_start;
                row.querySelector('[data-field="baris_start"]').value = data.position.line_start;
                row.querySelector('[data-field="halaman_end"]').value = data.position.page_end;
                row.querySelector('[data-field="baris_end"]').value = data.position.line_end;
                if (badge) {
                    badge.textContent = `Hal. ${data.position.page_start}–${data.position.page_end} · baris ${data.position.line_start}–${data.position.line_end}`;
                    badge.classList.remove('d-none');
                }
                if (hint) hint.classList.add('d-none');
            } catch (e) {
                if (hint) {
                    hint.textContent = 'Gagal mengambil posisi ayat, isi halaman dan baris manual.';
                    hint.classList.remove('d-none');
                }
                if (badge) badge.classList.add('d-none');
            }
        }

        rows.forEach(function (row) {
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
                    row.querySelector(`.js-jenis[value="ziyadah"]`).checked = true;
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
