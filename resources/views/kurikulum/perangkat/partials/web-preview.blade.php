@php
    $formatLabel = match ($printVersion ?? 'pondok') {
        'kbc' => 'KBC',
        'merdeka' => 'Merdeka',
        default => 'Pondok',
    };
    $sections = \App\Models\PerangkatPembelajaran::SECTIONS_RPM;
    $nilai = fn (string $key) => $perangkat->desainValue($key);
@endphp

<div class="card h-100">
    <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="card-title mb-1"><i class="ri-layout-masonry-line text-primary me-1"></i>Preview Format Website</h6>
            <p class="text-muted mb-0 small">Tampilan isi perangkat dalam format {{ $formatLabel }}.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary">{{ $formatLabel }}</span>
    </div>
    <div class="card-body" style="max-height:680px;overflow:auto;">
        <div class="border rounded p-3 mb-3 bg-light-subtle">
            <h5 class="mb-1">{{ $perangkat->judul }}</h5>
            <div class="small text-muted">
                {{ $perangkat->subject?->name ?? '-' }}
                · {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}
                · {{ $perangkat->academicYear?->name ?? '-' }}
                · Semester {{ ucfirst($perangkat->semester) }}
            </div>
            <div class="small text-muted mt-1">
                Guru: {{ $perangkat->teacher?->name ?? '—' }} · Alokasi: {{ $perangkat->alokasiWaktu() }}
            </div>
        </div>

        <h6 class="fw-semibold border-bottom pb-1">Informasi Umum</h6>
        <table class="table table-sm table-borderless mb-3">
            <tr><td class="text-muted" style="width:38%">Nama Guru</td><td>{{ $perangkat->teacher?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted">Satuan Pendidikan</td><td>{{ $perangkat->school?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted">Kelas / Fase</td><td>{{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}{{ $perangkat->gradeLevel?->fase ? ' / '.$perangkat->gradeLevel->fase : '' }}</td></tr>
            <tr><td class="text-muted">Mata Pelajaran</td><td>{{ $perangkat->subject?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted">Semester / Tahun Ajaran</td><td>{{ ucfirst($perangkat->semester) }} / {{ $perangkat->academicYear?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted">Alokasi Waktu</td><td>{{ $perangkat->alokasiWaktu() }}</td></tr>
        </table>

        @foreach($perangkat->groups() as $groupKey => $group)
            @continue($groupKey === 'asesmen')
            <h6 class="fw-semibold border-bottom pb-1 mt-3">{{ $group['label'] }}</h6>
            <table class="table table-sm table-borderless mb-2">
                @foreach($group['keys'] as $key)
                    @php $value = $nilai($key); @endphp
                    <tr>
                        <td class="text-muted" style="width:38%">{{ $sections[$key] ?? $key }}</td>
                        <td>{!! filled($value) ? nl2br(e($value)) : '<span class="text-muted">—</span>' !!}</td>
                    </tr>
                @endforeach
            </table>
        @endforeach

        <h6 class="fw-semibold border-bottom pb-1 mt-3">Asesmen Formatif dan Sumatif</h6>
        <table class="table table-sm table-bordered mb-3">
            <thead class="table-light"><tr><th>Jenis</th><th>Teknik</th><th>Indikator</th><th>Instrumen/Bukti</th></tr></thead>
            <tbody>
                @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_ROWS as $rowKey => $rowLabel)
                    <tr>
                        <td>{{ $perangkat->assessmentMatrix()[$rowKey]['jenis'] ?: $rowLabel }}</td>
                        @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_COLUMNS as $columnKey => $columnLabel)
                            @continue($columnKey === 'jenis')
                            <td>{{ $perangkat->assessmentMatrix()[$rowKey][$columnKey] ?: '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="alert alert-light border mb-0 small">
            <i class="ri-information-line me-1"></i>
            Ini preview isi. Gunakan tombol cetak untuk menghasilkan PDF sesuai format {{ $formatLabel }}.
        </div>
    </div>
</div>
