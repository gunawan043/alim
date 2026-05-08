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
                    <div class="table-responsive mx-3 my-3">
                        <table class="table table-bordered table-striped table-hover mb-0" style="table-layout:fixed;min-width:800px;">
                            <thead class="table-light">
                                <tr style="font-size:.80rem;">
                                    <th style="vertical-align:middle;width:40px;min-width:40px;text-align:center;">#</th>
                                    <th style="vertical-align:middle;width:70px;min-width:70px;text-align:center;">NIS</th>
                                    <th style="vertical-align:middle;min-width:200px;">Nama Siswa</th>
                                    <th style="vertical-align:middle;width:110px;min-width:110px;text-align:center;">Mengerjakan LKPD</th>
                                    <th style="vertical-align:middle;width:110px;min-width:110px;text-align:center;">Diskusi Kelompok</th>
                                    <th style="vertical-align:middle;width:110px;min-width:110px;text-align:center;">Kuis/Tes Singkat</th>
                                    <th style="vertical-align:middle;width:110px;min-width:110px;text-align:center;">Penilaian Antarteman</th>
                                    <th style="vertical-align:middle;width:110px;min-width:110px;text-align:center;">NR Final</th>
                                    <th style="vertical-align:middle;min-width:50px;text-align:center;">Ket.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $s)
                                @php $n = $formatifMap->get($s->student_id); @endphp
                                <tr>
                                    <td class="text-center fw-bold text-muted" style="vertical-align:middle;">{{ $i + 1 }}</td>
                                    <td class="text-center" style="vertical-align:middle;">{{ $s->student->nis ?? '-' }}</td>
                                    <td style="vertical-align:middle;">{{ $s->student->name ?? '-' }}</td>
                                    <td><input type="number" name="formatif[{{ $s->student_id }}][skor_lkpd]" value="{{ $n->skor_lkpd ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;min-width:70px;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="formatif[{{ $s->student_id }}][skor_diskusi]" value="{{ $n->skor_diskusi ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;min-width:70px;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="formatif[{{ $s->student_id }}][skor_kuis]" value="{{ $n->skor_kuis ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;min-width:70px;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="formatif[{{ $s->student_id }}][skor_antarteman]" value="{{ $n->skor_antarteman ?? '' }}" min="0" max="100" placeholder="---" style="width:100%;min-width:80px;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
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
                            <span class="text-muted" style="font-size:.75rem;">NR Final = rata-rata skor yang terisi &bull; Kosong = belum dinilai</span>
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
@endsection

@push('scripts')
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
@endpush
