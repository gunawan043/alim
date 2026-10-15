@extends('layouts.master')
@section('title') Asesmen Formatif @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Asesmen Formatif @endslot
        @slot('title') Asesmen Formatif @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Buku (gaya kartu Jurnal) --}}
    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-article-line me-1 text-primary"></i>Buku Nilai — Asesmen Formatif</h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-primary-subtle text-primary"><i class="ri-book-2-line me-1"></i>{{ $book['adminBook']->subject->name ?? '-' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-team-line me-1"></i>Kelas - {{ $book['adminBook']->studyGroup->name }}</span>
                        <span class="badge bg-dark-subtle text-dark"><i class="ri-calendar-line me-1"></i>Semester {{ ucfirst($book['adminBook']->semester) }}</span>
                        <span class="badge bg-warning-subtle text-warning"><i class="ri-calendar-2-line me-1"></i>{{ $book['adminBook']->academicYear->name ?? '-' }}</span>
                        <span id="autosave-indicator" class="badge bg-light text-primary border d-flex align-items-center gap-1" style="font-size:.75rem;">
                            <span id="autosave-dot" class="rounded-circle bg-secondary" style="width:8px;height:8px;display:inline-block;"></span>
                            <span id="autosave-text">Menunggu perubahan…</span>
                        </span>
                    </div>
                </div>
                <div class="col-md-auto d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
                    <a href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $book['adminBook']->academic_year_id, 'semester' => $book['adminBook']->semester]) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-line-chart-line me-1"></i> Realisasi
                    </a>
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed py-2">
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Presensi Siswa</a>
                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Jurnal Pembelajaran</a>
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Catatan Guru</a>
                    </div>
                </div>

                <form id="form-nilai" method="POST" action="{{ route('user.schools.guru-mapel.w4.store', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}">
                    @csrf
                    @php $autosaveUrl = route('user.schools.guru-mapel.autosave', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]); @endphp
                    <div class="table-responsive tf-scroll mx-3 my-3">
                        <table id="formatif-table" class="table table-bordered table-hover align-middle mb-0 js-tools-table" style="min-width:1500px;">
                            <thead class="table-light">
                                <tr style="font-size:.80rem;">
                                    <th rowspan="2" style="vertical-align:middle;width:40px;min-width:40px;text-align:center;">#</th>
                                    <th rowspan="2" style="vertical-align:middle;width:70px;min-width:70px;text-align:center;">NIS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:200px;">Nama Siswa</th>
                                    @foreach(['lkpd' => 'Mengerjakan LKPD', 'diskusi' => 'Diskusi Kelompok', 'kuis' => 'Kuis/Tes Singkat', 'antarteman' => 'Penilaian Antarteman'] as $aspectKey => $aspectLabel)
                                        @php $aspectCols = array_values($formatifColumns[$aspectKey] ?? ['1', '2', '3', '4']); @endphp
                                        <th colspan="{{ count($aspectCols) + 1 }}" class="text-center" style="background:rgba(var(--bs-primary-rgb),.06);">
                                            {{ $aspectLabel }}
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1 tf-col-add-aspect" data-aspect="{{ $aspectKey }}"
                                                    title="Tambah kolom {{ $aspectLabel }}" style="line-height:1;"><i class="ri-add-circle-line"></i></button>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" style="vertical-align:middle;width:100px;min-width:100px;text-align:center;">NR Final</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:60px;text-align:center;">Ket.</th>
                                </tr>
                                <tr style="font-size:.75rem;">
                                    @foreach(['lkpd', 'diskusi', 'kuis', 'antarteman'] as $aspectKey)
                                        @php $aspectCols = array_values($formatifColumns[$aspectKey] ?? ['1', '2', '3', '4']); @endphp
                                        @foreach($aspectCols as $colLabel)
                                            <th style="text-align:center;width:64px;min-width:64px;" title="{{ $colLabel }}">
                                                {{ $colLabel }}
                                                @if(count($aspectCols) > 4)
                                                    <button type="button" class="btn btn-sm btn-link p-0 text-danger tf-col-remove-aspect"
                                                            data-aspect="{{ $aspectKey }}" data-idx="{{ $loop->index }}"
                                                            title="Hapus kolom ini" style="line-height:1;"><i class="ri-close-line"></i></button>
                                                @endif
                                            </th>
                                        @endforeach
                                        <th style="text-align:center;width:64px;min-width:64px;" title="Rata-rata {{ $aspectKey }}">Rata</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $s)
                                @php $n = $formatifMap->get($s->student_id); @endphp
                                <tr data-student="{{ $s->student_id }}" data-name-prefix="formatif[{{ $s->student_id }}]">
                                    <td class="text-center fw-bold text-muted" style="vertical-align:middle;">{{ $i + 1 }}</td>
                                    <td class="text-muted text-center"><code>{{ $s->student->nis ?? '-' }}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-xs">
                                                <span class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $s->student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                </span>
                                            </div>
                                            <span class="">{{ $s->student->name }}</span>
                                        </div>
                                    </td>
                                    @php
                                        $tfAspects = [
                                            'lkpd' => ['scores' => $n->lkpd_scores ?? [], 'scalar' => $n->skor_lkpd ?? null],
                                            'diskusi' => ['scores' => $n->diskusi_scores ?? [], 'scalar' => $n->skor_diskusi ?? null],
                                            'kuis' => ['scores' => $n->kuis_scores ?? [], 'scalar' => $n->skor_kuis ?? null],
                                            'antarteman' => ['scores' => $n->antarteman_scores ?? [], 'scalar' => $n->skor_antarteman ?? null],
                                        ];
                                    @endphp
                                    @foreach($tfAspects as $aspect => $g)
                                        @php
                                            $scores = is_array($g['scores']) ? array_values($g['scores']) : [];
                                            if (empty($scores) && $g['scalar'] !== null) {
                                                $scores = [(float) $g['scalar']];
                                            }
                                            $cols = array_values($formatifColumns[$aspect] ?? ['1', '2', '3', '4']);
                                            $slots = max(4, count($cols), count($scores));
                                            $vals = array_map('floatval', $scores);
                                            $avg = count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null;
                                        @endphp
                                        @for($k = 0; $k < $slots; $k++)
                                            <td class="p-0" style="text-align:center;">
                                                <input type="number" class="tf-input" data-aspect="{{ $aspect }}"
                                                       name="formatif[{{ $s->student_id }}][{{ $aspect }}_scores][]"
                                                       value="{{ $scores[$k] ?? '' }}"
                                                       title="{{ $cols[$k] ?? ($k + 1) }}"
                                                       min="0" max="100" placeholder="---"
                                                       style="width:100%;height:28px;border:0;background:transparent;text-align:center;color:var(--bs-body-color);">
                                            </td>
                                        @endfor
                                        <td class="text-center fw-semibold text-muted tf-avg-cell" data-aspect="{{ $aspect }}" style="width:64px;">
                                            {{ $avg !== null ? number_format($avg, 2) : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="bg-light text-center fw-bold" style="vertical-align:middle;font-style:italic;color:#0d6efd;">
                                        <span id="nrf-{{ $s->student_id }}">{{ $n->nr_final ?? '-' }}</span>
                                    </td>
                                    <td><input type="text" name="formatif[{{ $s->student_id }}][ket]" value="{{ $n->ket ?? '' }}" placeholder="---" style="width:100%;min-width:50px;text-align:left;background:#f8f9fa00;border:none;"></td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">Belum ada siswa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-top-dashed bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:.75rem;">Tiap aspek minimal 4 kolom — tambah dengan tombol <i class="ri-add-circle-line"></i> di header aspek &bull; Rata per aspek → NR Final &bull; S6 otomatis tersinkron</span>
                            <div class="d-flex align-items-center gap-2">
                                <span id="save-status" class="text-muted" style="font-size:.8rem;"></span>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Simpan Nilai
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-help-line me-1 text-secondary"></i> Petunjuk Pengisian</h6></div>
                <div class="card-body" style="font-size:.75rem;">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Isi skor <strong>LKPD</strong>, <strong>Diskusi</strong>, <strong>Kuis/Tes</strong>, dan <strong>Antarteman</strong> untuk setiap siswa (0–100).</li>
                        <li class="mb-2">Nilai kosong berarti <strong>belum dinilai</strong>.</li>
                        <li>Klik <strong>Simpan Nilai</strong> untuk menyimpan data.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @include('nilai-guru.partials.table-tools')
@endsection

    {{-- Modal konfirmasi hapus kolom (konsep lord-icon) --}}
    <x-modal-confirm id="hapusKolomModal" class="zoomIn"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primaryColor="#f7b84b" secondaryColor="#f06548"
        title="Hapus kolom ini?"
        submitLabel="Ya, Hapus!"
        submitIcon="ri-delete-bin-line"
        submitClass="btn-danger"
        submitId="hapusKolomConfirm">
        <p class="text-center text-muted mb-0" id="hapusKolomBody">Kolom akan dihapus.</p>
    </x-modal-confirm>

@push('scripts')

<style>
    /* ── Asesmen Formatif — tabel profesional (tema terang/gelap) ── */
    .tf-scroll { max-height: 70vh; overflow: auto; }
    #formatif-table { font-size: 12px; }
    #formatif-table thead th {
        position: sticky; top: 0; z-index: 3;
        background: var(--bs-tertiary-bg); font-weight: 600;
    }
    #formatif-table th:nth-child(-n+3), #formatif-table td:nth-child(-n+3) {
        position: sticky; z-index: 2; background: var(--bs-body-bg);
    }
    #formatif-table thead th:nth-child(-n+3) { z-index: 4; }
    #formatif-table th:nth-child(1), #formatif-table td:nth-child(1) { left: 0; width: 44px; min-width: 44px; }
    #formatif-table th:nth-child(2), #formatif-table td:nth-child(2) { left: 44px; width: 82px; min-width: 82px; }
    #formatif-table th:nth-child(3), #formatif-table td:nth-child(3) { left: 126px; min-width: 190px; white-space: nowrap; }
    #formatif-table tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }
    #formatif-table tbody tr:hover td:nth-child(-n+3) { background: var(--bs-secondary-bg); }
    #formatif-table td.p-0 { padding: 0; }
    #formatif-table td:nth-child(-n+3) { box-shadow: inset -1px 0 0 var(--bs-border-color); }
    #formatif-table input.tf-input {
        width: 100%; height: 28px; border: 0; border-radius: 0;
        background: transparent; text-align: center;
        font-size: 12px; color: var(--bs-body-color);
    }
    #formatif-table input.tf-input::placeholder { text-align: center; opacity: .45; }
    #formatif-table input.tf-input:focus { outline: none; background: var(--bs-body-bg); box-shadow: inset 0 0 0 2px var(--bs-primary); }
    #formatif-table .tf-avg-cell { background: rgba(var(--bs-primary-rgb), .06); font-weight: 700; color: var(--bs-primary); }
