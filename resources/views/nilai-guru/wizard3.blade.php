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

    <style>
        .form-control {
            border: none !important;
            box-shadow: none !important;
            outline: none;
        }

        .form-control:focus {
            border: none !important;
            box-shadow: none !important;
            outline: none;
        }
    </style>

    {{-- Info Buku (gaya kartu Jurnal) --}}
    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-article-line me-1 text-primary"></i>Buku Nilai — Nilai Sumatif</h5>
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
                    <a class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" href="#collapseBobotNR" role="button" aria-expanded="false" aria-controls="collapseBobotNR">
                        <i class="ri-settings-3-line me-1"></i> Pengaturan NR Final
                    </a>
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
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
                    <div class="table-responsive nilai-scroll mx-3 my-3">
                        <table id="sumatif-table" class="table table-bordered table-hover align-middle mb-0 js-tools-table">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" style="vertical-align:middle;text-align:center;">#</th>
                                    <th rowspan="2" style="vertical-align:middle;text-align:center;">NIS</th>
                                    <th rowspan="2" style="vertical-align:middle;text-align:center;">Nama Siswa</th>
                                    <th colspan="{{ count($columns) }}" style="vertical-align:middle;text-align:center;">
                                        Sumatif Harian
                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2 py-0 px-2" style="font-size:.65rem;"
                                                data-bs-toggle="modal" data-bs-target="#kolomModal">
                                            <i class="ri-settings-3-line"></i> Kelola Kolom
                                        </button>
                                    </th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">RS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_rs ?? 50.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:75px;text-align:center;">Nilai Murni<br>STS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">STS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_sts ?? 25.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:75px;text-align:center;">Nilai Murni<br>SAS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">SAS<br><small style="vertical-align:middle;font-size:.65rem;font-style:italic;color:#6c757d;">{{ $book['adminBook']->nr_final_weight_sas ?? 25.0 }}%</small></th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">RSA</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:65px;text-align:center;">NR Murni</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:70px;text-align:center;">Raport STS</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:70px;text-align:center;">NR Final</th>
                                    <th rowspan="2" style="vertical-align:middle;min-width:240px;text-align:center;">Ket.</th>
                                </tr>
                                <tr>
                                    @foreach($columns as $col)
                                        <th style="text-align:center;min-width:65px;" title="{{ $col['legacy'] ? 'Kolom legacy (S1–S6)' : 'Kolom tambahan' }}">
                                            {{ $col['label'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $s)
                                @php $n = $sumatifMap->get($s->student_id); $shVals = $shMap[$s->student_id] ?? []; @endphp
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
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
                                    @foreach($columns as $col)
                                        @if($col['id'] === 's6')
                                            @php
                                                $fmtS6 = $formatifMap[$s->student_id] ?? null;
                                                $s6Val = $n?->s6 ?? ($fmtS6?->nr_final !== null ? (float) $fmtS6->nr_final : null);
                                            @endphp
                                            <td class="text-center" style="font-style:italic;" title="S6 otomatis = rata-rata Penilaian Asesmen Formatif">
                                                <span id="s6-{{ $s->student_id }}">{{ $s6Val !== null ? number_format($s6Val, 1) : '—' }}</span>
                                            </td>
                                        @else
                                        <td>
                                            <input type="number" name="sumatif[{{ $s->student_id }}][sh][{{ $col['id'] }}]"
                                                   value="{{ $shVals[$col['id']] ?? '' }}"
                                                   min="0" max="100" placeholder="---"
                                                   style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;">
                                        </td>
                                        @endif
                                    @endforeach
                                    <td class="bg-light text-center" style="font-style:italic;color:#6c757d;">
                                        <span id="rs-{{ $s->student_id }}" data-student="{{ $s->student_id }}">{{ $n->rs ?? '-' }}</span>
                                    </td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sts_murni]" value="{{ $n->sts_murni ?? '' }}" min="0" max="100" placeholder="---" class="murni-sts" title="Nilai hasil ujian STS (tanpa elemen lain)" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sts]" value="{{ $n->sts ?? '' }}" min="0" max="100" placeholder="---" class="input-sts" @if(($n->sts_murni ?? null) === null) readonly @endif title="Wajib isi Nilai Murni STS terlebih dahulu" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sas_murni]" value="{{ $n->sas_murni ?? '' }}" min="0" max="100" placeholder="---" class="murni-sas" title="Nilai hasil ujian SAS (tanpa elemen lain)" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
                                    <td><input type="number" name="sumatif[{{ $s->student_id }}][sas]" value="{{ $n->sas ?? '' }}" min="0" max="100" placeholder="---" class="input-sas" @if(($n->sas_murni ?? null) === null) readonly @endif title="Wajib isi Nilai Murni SAS terlebih dahulu" style="width:100%;text-align:center;background:#f8f9fa00;border:none;text-align-last:center;"></td>
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
                                <tr><td colspan="{{ 12 + count($columns) }}" class="text-center text-muted py-3">Belum ada siswa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-top-dashed bg-light">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="text-muted">RS = Rata-rata SH &bull; STS = Sumatif Tengah Semester &bull; SAS = Sumatif Akhir Semester &bull; RSA = auto &bull; NR Final = RS×wRs + STS×wSts + SAS×wSas</span>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="text-muted small mb-0" style="white-space:nowrap;"><i class="ri-stack-line me-1"></i>Paket Soal Final</label>
                                    <select name="paket_soal_id" class="form-select form-select-sm" style="min-width:220px;">
                                        <option value="">— Tanpa paket (input manual) —</option>
                                        @foreach($paketOptions as $paket)
                                            <option value="{{ $paket->id }}" {{ $selectedPaketId === $paket->id ? 'selected' : '' }}>
                                                {{ $paket->kode_paket }} — {{ \Illuminate\Support\Str::limit($paket->judul, 40) }} ✓
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <span id="save-status" class="text-muted" style="font-size:.8rem;"></span>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Simpan Nilai
                                </button>
                            </div>
                        </div>
                        @if($paketOptions->isEmpty())
                            <small class="text-muted d-block mt-1">
                                Hanya paket soal <strong>final (approved + dipublikasikan)</strong> dari Bank Soal Terpusat yang dapat dipakai sebagai asesmen final.
                            </small>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-help-line me-1 text-secondary"></i> Petunjuk Pengisian</h6></div>
                <div class="card-body" style="font-size:.75rem;">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Isi nilai <strong>Sumatif Harian</strong> (kolom dapat ditambah melalui tombol <strong>Kelola Kolom</strong>), <strong>STS</strong>, dan <strong>SAS</strong> untuk setiap siswa (0–100).</li>
                        <li class="mb-2"><strong>RS</strong> = rata-rata semua kolom Sumatif Harian yang terisi; <strong>RSA</strong> dan <strong>NR Murni</strong> dihitung otomatis.</li>
                        <li class="mb-2"><strong>Nilai Murni STS/SAS</strong> = nilai <em>hasil ujian</em> (tanpa ditambah elemen penilaian lain). Kolom <strong>STS</strong> &amp; <strong>SAS</strong> terkunci sampai Nilai Murni terisi.</li>
                        <li class="mb-2"><strong>NR Final</strong> = RS×bobot RS + STS×bobot STS + SAS×bobot SAS (aturan sama dengan Leger & Rapor).</li>
                        <li>Klik <strong>Simpan Nilai</strong> atau biarkan tersimpan otomatis (autosave) saat berpindah sel.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: peringatan Nilai Murni (konsep modal lord-icon/zoomIn) --}}
    {{-- Modal: peringatan Nilai Murni (template lord-icon / zoomIn) --}}
    <div class="modal fade zoomIn" id="murniModalW3" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#005981" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Nilai Murni Belum Diisi</h4>
                            <p class="text-muted mx-4 mb-0" id="murniModalW3Body">
                                Isi <strong>Nilai Murni</strong> (nilai hasil ujian, tanpa elemen lain) terlebih dahulu sebelum mengisi kolom STS/SAS.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn w-sm btn-primary" id="murniModalW3Focus">Isi Nilai Murni Sekarang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

    @if(session('success'))
        {{-- Modal sukses (konsep modal lord-icon/zoomIn) --}}
        <x-modal-success id="savedModalW3" title="Tersimpan!" message="{{ session('success') }}" />
    @endif
    <style>
    /* ── Buku Nilai — tabel gaya Leger (tema terang/gelap) ── */
    .nilai-scroll { max-height: 72vh; overflow: auto; }
    #sumatif-table { font-size: 12px; }
    /* Hierarki lapisan: header identitas (40) > header lain (30) > body identitas (10) */
    #sumatif-table thead th {
        position: sticky; top: 0; z-index: 30;
        background: var(--bs-tertiary-bg); font-weight: 600;
    }
    #sumatif-table thead tr:first-child th:nth-child(-n+3) { z-index: 40; }
    #sumatif-table tbody td:nth-child(-n+3) {
        position: sticky; z-index: 10; background: var(--bs-body-bg);
    }
    #sumatif-table thead tr:first-child th:nth-child(1), #sumatif-table tbody td:nth-child(1) { left: 0; width: 50px; min-width: 50px; max-width: 50px; }
    #sumatif-table thead tr:first-child th:nth-child(2), #sumatif-table tbody td:nth-child(2) { left: 50px; width: 90px; min-width: 90px; max-width: 90px; }
    #sumatif-table thead tr:first-child th:nth-child(3), #sumatif-table tbody td:nth-child(3) { left: 140px; min-width: 200px; white-space: nowrap; }
    #sumatif-table tbody td:nth-child(3) { box-shadow: inset -1px 0 0 var(--bs-border-color); }
    /* Layar kecil: kolom beku dimatikan agar tabel bisa digeser ke samping */
    @media (max-width: 991.98px) {
        #sumatif-table thead tr:first-child th:nth-child(-n+3),
        #sumatif-table tbody td:nth-child(-n+3) { left: auto !important; }
    }
    #sumatif-table tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }
    #sumatif-table tbody tr:hover td:nth-child(-n+3) { background: var(--bs-secondary-bg); }
    #sumatif-table input {
        width: 100%; height: 28px; border: 0; border-radius: 0;
        background: transparent; text-align: center;
        font-size: 12px; color: var(--bs-body-color);
    }
    #sumatif-table input::placeholder { text-align: center; opacity: .45; }
    #sumatif-table input:focus { outline: none; border: 0; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
