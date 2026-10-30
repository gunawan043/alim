@extends('cetak.layout')

@section('title', 'Laporan Projek')
@section('doc_title', 'Laporan Projek ' . $project->type_label)
@section('doc_subtitle')
    {{ $project->title }} — {{ $project->academicYear?->name ?? '-' }} — Semester {{ ucfirst($project->semester) }}
@endsection

@section('content')
    <style>
        @media print { @page { size: A4 landscape; margin: 8mm 10mm; } }
        .rubric-note { font-size: 8.5px; color: #444; }
    </style>

    <table class="info">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '-' }}</td>
            <td class="label">Jenis Projek</td><td class="colon">:</td><td>{{ $project->type_label }}</td>
        </tr>
        <tr>
            <td class="label">Judul Projek</td><td class="colon">:</td><td>{{ $project->title }}</td>
            <td class="label">Tema</td><td class="colon">:</td><td>{{ $project->theme ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Profil Kurikulum</td><td class="colon">:</td><td>{{ $profile?->name ?? '—' }}</td>
            <td class="label">Status</td><td class="colon">:</td><td>{{ $project->status_label }}</td>
        </tr>
        <tr>
            <td class="label">Pelaksanaan</td><td class="colon">:</td><td>{{ $project->start_date?->format('d/m/Y') ?? '—' }} – {{ $project->end_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="label">Rata-rata</td><td class="colon">:</td><td>{{ $average ? number_format((float) $average, 2) : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Koordinator</td><td class="colon">:</td><td>{{ $project->coordinator?->name ?? '—' }}</td>
            <td class="label">Jumlah Dinilai</td><td class="colon">:</td><td>{{ $assessments->count() }} santri</td>
        </tr>
    </table>

    @if($project->description)
        <div class="section-title">Deskripsi Projek</div>
        <div class="section-body"><p>{{ $project->description }}</p></div>
    @endif

    @if(! empty($project->activities))
        <div class="section-title">Aktivitas Projek</div>
        <div class="section-body">
            <ol style="margin:0;padding-left:16px">
                @foreach($project->activities as $activity)
                    <li style="margin-bottom:3px">
                        <strong>{{ $activity['judul'] ?? 'Aktivitas' }}</strong>
                        @if($activity['tanggal'] ?? null) — {{ \Illuminate\Support\Carbon::parse($activity['tanggal'])->format('d/m/Y') }} @endif
                        @if($activity['deskripsi'] ?? null)<br><span class="muted">{{ $activity['deskripsi'] }}</span>@endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    <div class="section-title">Rekap Penilaian</div>
    <div class="section-body" style="padding:0;border:none">
        <table class="data" style="margin-bottom:6px">
            <thead>
                <tr>
                    <th style="width:25px">No</th>
                    <th style="width:120px">Nama Santri</th>
                    @foreach($dimensions as $label)<th>{{ $label }}</th>@endforeach
                    <th style="width:55px">Nilai</th>
                    <th style="width:90px">Predikat</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $index => $assessment)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $assessment->student?->name ?? '—' }}<br><span class="muted">{{ $assessment->student?->nis }}</span></td>
                        @foreach($dimensions as $key => $label)
                            <td class="center">{{ data_get($assessment->scores, $key, '—') }}</td>
                        @endforeach
                        <td class="center"><strong>{{ number_format((float) $assessment->average, 2) }}</strong></td>
                        <td class="center">{{ $assessment->predicate ?? '—' }}</td>
                        <td class="small">
                            {{ $assessment->notes ?: '—' }}
                            @if($assessment->evidence_note)<br><span class="muted">Bukti: {{ $assessment->evidence_note }}</span>@endif
                            @if($assessment->evidence_url)<br><span class="muted">{{ $assessment->evidence_url }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 4 + count($dimensions) }}" class="center muted">Belum ada penilaian.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="rubric-note">
            Rubrik: 1 = Belum Berkembang, 2 = Mulai Berkembang, 3 = Berkembang Baik, 4 = Sangat Berkembang.
        </div>
    </div>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_full_name ?? '................................' }}</div>
                @if(($profile ?? null)?->isKemenag() && $school?->principal_nupy_display)<div class="nip">NUPY. {{ $school->principal_nupy_display }}</div>
                @elseif($school?->principal_nupy_display)<div class="nip">NUPY. {{ $school->principal_nupy_display }}</div>@endif
            </td>
            <td style="width:50%">
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Koordinator Projek
                <div class="space"></div>
                <div class="name">{{ $project->coordinator?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
