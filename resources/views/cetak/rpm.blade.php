@extends('cetak.layout')

@php
    $isAgama = ($formatType ?? (($perangkat->tipe ?? 'umum') === 'agama' ? 'agama' : 'umum')) === 'agama';
    $printVersion = $printVersion ?? 'pondok';
    $docTitle = $printVersion === 'kemenag' || ($printVersion === 'pondok' && $isAgama)
        ? 'RENCANA PEMBELAJARAN MENDALAM'
        : 'PERENCANAAN PEMBELAJARAN MENDALAM';
    $atp = $perangkat->atp;
    $tps = $atp?->items?->map(fn ($item) => $item->tujuanPembelajaran)->filter() ?? collect();
    $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
    $alokasiWaktu = $perangkat->alokasiWaktu();
    $matrix = $perangkat->assessmentMatrix();
    $text = fn ($value) => filled($value) ? nl2br(e($value)) : '<span class="muted">—</span>';
    $gradeLabel = $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas';
    $fase = $perangkat->gradeLevel?->fase;
    $nilai = fn (string $key) => $perangkat->desainValue($key);
    $useDigitalSignature = $useDigitalSignature ?? false;
    $ttdKspBase64 = $ttdKspBase64 ?? null;
    $stampBase64 = $stampBase64 ?? null;
@endphp

@section('title', $docTitle)
@section('doc_title', $docTitle)
@section('doc_subtitle', '')