</style>
<script>
(function() {
    const statusEl = document.getElementById('save-status');
    const dotEl = document.getElementById('autosave-dot');
    const textEl = document.getElementById('autosave-text');
    const form = document.getElementById('form-nilai');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let debounceTimer = null;
    let saveInFlight = false;
    const DEBOUNCE_MS = 1500;

    function setIndicator(dotColor, text) {
        dotEl.className = 'rounded-circle ' + dotColor;
        textEl.textContent = text;
    }

    function showSuccess() {
        const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        setIndicator('bg-success', 'Tersimpan otomatis ' + time);
        statusEl.textContent = '';
        setTimeout(() => setIndicator('bg-secondary', 'Menunggu perubahan…'), 3000);
    }

    function showError(msg) {
        setIndicator('bg-danger', msg || 'Gagal menyimpan');
        statusEl.textContent = msg || 'Gagal menyimpan';
        statusEl.style.color = '#dc3545';
        setTimeout(() => setIndicator('bg-secondary', 'Menunggu perubahan…'), 4000);
    }

    function updateNrFinalCell(studentId, value) {
        const el = document.getElementById('nrf-' + studentId);
        if (el) el.textContent = (value !== null && value !== undefined) ? value : '-';
    }

    function doSave() {
        if (!form || saveInFlight) return;
        saveInFlight = true;

        setIndicator('bg-warning', 'Menyimpan…');
        statusEl.textContent = '';
        statusEl.style.color = '#0d6efd';

        const formData = new FormData(form);
        formData.append('type', 'formatif');

        fetch('{{ $autosaveUrl }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        })
        .then(res => {
            saveInFlight = false;
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.saved && !data.skipped && data.saved_rows) {
                data.saved_rows.forEach(function(row) {
                    if (row.nr_final !== undefined) {
                        updateNrFinalCell(row.student_id, row.nr_final);
                    }
                });
                showSuccess();
            }
        })
        .catch(() => {
            saveInFlight = false;
            showError();
        });
    }

    function triggerSave() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSave, DEBOUNCE_MS);
    }

    if (form) {
        form.querySelectorAll('input, select').forEach(function(el) {
            el.addEventListener('input', triggerSave);
            el.addEventListener('change', triggerSave);
        });
    }
})();
</script>
<script>
// ─── Asesmen Formatif: tambah kolom & rata-rata live per aspek ─────
(function () {
    function aspectAvg(group) {
        var vals = Array.prototype.slice.call(group.querySelectorAll('input')).map(function (i) {
            return parseFloat(i.value);
        }).filter(function (v) { return !isNaN(v); });
        if (!vals.length) return null;
        return vals.reduce(function (a, b) { return a + b; }, 0) / vals.length;
    }

    function refreshRow(row) {
        var avgs = [];
        row.querySelectorAll('.tf-avg-cell').forEach(function (cell) {
            var aspect = cell.getAttribute('data-aspect');
            var vals = Array.prototype.slice.call(row.querySelectorAll('.tf-input[data-aspect="' + aspect + '"]'))
                .map(function (i) { return parseFloat(i.value); })
                .filter(function (v) { return !isNaN(v); });
            var avg = vals.length ? vals.reduce(function (a, b) { return a + b; }, 0) / vals.length : null;
            cell.textContent = avg === null ? '—' : avg.toFixed(2);
            if (avg !== null) avgs.push(avg);
        });
        var nrf = row.querySelector('span[id^="nrf-"]');
        if (nrf) {
            nrf.textContent = avgs.length
                ? (avgs.reduce(function (a, b) { return a + b; }, 0) / avgs.length).toFixed(2)
                : '-';
        }
    }

    document.querySelectorAll('#form-nilai tbody tr[data-student]').forEach(function (row) {
        refreshRow(row);
        row.addEventListener('input', function () { refreshRow(row); });

    });
})();
</script>


