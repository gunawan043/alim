@extends('layouts.master')

@section('title', 'Setoran Mufradat')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $period->id]) }}">{{ $period->title }}</a> @endslot
        @slot('title') Daftar Hadir Setoran @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger"><strong>Periksa input:</strong> {{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Jadwal</label>
                    <select name="schedule_id" class="form-select">
                        <option value="">— Pilih jadwal —</option>
                        @foreach($period->schedules as $schedule)
                            <option value="{{ $schedule->id }}" @selected((string) $scheduleId === (string) $schedule->id)>{{ $schedule->day_label }} · {{ $schedule->time_start ? substr($schedule->time_start, 0, 5) : '-' }} · {{ $schedule->place ?: 'Tempat belum diisi' }}</option>
                        @endforeach
                    </select>
                </div>
                <x-bahasa-filter-fields
                    :schools="$schools"
                    :grade-levels="$gradeLevels"
                    :study-groups="$studyGroups"
                    :show-school="$showSchool"
                    :school-id="$schoolId"
                    :grade-level-id="$gradeLevelId"
                    :study-group-id="$studyGroupId"
                />
                <div class="col-md-2">
                    <button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('user.bahasa.mufradat.attendance.store', ['userId' => $userId, 'period' => $period->id]) }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="schedule_id" value="{{ $scheduleId }}">

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-end gap-2">
                <div>
                    <h5 class="card-title mb-1">Peserta Kegiatan Hafalan Mufradat</h5>
                    <div class="lang-muted">{{ $period->title }} · {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · {{ $students->count() }} santri</div>
                </div>
                <div class="d-flex gap-2">
                    <select name="mustami_id" class="form-select form-select-sm" style="min-width:220px">
                        <option value="">— Mustami —</option>
                        @foreach($mustamiOptions as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('user.bahasa.mufradat.print-attendance', ['userId' => $userId, 'period' => $period->id]) }}" target="_blank" class="btn btn-sm btn-light"><i class="ri-printer-line"></i></a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45px">Hadir</th>
                            <th>Nama Santri</th>
                            <th>Kelas</th>
                            <th style="width:130px">Jumlah Kata</th>
                            <th style="width:130px">Benar</th>
                            <th style="width:110px">Nilai</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $index => $student)
                            @php
                                $current = $existing->get($student->id);
                                $score = $current?->score;
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="hidden" name="rows[{{ $index }}][student_id]" value="{{ $student->id }}">
                                    <input type="checkbox" class="form-check-input row-present" name="rows[{{ $index }}][present]" value="1" @checked($current)>
                                </td>
                                <td class="fw-semibold">{{ $student->name }}</td>
                                <td class="lang-muted">{{ $student->currentClassHistory?->studyGroup?->name ?? '-' }}</td>
                                <td><input type="number" min="0" name="rows[{{ $index }}][total_words]" value="{{ $current?->total_words ?? $period->vocabularies->count() }}" class="form-control form-control-sm row-total"></td>
                                <td><input type="number" min="0" name="rows[{{ $index }}][correct_words]" value="{{ $current?->correct_words }}" class="form-control form-control-sm row-correct"></td>
                                <td class="text-center fw-semibold row-score">{{ $score !== null ? number_format((float) $score, 1, ',', '.') . '%' : '-' }}</td>
                                <td><input name="rows[{{ $index }}][notes]" value="{{ $current?->notes }}" class="form-control form-control-sm" placeholder="Opsional"></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada santri pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="lang-muted"><i class="ri-information-line me-1"></i>Centang santri yang menyetor. Nilai ≥80% otomatis Lulus; di bawahnya Ulang.</div>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Setoran</button>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.querySelectorAll('.row-correct, .row-total').forEach(function (input) {
    input.addEventListener('input', function () {
        const row = this.closest('tr');
        const total = parseFloat(row.querySelector('.row-total')?.value || 0);
        const correct = parseFloat(row.querySelector('.row-correct')?.value || 0);
        const score = total > 0 ? (correct / total) * 100 : 0;
        row.querySelector('.row-score').textContent = total > 0 ? score.toFixed(1).replace('.', ',') + '%' : '-';
    });
});
document.querySelectorAll('.row-present').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        const row = this.closest('tr');
        row.querySelectorAll('input[type="number"], .row-correct, .row-total, input[name$="[notes]"]').forEach(function (input) {
            input.disabled = !checkbox.checked;
        });
    });
    checkbox.dispatchEvent(new Event('change'));
});
</script>
@endsection
