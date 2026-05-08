@extends('layouts.master')
@section('title') Nilai Sumatif @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Nilai Sumatif @endslot
        @slot('title') Nilai Sumatif @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Buku — full width atas --}}
    <div class="card border-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-md-auto">
                    <p class="mb-n1 btn btn-primary btn-sm" style="font-size: 10px;"><i class="ri-book-2-line me-1"></i>{{ $book['adminBook']->subject->name ?? '-' }}</p>
                    <p class="mb-n1 btn btn-secondary btn-sm"><i class="ri-team-line me-1"></i>{{ $book['adminBook']->studyGroup->name }}</p>
                    <p class="mb-n1 btn btn-dark btn-sm"><i class="ri-calendar-line me-1"></i>{{ ucfirst($book['adminBook']->semester) }}</p>
                    <p class="mb-n1 btn btn-warning btn-sm"><i class="ri-government-line me-1"></i>{{ $book['adminBook']->academicYear->name ?? '-' }}</p>
                </div>
                <div class="col-md-auto ms-auto">
                    <span id="autosave-indicator" class="badge bg-light text-primary border d-flex align-items-center gap-1" style="font-size:.75rem;">
                        <span id="autosave-dot" class="rounded-circle bg-secondary" style="width:8px;height:8px;display:inline-block;"></span>
                        <span id="autosave-text">Menunggu perubahan…</span>
                    </span>
                </div>
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <a class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" href="#collapseBobotNR" role="button" aria-expanded="false" aria-controls="collapseBobotNR">
                        <i class="ri-settings-3-line me-1"></i> Pengaturan NR Final
                    </a>
                </div>
            </div>
            {{-- Collapsible: Pengaturan Bobot NR Final --}}
            <div class="collapse mt-2" id="collapseBobotNR">
                <div class="border rounded p-2" style="background:#f8f9fa;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold" style="font-size:.75rem;">Bobot NR Final:</span>
                        <div class="input-group input-group-sm" style="width:120px;">
                            <span class="input-group-text" style="font-size:.7rem;">RS %</span>
                            <input type="number" id="wRs" class="form-control" min="0" max="100" step="0.5"
                                value="{{ $book['adminBook']->nr_final_weight_rs ?? 50.0 }}">
                        </div>
                        <div class="input-group input-group-sm" style="width:120px;">
                            <span class="input-group-text" style="font-size:.7rem;">STS %</span>
                            <input type="number" id="wSts" class="form-control" min="0" max="100" step="0.5"
                                value="{{ $book['adminBook']->nr_final_weight_sts ?? 25.0 }}">
                        </div>
                        <div class="input-group input-group-sm" style="width:120px;">
                            <span class="input-group-text" style="font-size:.7rem;">SAS %</span>
                            <input type="number" id="wSas" class="form-control" min="0" max="100" step="0.5"
                                value="{{ $book['adminBook']->nr_final_weight_sas ?? 25.0 }}">
                        </div>
                        <div id="bobot-sum" class="badge bg-secondary" style="font-size:.7rem;">Total: 100%</div>
                        <span id="bobot-status" class="text-muted" style="font-size:.7rem;"></span>
                    </div>
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
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Catatan Guru</a>
                    </div>
                </div>

                <form id="form-nilai" method="POST" action="{{ route('user.schools.guru-mapel.w3.store', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}">
                    @csrf
                    @php $autosaveUrl = route('user.schools.guru-mapel.autosave', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]); @endphp
                    <div class="table-responsive mx-3 my-3">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" style="vertical-align:middle;width:35px;text-align:center;">#</th>
                                    <th rowspan="2" style="vertical-align:middle;width:70px;text-align:center;">NIS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:200px;text-align:center;">Nama Siswa</th>
                                    <th colspan="6" style="vertical-align:middle;text-align:center;text-align:center;">Sumatif Harian</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">RS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_rs ?? 50.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">STS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_sts ?? 25.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">SAS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_sas ?? 25.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">RSA</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">NR Murni</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:70px;text-align:center;">Raport STS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:70px;text-align:center;">NR Final</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:240px;text-align:center;">Ket.</th>
                                </tr>
                                <tr>
                                    <th style="text-align:center;min-width:65px;">S1</th>
                                    <th style="text-align:center;min-width:65px;">S2</th>
                                    <th style="text-align:center;min-width:65px;">S3</th>
                                    <th style="text-align:center;min-width:65px;">S4</th>
                                    <th style="text-align:center;min-width:65px;">S5</th>
                                    <th style="text-align:center;min-width:65px;">S6</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $s)
                                @php $n = $sumatifMap->get($s->student_id); @endphp
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
                                    <td class="text-center">{{ $s->student->nis ?? '-' }}</td>
                                    <td>{{ $s->student->name ?? '-' }}</td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s1]" value="{{ $n->s1 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s2]" value="{{ $n->s2 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s3]" value="{{ $n->s3 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s4]" value="{{ $n->s4 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s5]" value="{{ $n->s5 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][s6]" value="{{ $n->s6 ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td class="bg-light text-center" style="font-style:italic;color:#6c757d;">
                                        <span id="rs-{{ $s->student_id }}" data-student="{{ $s->student_id }}">{{ $n->rs ?? '-' }}</span>
                                    </td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sts]" value="{{ $n->sts ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sas]" value="{{ $n->sas ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td class="bg-light text-center" style="font-style:italic;color:#6c757d;">
                                        <span id="rsa-{{ $s->student_id }}" data-student="{{ $s->student_id }}">{{ $n->rsa ?? '-' }}</span>
                                    </td>
                                    <td class="bg-light text-center" style="font-style:italic;color:#6c757d;">
                                        <span id="nrm-{{ $s->student_id }}" data-student="{{ $s->student_id }}">{{ $n->nr_murni ?? '-' }}</span>
                                    </td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][raport_sts]" value="{{ $n->raport_sts ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td class="bg-light text-center fw-bold" style="font-style:italic;color:#0d6efd;">
                                        <span id="nrf-{{ $s->student_id }}">{{ $n->nr_final ?? '-' }}</span>
                                    </td>
                                    <td><input type="text" name="sumatif[{{ $s->student_id }}][ket]" value="{{ $n->ket ?? '' }}" placeholder="---" style="width:100%;text-align:left;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                </tr>
                                @empty
                                <tr><td colspan="18" class="text-center text-muted py-3">Belum ada siswa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-top-dashed bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">RS = Rata-rata SH &bull; STS = Sumatif Tengah Semester &bull; SAS = Sumatif Akhir Semester &bull; RSA = auto &bull; NR Final = RS×wRs + STS×wSts + SAS×wSas</span>
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
                        <li class="mb-2">Isi nilai <strong>Sumatif Harian</strong> (S1–S6), <strong>STS</strong>, dan <strong>SAS</strong> untuk setiap siswa (0–100).</li>
                        <li class="mb-2"><strong>RS</strong>, <strong>RSA</strong>, dan <strong>NR Murni</strong> dihitung secara otomatis.</li>
                        <li class="mb-2"><strong>NR Final</strong> dapat diubah manual sesuai kebutuhan.</li>
                        <li>Klik <strong>Simpan Nilai</strong> untuk menyimpan data.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const statusEl = document.getElementById('save-status');
    const dotEl = document.getElementById('autosave-dot');
    const textEl = document.getElementById('autosave-text');
    const form = document.getElementById('form-nilai');

    // CSRF token from meta tag (set by layouts/master.blade.php)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let debounceTimer = null;
    let saveInFlight = false;
    const DEBOUNCE_MS = 1500;

    // ─── Status helpers ───────────────────────────────────────────
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

    // ─── Per-row computed cells (auto fields) — update via stable IDs ────
    function updateRowCells(studentId, data) {
        const set = (prefix, val) => {
            const el = document.getElementById(prefix + studentId);
            if (el) el.textContent = (val !== null && val !== undefined) ? val : '-';
        };
        if (data.rs !== undefined)        set('rs-', data.rs);
        if (data.rsa !== undefined)       set('rsa-', data.rsa);
        if (data.nr_murni !== undefined) set('nrm-', data.nr_murni);
        if (data.nr_final !== undefined)  set('nrf-', data.nr_final);
    }

    // Legacy column-index fallback (unused, kept for safety)
    function updateRowCellsByCol(studentId, data) {
        const row = document.querySelector(`input[name="sumatif[${studentId}][s1]"]`)?.closest('tr');
        if (!row) return;
        const setText = (col, val) => {
            const td = row.querySelector(`td:nth-child(${col})`);
            if (td) td.textContent = (val !== null && val !== undefined) ? val : '-';
        };
        if (data.rs !== undefined)        setText(13, data.rs);
        if (data.rsa !== undefined)       setText(15, data.rsa);
        if (data.nr_murni !== undefined) setText(16, data.nr_murni);
    }

    // Update all saved rows from server response
    function updateAllRows(savedRows) {
        if (!Array.isArray(savedRows)) return;
        savedRows.forEach(function(row) {
            updateRowCells(row.student_id, row);
        });
    }

    // ─── Actual save request ──────────────────────────────────────
    function doSave() {
        if (!form || saveInFlight) return;
        saveInFlight = true;

        setIndicator('bg-warning', 'Menyimpan…');
        statusEl.textContent = '';
        statusEl.style.color = '#0d6efd';

        const formData = new FormData(form);
        formData.append('type', 'sumatif');

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
            if (!res.ok) {
                res.json().catch(() => ({}));
                throw new Error('HTTP ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            // Update ALL rows that were saved
            if (data.saved_rows) {
                updateAllRows(data.saved_rows);
            } else if (data.student_id) {
                // Fallback for single-row response
                updateRowCells(data.student_id, data);
            }
            showSuccess();
        })
        .catch(() => {
            saveInFlight = false;
            showError();
        });
    }

    // ─── Debounced trigger ─────────────────────────────────────────
    function triggerSave() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSave, DEBOUNCE_MS);
    }

    // ─── Attach to all form inputs ────────────────────────────────
    if (form) {
        form.querySelectorAll('input, select').forEach(function(el) {
            el.addEventListener('input', triggerSave);
            el.addEventListener('change', triggerSave);
        });
    }

    // ─── Bobot NR Final management ─────────────────────────────────
    (function bobotSetup() {
        const wRsEl   = document.getElementById('wRs');
        const wStsEl  = document.getElementById('wSts');
        const wSasEl  = document.getElementById('wSas');
        const sumEl   = document.getElementById('bobot-sum');
        const statusEl = document.getElementById('bobot-status');
        const bobotForm = document.getElementById('form-nilai');

        let bobotTimer = null;

        function getTotal() {
            return (
                (parseFloat(wRsEl.value)  || 0) +
                (parseFloat(wStsEl.value) || 0) +
                (parseFloat(wSasEl.value) || 0)
            );
        }

        function renderTotal() {
            const t = getTotal();
            sumEl.textContent = 'Total: ' + t.toFixed(1) + '%';
            if (Math.abs(t - 100) < 0.01) {
                sumEl.className = 'badge bg-success';
                statusEl.textContent = '';
            } else {
                sumEl.className = 'badge bg-danger';
                statusEl.textContent = 'Total harus 100%';
            }
        }

        function saveBobot() {
            const t = getTotal();
            if (Math.abs(t - 100) > 0.01) return; // Jangan simpan kalau total bukan 100

            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('nr_final_weight_rs',  wRsEl.value);
            fd.append('nr_final_weight_sts', wStsEl.value);
            fd.append('nr_final_weight_sas', wSasEl.value);

            statusEl.textContent = 'Menyimpan…';

            fetch('{{ route('user.schools.guru-mapel.w3.bobot', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: fd,
            })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(() => {
                statusEl.textContent = 'Tersimpan!';
                setTimeout(() => { statusEl.textContent = ''; }, 2000);
            })
            .catch(() => {
                statusEl.textContent = 'Gagal menyimpan bobot';
            });
        }

        function triggerBobotSave() {
            clearTimeout(bobotTimer);
            bobotTimer = setTimeout(saveBobot, 1200);
        }

        [wRsEl, wStsEl, wSasEl].forEach(el => {
            el.addEventListener('input', () => { renderTotal(); triggerBobotSave(); });
        });

        renderTotal();
    })();
})();
</script>
@endpush