</style>
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('savedModalW3');
                if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
            });
        </script>
    @endif

    {{-- Modal: Kelola Kolom Sumatif Harian --}}
    <div class="modal fade" id="kolomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-layout-column-line me-1"></i> Kelola Kolom Sumatif Harian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small py-2">
                        Kolom <strong>S1–S6 (legacy)</strong> wajib dipertahankan untuk kompatibilitas dan tidak dapat dihapus —
                        hanya labelnya yang bisa diganti. Kolom <strong>baru</strong> dapat ditambah, diganti nama, diurutkan, dan dihapus.
                    </div>
                    <div id="kolom-list"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="kolomAdd()">
                        <i class="ri-add-line me-1"></i>Tambah Kolom
                    </button>
                </div>
                <div class="modal-footer">
                    <span id="kolom-status" class="text-muted small me-auto"></span>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="kolomSave()">
                        <i class="ri-save-line me-1"></i>Simpan Kolom
                    </button>
                </div>
            </div>
        </div>
    </div>
    @include('nilai-guru.partials.table-tools')
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
                statusEl.textContent = 'Tersimpan! Memuat ulang…';
                setTimeout(() => { window.location.reload(); }, 700);
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

<script>
// ─── Kelola Kolom Sumatif Harian (tambah/rename/hapus/urut) ─────
const kolomColumnsUrl = @json(route('user.schools.guru-mapel.w3.columns', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]));
const kolomLegacyIds = @json(\App\Services\SumatifHarianService::LEGACY_IDS);
let kolomData = @json($columns);

