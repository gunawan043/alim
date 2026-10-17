@extends('layouts.master')
@section('title') Input Nilai SAS — {{ $adminBook->subject->name ?? '' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">Data Nilai</a> @endslot
        @slot('title') Nilai SAS — {{ $adminBook->studyGroup->name ?? '' }} / {{ $adminBook->subject->name ?? '' }}</title>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            {{-- Info Header --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted" style="font-size:11px;">MATA PELAJARAN</label>
                            <p class="mb-0 fw-semibold">{{ $adminBook->subject->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted" style="font-size:11px;">KELAS</label>
                            <p class="mb-0 fw-semibold">{{ $adminBook->studyGroup->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted" style="font-size:11px;">TAHUN AJARAN</label>
                            <p class="mb-0 fw-semibold">{{ $adminBook->academicYear->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted" style="font-size:11px;">SEMESTER</label>
                            <p class="mb-0 fw-semibold">{{ ucfirst($adminBook->semester) }}</p>
                        </div>
                        @if($isPrivileged)
                            <div class="col-md-3">
                                <label class="form-label text-muted" style="font-size:11px;">GURU</label>
                                <p class="mb-0 fw-semibold">{{ $adminBook->teacher->name ?? '-' }}</p>
                            </div>
                        @endif
                        <div class="col-md-3 text-end">
                            <a href="{{ route('user.schools.nilai.sts', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}"
                               class="btn btn-outline-secondary mt-3">
                                <i class="ri-arrow-left-line me-1"></i> Kembali ke STS
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Input Nilai SAS + Formatif + Penghargaan + Pembiasaan --}}
            <form method="POST"
                  action="{{ route('user.schools.nilai.sas.store', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}"
                  id="formSas">
                @csrf

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            Penilaian Asesmen Formatif &amp; Sumatif Akhir Semester (SAS)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-nowrap align-middle mb-0" style="min-width: 1600px;">
                                <thead class="table-light text-center">
                                    {{-- Row 1: main groups --}}
                                    <tr>
                                        <th rowspan="3" class="align-middle text-center" width="40">No</th>
                                        <th rowspan="3" class="align-middle text-center" width="60">NIS</th>
                                        <th rowspan="3" class="align-middle text-center" width="220">Nama Siswa</th>

                                        {{-- Formatif --}}
                                        <th colspan="4" class="table-primary">Penilaian Asesmen Formatif</th>

                                        {{-- Sumatif --}}
                                        <th colspan="2" class="table-info">Sumatif</th>

                                        {{-- NR --}}
                                        <th colspan="4" class="table-success">Nilai Raport</th>

                                        {{-- Penghargaan Akademik --}}
                                        <th colspan="6" class="table-warning">Penghargaan Akademik</th>

                                        {{-- Pembiasaan Pagi --}}
                                        <th colspan="3" class="table-secondary">Pembiasaan Pagi</th>

                                        {{-- Keterangan --}}
                                        <th rowspan="3" class="align-middle text-center" width="100">Ket.</th>
                                    </tr>

                                    {{-- Row 2: sub-groups --}}
                                    <tr>
                                        {{-- Formatif sub --}}
                                        <th class="table-primary" width="60">LKPD</th>
                                        <th class="table-primary" width="60">Diskusi</th>
                                        <th class="table-primary" width="60">Kuis</th>
                                        <th class="table-primary" width="60">Antarteman</th>

                                        {{-- Sumatif sub --}}
                                        <th class="table-info" width="70">STS</th>
                                        <th class="table-info" width="70">SAS</th>

                                        {{-- NR sub --}}
                                        <th class="table-success" width="60">RS</th>
                                        <th class="table-success" width="60">RSA</th>
                                        <th class="table-success" width="60">NR Murni</th>
                                        <th class="table-success" width="70">NR Final</th>

                                        {{-- Penghargaan sub (skala 1-5) --}}
                                        <th class="table-warning" width="55">Jujur</th>
                                        <th class="table-warning" width="55">Disiplin</th>
                                        <th class="table-warning" width="55">Peduli</th>
                                        <th class="table-warning" width="55">Adab</th>
                                        <th class="table-warning" width="55">Kehadiran</th>
                                        <th class="table-warning" width="55">Keaktifan</th>

                                        {{-- Pembiasaan sub --}}
                                        <th class="table-secondary" width="60">Do'a</th>
                                        <th class="table-secondary" width="60">Hiwar</th>
                                        <th class="table-secondary" width="60">Conversation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($students as $i => $history)
                                        @php
                                            $student = $history->student;
                                            $sumatif = $sumatifMap[$student->id] ?? null;
                                            $formatif = $formatifMap[$student->id] ?? null;
                                            $phg = $penghargaanMap[$student->id] ?? null;
                                            $pemb = $pembiasaanMap[$student->id] ?? null;
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                            <td>{{ $student->name }}</td>

                                            {{-- Formatif --}}
                                            <td><input type="number" class="form-control form-control-sm text-center" name="formatif[{{ $student->id }}][skor_lkpd]" value="{{ $formatif?->skor_lkpd ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="formatif[{{ $student->id }}][skor_diskusi]" value="{{ $formatif?->skor_diskusi ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="formatif[{{ $student->id }}][skor_kuis]" value="{{ $formatif?->skor_kuis ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="formatif[{{ $student->id }}][skor_antarteman]" value="{{ $formatif?->skor_antarteman ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>

                                            {{-- Sumatif (STS read-only, SAS input) --}}
                                            <td class="text-center bg-info-subtle">{{ $sumatif?->sts ?? '-' }}</td>
                                            <td><input type="number" class="form-control form-control-sm text-center bg-warning-subtle" name="sumatif[{{ $student->id }}][sas]" value="{{ $sumatif?->sas ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>

                                            {{-- NR (read-only, calculated) --}}
                                            <td class="text-center bg-success-subtle">{{ $sumatif?->rs ?? '-' }}</td>
                                            <td class="text-center bg-success-subtle">{{ $sumatif?->rsa ?? '-' }}</td>
                                            <td class="text-center bg-success-subtle fw-semibold">{{ $sumatif?->nr_murni ?? '-' }}</td>
                                            <td><input type="number" class="form-control form-control-sm text-center bg-success-subtle" name="sumatif[{{ $student->id }}][nr_final]" value="{{ $sumatif?->nr_final ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>

                                            {{-- Penghargaan Akademik (skala 1-5) --}}
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][jujur]" value="{{ $phg?->jujur ?? '' }}" min="1" max="5" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][disiplin]" value="{{ $phg?->disiplin ?? '' }}" min="1" max="5" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][peduli]" value="{{ $phg?->peduli ?? '' }}" min="1" max="5" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][adab]" value="{{ $phg?->adab ?? '' }}" min="1" max="5" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][kehadiran]" value="{{ $phg?->kehadiran ?? '' }}" min="1" max="5" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="penghargaan[{{ $student->id }}][keaktifan]" value="{{ $phg?->keaktifan ?? '' }}" min="1" max="5" placeholder="-"></td>

                                            {{-- Pembiasaan Pagi --}}
                                            <td><input type="number" class="form-control form-control-sm text-center" name="pembiasaan[{{ $student->id }}][skor_doa]" value="{{ $pemb?->skor_doa ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="pembiasaan[{{ $student->id }}][skor_hiwar]" value="{{ $pemb?->skor_hiwar ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>
                                            <td><input type="number" class="form-control form-control-sm text-center" name="pembiasaan[{{ $student->id }}][skor_conversation]" value="{{ $pemb?->skor_conversation ?? '' }}" min="0" max="100" step="0.01" placeholder="-"></td>

                                            {{-- Keterangan --}}
                                            <td><input type="text" class="form-control form-control-sm" name="sumatif[{{ $student->id }}][ket]" value="{{ $sumatif?->ket ?? '' }}" placeholder="-"></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="26" class="text-center text-muted py-4">
                                                <i class="ri-group-line me-1"></i>Tidak ada siswa di kelas ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer border-top-dashed">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}"
                               class="btn btn-secondary">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Semua Nilai
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
// Auto-calculate RSA when SAS changes
document.querySelectorAll('input[name^="sumatif"]').forEach(function(input) {
    if (input.name.includes('[sas]')) {
        input.addEventListener('input', recalcRsa);
    }
});

function recalcRsa() {
    // STS is displayed as read-only in bg-info-subtle cell
    // RSA = (STS + SAS) / 2 — handled server-side, shown from DB
}
</script>
@endsection