@section('content')
    {{-- A. INFORMASI UMUM --}}
    <div class="section-title">A. Informasi Umum</div>
    <div class="section-body">
        <table class="info">
            <tr>
                <td class="label">Nama Guru</td><td class="colon">:</td><td>{{ $perangkat->teacher?->name ?? '—' }}</td>
                <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">{{ $isAgama ? 'Kelas' : 'Kelas / Fase' }}</td><td class="colon">:</td>
                <td>{{ $gradeLabel }}{{ ! $isAgama && $fase ? ' / '.$fase : '' }}</td>
                <td class="label">Mata Pelajaran / Semester</td><td class="colon">:</td>
                <td>{{ $perangkat->subject?->name ?? '—' }} / {{ ucfirst($perangkat->semester) }}</td>
            </tr>
            @if($isAgama)
                <tr>
                    <td class="label">Materi Pelajaran</td><td class="colon">:</td>
                    <td colspan="3">{!! $text($nilai('identifikasi_materi')) !!}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Alokasi Waktu</td><td class="colon">:</td><td>{{ $alokasiWaktu }}</td>
                <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $perangkat->academicYear?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- B. IDENTIFIKASI (umum) --}}
    @if(! $isAgama)
        <div class="section-title">B. Identifikasi</div>
        <div class="section-body">
            <table class="inside">
                <tr><td class="label">Peserta Didik</td><td>{!! $text($nilai('identifikasi_peserta_didik')) !!}</td></tr>
                <tr><td class="label">Materi Pelajaran</td><td>{!! $text($nilai('identifikasi_materi')) !!}</td></tr>
                <tr><td class="label">Profil Lulusan</td><td>{!! $text($nilai('profil_lulusan')) !!}</td></tr>
            </table>
        </div>
    @endif

    {{-- C. DESAIN PEMBELAJARAN --}}
    <div class="section-title">{{ $isAgama ? 'B. Desain Pembelajaran' : 'C. Desain Pembelajaran' }}</div>
    <div class="section-body">
        @if(! $isAgama)
            <table class="inside">
                <tr>
                    <td class="label">Capaian Pembelajaran</td>
                    <td>
                        @forelse($cps as $cp)
                            <p><strong>[Fase {{ $cp->fase }}{{ $cp->elemen ? ' — '.$cp->elemen : '' }}]</strong> {{ $cp->deskripsi }}</p>
                        @empty
                            <span class="muted">—</span>
                        @endforelse
                    </td>
                </tr>
                <tr><td class="label">Topik Pembelajaran</td><td>{!! $text($nilai('topik')) !!}</td></tr>
                <tr><td class="label">Lintas Disiplin Ilmu</td><td>{!! $text($nilai('lintas_disiplin')) !!}</td></tr>
            </table>
        @endif

        <table class="inside">
            <tr>
                <td class="label">Tujuan Pembelajaran</td>
                <td>
                    @forelse($tps as $tp)
                        <p><strong>{{ $tp->kode_tp }}</strong> — {{ $tp->deskripsi }}</p>
                    @empty
                        <span class="muted">Belum ada TP dari ATP.</span>
                    @endforelse
                </td>
            </tr>
        </table>
    </div>

    {{-- D. KERANGKA PEMBELAJARAN --}}
    <div class="section-title">{{ $isAgama ? 'C. Kerangka Pembelajaran' : 'D. Kerangka Pembelajaran' }}</div>
    <div class="section-body">
        <table class="inside">
            @if($isAgama)
                <tr><td class="label">Model Pembelajaran</td><td>{!! $text($nilai('model_pembelajaran')) !!}</td></tr>
            @else
                <tr><td class="label">Praktik Pedagogik</td><td>{!! $text($nilai('praktik_pedagogik')) !!}</td></tr>
            @endif
            <tr><td class="label">Kemitraan Pembelajaran</td><td>{!! $text($nilai('kemitraan_pembelajaran')) !!}</td></tr>
            <tr><td class="label">Lingkungan Pembelajaran</td><td>{!! $text($nilai('lingkungan_pembelajaran')) !!}</td></tr>
            <tr><td class="label">Pemanfaatan Digital</td><td>{!! $text($nilai('pemanfaatan_digital')) !!}</td></tr>
        </table>
    </div>

    {{-- E. PENGALAMAN BELAJAR --}}
    <div class="section-title">{{ $isAgama ? 'D. Pengalaman Belajar' : 'E. Pengalaman Belajar' }}</div>
    <div class="section-body">
        <table class="data">
            <thead>
                <tr><th style="width:24%">Tahap</th><th>Aktivitas Pembelajaran</th></tr>
            </thead>
            <tbody>
                <tr><td>Kegiatan Awal</td><td>{!! $text($nilai('kegiatan_awal')) !!}</td></tr>
                <tr>
                    <td rowspan="3">Kegiatan Inti</td>
                    <td><strong>Memahami</strong><br>{!! $text($nilai('pengalaman_memahami')) !!}</td>
                </tr>
                <tr><td><strong>Mengaplikasikan</strong><br>{!! $text($nilai('pengalaman_mengaplikasi')) !!}</td></tr>
                <tr><td><strong>Merefleksikan</strong><br>{!! $text($nilai('pengalaman_refleksi')) !!}</td></tr>
                <tr><td>Penutup</td><td>{!! $text($nilai('penutup')) !!}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- F. ASESMEN --}}
    <div class="section-title">{{ $isAgama ? 'E. Asesmen Formatif dan Sumatif' : 'F. Asesmen Formatif dan Sumatif' }}</div>
    <div class="section-body">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:18%">Jenis Asesmen</th>
                    <th style="width:20%">Teknik</th>
                    <th style="width:28%">Indikator</th>
                    <th>Instrumen/Bukti</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_ROWS as $rowKey => $rowLabel)
                    <tr>
                        <td>{{ $matrix[$rowKey]['jenis'] ?: $rowLabel }}</td>
                        @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_COLUMNS as $columnKey => $columnLabel)
                            @continue($columnKey === 'jenis')
                            <td>{!! $text($matrix[$rowKey][$columnKey] ?? '') !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- INTEGRASI KBC (profil Kemenag) --}}
    @if($curriculumProfile?->isKemenag())
        @php
            $kbcFilled = collect(\App\Models\PerangkatPembelajaran::KBC_FIELDS)
                ->filter(fn ($label, $key) => filled($nilai($key)));
        @endphp
        @if($kbcFilled->isNotEmpty())
            <div class="section-title">Integrasi Kurikulum Berbasis Cinta (KBC)</div>
            <div class="section-body">
                <table class="inside">
                    @foreach($kbcFilled as $key => $label)
                        <tr><td class="label">{{ $label }}</td><td>{!! $text($nilai($key)) !!}</td></tr>
                    @endforeach
                </table>
            </div>
        @endif
    @endif

    {{-- PROJEK P5 / P5RA --}}
    @if(($kokurikulerProjects ?? collect())->isNotEmpty())
        <div class="section-title">Projek {{ $curriculumProfile?->label('kokurikuler_label', 'P5 / P5RA') }}</div>
        <div class="section-body">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:30px">No</th>
                        <th>Judul Projek</th>
                        <th style="width:150px">Tema</th>
                        <th style="width:160px">Dimensi / Nilai</th>
                        <th style="width:130px">Pelaksanaan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kokurikulerProjects as $index => $project)
                        @php
                            $projectDimensions = collect($project->dimensions ?? [])
                                ->map(fn ($key) => $curriculumProfile?->values()[$key] ?? $key)
                                ->filter()
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td><strong>{{ $project->title }}</strong><br><span class="small">{{ $project->description }}</span></td>
                            <td>{{ $project->theme ?: '—' }}</td>
                            <td class="small">{{ $projectDimensions ?: '—' }}</td>
                            <td class="small">
                                {{ $project->start_date?->format('d/m/Y') ?? '—' }}
                                @if($project->end_date) – {{ $project->end_date->format('d/m/Y') }} @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- TANDA TANGAN --}}
    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                @if($useDigitalSignature && $ttdKspBase64)
                    <div class="signature-box">
                        @if($stampBase64)
                            <img src="{{ $stampBase64 }}" class="stamp-img" alt="Stempel">
                        @endif
                        <img src="{{ $ttdKspBase64 }}" class="ttd-img" alt="TTD KSP">
                    </div>
                @else
                    <div class="space"></div>
                    <div class="name">__________________________</div>
                @endif
            </td>
            <td style="width:50%">
                {{ $school?->city?->name ?? 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
                Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">__________________________</div>
            </td>
        </tr>
    </table>
@endsection