function kolomIsLegacy(id) { return kolomLegacyIds.includes(id); }

function kolomRender() {
    const wrap = document.getElementById('kolom-list');
    if (!wrap) return;

    wrap.innerHTML = kolomData.map(function (c, i) {
        const legacy = kolomIsLegacy(c.id);
        const safeLabel = String(c.label).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        return '<div class="input-group input-group-sm mb-2">' +
            '<span class="input-group-text" style="width:110px;font-size:.7rem;">' + (legacy ? 'Legacy' : 'Baru') + '</span>' +
            '<input type="text" class="form-control kolom-label" maxlength="60" value="' + safeLabel + '" oninput="kolomData[' + i + '].label = this.value">' +
            '<button type="button" class="btn btn-outline-secondary" onclick="kolomMove(' + i + ', -1)" ' + (i === 0 ? 'disabled' : '') + '><i class="ri-arrow-up-line"></i></button>' +
            '<button type="button" class="btn btn-outline-secondary" onclick="kolomMove(' + i + ', 1)" ' + (i === kolomData.length - 1 ? 'disabled' : '') + '><i class="ri-arrow-down-line"></i></button>' +
            (legacy
                ? '<button type="button" class="btn btn-outline-secondary" disabled title="Kolom legacy tidak dapat dihapus"><i class="ri-lock-line"></i></button>'
                : '<button type="button" class="btn btn-outline-danger" onclick="kolomRemove(' + i + ')" title="Hapus kolom"><i class="ri-delete-bin-line"></i></button>') +
            '</div>';
    }).join('');
}

