@php
    $formatLabel = match ($printVersion ?? 'pondok') {
        'kbc' => 'KBC',
        'merdeka' => 'Merdeka',
        default => 'Pondok',
    };
    $sections = \App\Models\PerangkatPembelajaran::SECTIONS_RPM;
    $nilai = fn (string $key) => $perangkat->desainValue($key);
    $render = function ($value) {
        if (! filled($value)) return '<span class="text-muted">—</span>';
        $value = (string) $value;
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><blockquote><table><thead><tbody><tr><th><td>';
        return preg_match('/<(p|br|ul|ol|li|strong|em|h[1-6]|blockquote|table)\b/i', $value)
            ? strip_tags($value, $allowed)
            : nl2br(e($value));
    };
@endphp

<div class="card h-100">
    {{-- HEADER --}}
    <div class="card-header border-bottom-dashed">
        <div class="row g-3 align-items-center">
            <div class="col-sm">
                <h5 class="card-title mb-0">
                    <i class="ri-layout-masonry-line text-primary me-1"></i>Preview Format Website
                </h5>
                <p class="text-muted mb-0 small">Tampilan isi perangkat dalam format {{ $formatLabel }}.</p>
            </div>
            <div class="col-sm-auto">
                <div class="toolbar-actions">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="ri-file-text-line me-1"></i>{{ $formatLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- BODY --}}
    <div class="card-body" style="max-height:680px;overflow:auto;">

        {{-- INFO HEADER PERANGKAT (Template PDF: alert-light + d-flex) --}}
        <div class="alert alert-light border py-2 mb-3 small">
            <h6 class="mb-1">{{ $perangkat->judul }}</h6>
            <div class="d-flex flex-wrap gap-3">
                <span><i class="ri-book-2-line me-1 text-primary"></i>{{ $perangkat->subject?->name ?? '—' }}</span>
                <span><i class="ri-group-line me-1 text-primary"></i>{{ $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}</span>
                <span><i class="ri-calendar-line me-1 text-primary"></i>{{ $perangkat->academicYear?->name ?? '—' }}</span>
                <span><i class="ri-calendar-2-line me-1 text-primary"></i>Semester {{ ucfirst($perangkat->semester) }}</span>
            </div>
            <div class="d-flex flex-wrap gap-3 mt-1">
                <span><i class="ri-user-line me-1 text-primary"></i>Guru: {{ $perangkat->teacher?->name ?? '—' }}</span>
                <span><i class="ri-time-line me-1 text-primary"></i>Alokasi: {{ $perangkat->alokasiWaktu() }}</span>
            </div>
        </div>

        {{-- INFORMASI UMUM --}}
        <h6 class="text-uppercase fw-semibold text-muted small mb-2">Informasi Umum</h6>
        <div class="table-container mb-3">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <tbody>
                    <tr>
                        <td class="text-muted small" style="width:38%">Nama Guru</td>
                        <td>{{ $perangkat->teacher?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Satuan Pendidikan</td>
                        <td>{{ $perangkat->school?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kelas / Fase</td>
                        <td>
                            {{ $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}{{ $perangkat->gradeLevel?->fase ? ' / '.$perangkat->gradeLevel->fase : '' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Mata Pelajaran</td>
                        <td>{{ $perangkat->subject?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Semester / Tahun Ajaran</td>
                        <td>{{ ucfirst($perangkat->semester) }} / {{ $perangkat->academicYear?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Alokasi Waktu</td>
                        <td>{{ $perangkat->alokasiWaktu() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- GROUPS --}}
        @foreach($perangkat->groups() as $groupKey => $group)
            @continue($groupKey === 'asesmen')
            <h6 class="text-uppercase fw-semibold text-muted small mb-2 mt-3">{{ $group['label'] }}</h6>
            <div class="table-container mb-3">
                <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                    <tbody>
                        @foreach($group['keys'] as $key)
                            @php $value = $nilai($key); @endphp
                            <tr>
                                <td class="text-muted small" style="width:38%">{{ $sections[$key] ?? $key }}</td>
                                <td class="small">
                                    {!! filled($value) ? nl2br(e($value)) : '<span class="text-muted">—</span>' !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        {{-- ASESMEN --}}
        @if(! empty($legacyFilled))
            <h6 class="fw-semibold border-bottom pb-1 mt-3">Fondasi Pembelajaran Mendalam</h6>
            <table class="table table-sm table-borderless mb-2">
                @foreach($legacyFilled as $key => $label)
                    <tr>
                        <td class="text-muted" style="width:38%">{{ $label }}</td>
                        <td>{!! $render($nilai($key)) !!}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <h6 class="text-uppercase fw-semibold text-muted small mb-2 mt-3">Asesmen Formatif dan Sumatif</h6>
        <div class="table-container mb-3">
            <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:20%">Jenis</th>
                        <th>Teknik</th>
                        <th>Indikator</th>
                        <th>Instrumen / Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_ROWS as $rowKey => $rowLabel)
                        <tr>
                            <td class="text-center fw-semibold">{{ $perangkat->assessmentMatrix()[$rowKey]['jenis'] ?: $rowLabel }}</td>
                            @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_COLUMNS as $columnKey => $columnLabel)
                                @continue($columnKey === 'jenis')
                                <td class="small">{{ $perangkat->assessmentMatrix()[$rowKey][$columnKey] ?: '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ALERT FOOTER --}}
        <div class="alert alert-light border mb-0 small">
            <i class="ri-information-line me-1 text-primary"></i>
            Ini preview isi. Gunakan tombol cetak untuk menghasilkan PDF sesuai format <strong>{{ $formatLabel }}</strong>.
        </div>
    </div>
</div>