<script>
// ─── Tambah/hapus kolom per aspek (langsung di header) ───
(function () {
    var url = "{{ route('user.schools.guru-mapel.w4.kolom', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}";
    var cols = @json($formatifColumns);
    var aspectLabels = { lkpd: 'Mengerjakan LKPD', diskusi: 'Diskusi Kelompok', kuis: 'Kuis/Tes Singkat', antarteman: 'Penilaian Antarteman' };

    function save(next) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ columns: next }),
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (d) { if (d && d.success) { window.location.reload(); } })
        .catch(function () { window.alert('Gagal menyimpan kolom.'); });
    }

    document.querySelectorAll('.tf-col-add-aspect').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var aspect = this.getAttribute('data-aspect');
            var next = Object.assign({}, cols);
            next[aspect] = (next[aspect] || []).slice();
            if (next[aspect].length >= 12) { return; }
            next[aspect].push(String(next[aspect].length + 1));
            save(next);
        });
    });

    // Penomoran otomatis: label kosong/angka dinomori ulang berurutan; label custom tetap.
    function renumber(labels) {
        var n = 0;
        return labels.map(function (label) {
            var t = String(label == null ? '' : label).trim();
            if (t === '' || /^\d+$/.test(t)) { n += 1; return String(n); }
            return t;
        });
    }

    var pendingKolom = null;

    document.querySelectorAll('.tf-col-remove-aspect').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var aspect = this.getAttribute('data-aspect');
            var i = parseInt(this.getAttribute('data-idx'), 10);
            if (((cols[aspect] || []).length) <= 4) { return; }

            pendingKolom = { aspect: aspect, idx: i };
            var body = document.getElementById('hapusKolomBody');
            if (body) {
                body.innerHTML = 'Kolom <strong>' + (i + 1) + '</strong> untuk <strong>' + (aspectLabels[aspect] || aspect) + '</strong> akan dihapus. Kolom sisanya dinomori ulang otomatis.';
            }
            var modalEl = document.getElementById('hapusKolomModal');
            if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });

    var confirmBtn = document.getElementById('hapusKolomConfirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!pendingKolom) { return; }
            var next = Object.assign({}, cols);
            next[pendingKolom.aspect] = (next[pendingKolom.aspect] || []).slice();
            next[pendingKolom.aspect].splice(pendingKolom.idx, 1);
            next[pendingKolom.aspect] = renumber(next[pendingKolom.aspect]);

            var modalEl = document.getElementById('hapusKolomModal');
            if (modalEl && window.bootstrap) { bootstrap.Modal.getInstance(modalEl)?.hide(); }

            save(next);
        });
    }
})();
</script>
<script>
// ─── Header beku multi-baris tabel formatif ───
(function () {
    function applyTops() {
        var table = document.getElementById('formatif-table');
        if (!table) return;
        var top = 0;
        table.querySelectorAll('thead tr').forEach(function (tr) {
            var h = tr.getBoundingClientRect().height;
            tr.querySelectorAll('th').forEach(function (th) { th.style.top = top + 'px'; });
            top += h;
        });
    }
    document.addEventListener('DOMContentLoaded', applyTops);
    window.addEventListener('load', applyTops);
    window.addEventListener('resize', applyTops);
})();
</script>
@endpush