function kolomUuid() {
    if (window.crypto && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function kolomAdd() {
    kolomData.push({ id: kolomUuid(), label: 'Sumatif Baru', legacy: false });
    kolomRender();
}

var pendingKolomSumatif = null;

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('hapusKolomSumatifConfirm');
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (pendingKolomSumatif === null) return;
        kolomData.splice(pendingKolomSumatif, 1);
        pendingKolomSumatif = null;
        var modalEl = document.getElementById('hapusKolomSumatifModal');
        if (modalEl && window.bootstrap) bootstrap.Modal.getInstance(modalEl)?.hide();
        kolomRender();
    });
});

function kolomRemove(i) {
    pendingKolomSumatif = i;
    var body = document.getElementById('hapusKolomSumatifBody');
    if (body) {
        body.innerHTML = 'Kolom <strong>' + kolomData[i].label + '</strong> akan dihapus. Nilai pada kolom ini ikut terhapus dari semua siswa.';
    }
    var modalEl = document.getElementById('hapusKolomSumatifModal');
    if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function kolomMove(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= kolomData.length) return;
    const tmp = kolomData[i];
    kolomData[i] = kolomData[j];
    kolomData[j] = tmp;
    kolomRender();
}

function kolomSave() {
    const status = document.getElementById('kolom-status');
    status.textContent = 'Menyimpan…';

    fetch(kolomColumnsUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({
            columns: kolomData.map(function (c) { return { id: c.id, label: c.label }; }),
        }),
    })
    .then(async (res) => {
        const data = await res.json().catch(() => ({}));

        if (! res.ok) {
            status.textContent = data.message || ('Gagal menyimpan kolom (HTTP ' + res.status + ')');
            return;
        }

        if (data.success) {
            window.location.reload();
        } else {
            status.textContent = data.message || 'Gagal menyimpan kolom';
        }
    })
    .catch(() => { status.textContent = 'Gagal menyimpan kolom'; });
}

document.getElementById('kolomModal')?.addEventListener('show.bs.modal', function () {
    kolomRender();
    document.getElementById('kolom-status').textContent = '';
});

