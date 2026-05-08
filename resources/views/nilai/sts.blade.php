@extends('layouts.master')
@section('title') Input Nilai STS — {{ $adminBook->subject->name ?? '' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">Data Nilai</a> @endslot
        @slot('title') Nilai STS — {{ $adminBook->studyGroup->name ?? '' }} / {{ $adminBook->subject->name ?? '' }}</title>
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
                            <a href="{{ route('user.schools.nilai.sas', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}"
                               class="btn btn-primary mt-3">
                                <i class="ri-arrow-right-line me-1"></i> Lanjut ke SAS
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Input Nilai STS --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Penilaian Asesmen Sumatif — Sumatif Tengah Semester (STS)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <form method="POST" action="{{ route('user.schools.nilai.sts.store', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" id="formSts">
                            @csrf
                            <table class="table table-bordered table-nowrap align-middle mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th rowspan="2" class="text-center align-middle" width="40">No</th>
                                        <th rowspan="2" class="text-center align-middle" width="60">NIS</th>
                                        <th rowspan="2" class="text-center align-middle" width="220">Nama Siswa</th>
                                        <th colspan="6">Asesmen Sumatif Harian</th>
                                        <th rowspan="2" class="text-center align-middle bg-primary-subtle" width="70">
                                            RS<br><small class="fw-normal">(50%)</small>
                                        </th>
                                        <th rowspan="2" class="text-center align-middle bg-warning-subtle" width="80">
                                            STS<br><small class="fw-normal">(25%)</small>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="text-center" width="60">S1</th>
                                        <th class="text-center" width="60">S2</th>
                                        <th class="text-center" width="60">S3</th>
                                        <th class="text-center" width="60">S4</th>
                                        <th class="text-center" width="60">S5</th>
                                        <th class="text-center" width="60">S6</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($students as $i => $history)
                                        @php $student = $history->student; @endphp
                                        @php $existing = $nilaiMap[$student->id] ?? null; @endphp
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                            <td>{{ $student->name }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s1]"
                                                       value="{{ $existing?->s1 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s2]"
                                                       value="{{ $existing?->s2 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s3]"
                                                       value="{{ $existing?->s3 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s4]"
                                                       value="{{ $existing?->s4 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s5]"
                                                       value="{{ $existing?->s5 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center sumatif-input"
                                                       name="nilai[{{ $student->id }}][s6]"
                                                       value="{{ $existing?->s6 ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                            <td class="text-center bg-primary-subtle fw-semibold rs-cell">-</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center bg-warning-subtle"
                                                       name="nilai[{{ $student->id }}][sts]"
                                                       value="{{ $existing?->sts ?? '' }}"
                                                       min="0" max="100" step="0.01"
                                                       placeholder="0">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center text-muted py-4">
                                                <i class="ri-group-line me-1"></i>Tidak ada siswa di kelas ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
                <div class="card-footer border-top-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}"
                           class="btn btn-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        <button type="submit" form="formSts" class="btn btn-success">
                            <i class="ri-save-line me-1"></i> Simpan Nilai STS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
// Auto-calculate RS on input change
document.querySelectorAll('.sumatif-input').forEach(function(input) {
    input.addEventListener('input', function() {
        var row = this.closest('tr');
        var inputs = row.querySelectorAll('.sumatif-input');
        var values = [];
        inputs.forEach(function(inp) {
            var v = parseFloat(inp.value);
            if (!isNaN(v) && inp.value !== '') values.push(v);
        });
        var rsCell = row.querySelector('.rs-cell');
        if (values.length > 0) {
            var avg = values.reduce(function(a, b) { return a + b; }, 0) / values.length;
            rsCell.textContent = avg.toFixed(2);
        } else {
            rsCell.textContent = '-';
        }
    });
});
</script>
@endsection