// ─── Gerbang Nilai Murni: STS/SAS terkunci sampai Nilai Murni terisi ───
function applyMurniGate() {
    document.querySelectorAll('#form-nilai tbody tr').forEach(function (row) {
        [['murni-sts', 'input-sts'], ['murni-sas', 'input-sas']].forEach(function (pair) {
            const murni = row.querySelector('.' + pair[0]);
            const target = row.querySelector('.' + pair[1]);
            if (!murni || !target) return;

            const locked = murni.value === '';
            target.readOnly = locked;
            target.classList.toggle('bg-light', locked);

            // Bantu isi otomatis dengan nilai murni (tetap bisa disesuaikan).
            if (!locked && target.value === '') {
                target.value = murni.value;
            }
        });
    });
}

document.addEventListener('input', function (e) {
    if (e.target.classList && (e.target.classList.contains('murni-sts') || e.target.classList.contains('murni-sas'))) {
        applyMurniGate();
    }
});

document.addEventListener('DOMContentLoaded', applyMurniGate);
applyMurniGate();
</script>
<script>
// ─── Peringatan Nilai Murni: klik kolom STS/SAS yang terkunci ─────
(function () {
    function refresh(row) {
        if (!row || !row.querySelector) return;
        var ms = row.querySelector('.murni-sts'), sts = row.querySelector('.input-sts');
        if (sts) sts.readOnly = !(ms && String(ms.value).trim() !== '');
        var ma = row.querySelector('.murni-sas'), sas = row.querySelector('.input-sas');
        if (sas) sas.readOnly = !(ma && String(ma.value).trim() !== '');
    }

    document.querySelectorAll('#form-nilai tbody tr').forEach(function (row) {
        refresh(row);
        row.addEventListener('input', function (e) {
            if (e.target.classList && (e.target.classList.contains('murni-sts') || e.target.classList.contains('murni-sas'))) {
                refresh(row);
            }
        });
    });

    document.addEventListener('click', function (e) {
        var inp = e.target;
        if (!inp.classList) return;
        var isSts = inp.classList.contains('input-sts');
        var isSas = inp.classList.contains('input-sas');
        if ((!isSts && !isSas) || !inp.readOnly) return;

        var need = isSts ? '.murni-sts' : '.murni-sas';
        var label = isSts ? 'Nilai Murni STS' : 'Nilai Murni SAS';
        var kolom = isSts ? 'STS' : 'SAS';
        var row = inp.closest('tr');
        var src = row ? row.querySelector(need) : null;

        var modalEl = document.getElementById('murniModalW3');
        if (!modalEl || !window.bootstrap) return;
        var body = document.getElementById('murniModalW3Body');
        if (body) {
            body.innerHTML = 'Kolom <strong>' + kolom + '</strong> belum dapat diisi. Isi <strong>' + label +
                '</strong> (nilai hasil ujian, tanpa elemen lain) terlebih dahulu.';
        }
        var btn = document.getElementById('murniModalW3Focus');
        if (btn) {
            btn.onclick = function () {
                if (src) { src.focus(); if (src.select) src.select(); }
                var m = bootstrap.Modal.getInstance(modalEl);
                if (m) m.hide();
            };
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        inp.blur();
    });
})();
</script>
<script>
// ─── Header beku multi-baris tabel Nilai Sumatif ───
(function () {
    function applyTops() {
        var table = document.getElementById('sumatif-table');
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
<x-modal-confirm id="hapusKolomSumatifModal" class="zoomIn"
    icon="https://cdn.lordicon.com/gsqxdxog.json"
    primaryColor="#f7b84b" secondaryColor="#f06548"
    title="Hapus kolom ini?"
    submitLabel="Ya, Hapus!"
    submitIcon="ri-delete-bin-line"
    submitClass="btn-danger"
    submitId="hapusKolomSumatifConfirm">
    <p class="text-center text-muted mb-0" id="hapusKolomSumatifBody">Kolom akan dihapus.</p>
</x-modal-confirm>
@endpush
