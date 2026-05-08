@extends('layouts.print')
@section('title') SK {{ $decree->decree_number }} @endsection

@section('content')
<style>
    @page { margin: 1.5cm 1.5cm 1.5cm 2cm; size: A4 landscape; }
    body { font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; padding: 0; }
    h1 { font-size: 13px; font-weight: bold; text-decoration: underline; margin: 0 0 6px 0; text-align: center; }
    .info-line { font-size: 11px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { border: 0.5px solid #000; padding: 3px 4px; vertical-align: middle; }
    th { background: #eee; font-weight: bold; text-align: center; }
    .fw-bold { font-weight: bold; }
    .sign-area { margin-top: 20px; text-align: right; width: 220px; float: right; }
</style>

<h1>LAMPIRAN I. SURAT KEPUTUSAN KEPALA {{ strtoupper($decree->school?->name ?? 'SEKOLAH') }}</h1>
<div class="info-line">
    <strong>NOMOR&nbsp;&nbsp;&nbsp;&nbsp;:</strong> {{ $decree->decree_number }}<br>
    <strong>TANGGAL :</strong> {{ $decree->issued_date?->translatedFormat('d F Y') ?? '-' }}<br>
    <strong>TENTANG :</strong> {{ strtoupper($decree->title) }}
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" style="width:22px;">No</th>
            <th rowspan="2" style="width:130px;">Nama / Mapel / Tugas Tambahan</th>
            @foreach($sortedGrades as $level => $groups)
                <th colspan="{{ $groups->count() }}" class="text-center fw-bold"
                    style="border-bottom:0.5px solid #000;">
                    {{ $groups->first()->gradeLevel->name ?? "Kelas $level" }}
                </th>
            @endforeach
            <th rowspan="2" class="text-center" style="width:40px;">Seb.<br>Jam</th>
            <th rowspan="2" class="text-center" style="width:50px;">Tugas<br>Lain2</th>
            <th rowspan="2" class="text-center" style="width:35px;">Jml<br>Jam</th>
        </tr>
        <tr>
            @foreach($sortedGrades as $level => $groups)
                @foreach($groups as $sg)
                    <th class="text-center" style="width:26px;">{{ $sg->name }}</th>
                @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $teacherCount = 0;
            $lastTeacherId = null;
        @endphp
        @forelse($teacherRows as $row)
            @php
                $isFirst = $row['teacher']->id !== $lastTeacherId;
                if ($isFirst) $teacherCount++;
                $lastTeacherId = $row['teacher']->id;
                $guruTasks = ($otherTeacherTasks ?? collect())->get($row['teacher']->id, collect());
                $taskHoursTotal = $guruTasks->sum('weekly_hours');
            @endphp
            <tr{{ $isFirst ? ' style="font-weight:bold;"' : '' }}>
                <td class="text-center" style="width:22px;">@if($isFirst) {{ $teacherCount }} @endif</td>
                <td{{ $isFirst ? ' style="background:#f4f4f4;"' : ' style="padding-left:12px;"' }}>
                    @if($isFirst)
                        {{ $row['teacher']->name }}
                        <br><span style="font-weight:normal; font-size:9px;">{{ $row['teacher']->getRoleNames()->first() ?? '' }}</span>
                    @endif
                    <div style="{{ !$isFirst ? 'padding-left:12px;' : 'margin-top:1px; font-size:10px;' }}">
                        {{ $row['subject']?->name ?? '-' }}
                    </div>
                </td>
                @foreach($sortedGrades as $level => $groups)
                    @foreach($groups as $sg)
                        <td class="text-center">
                            @if(isset($row['hours'][$sg->id]) && $row['hours'][$sg->id])
                                {{ $row['hours'][$sg->id] }}
                            @endif
                        </td>
                    @endforeach
                @endforeach
                <td class="text-center fw-bold bg-success-subtle">{{ $row['teachingHours'] }}</td>
                <td class="text-center" style="font-size:9px; line-height:1.4;">
                    @forelse($guruTasks as $ott)
                        <div>{{ $ott->task_name }} ({{ $ott->weekly_hours }}JP)</div>
                    @empty — @endforelse
                </td>
                <td class="text-center fw-bold bg-primary-subtle">{{ $row['totalHours'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 2 + $studyGroups->count() + 3 }}" class="text-center">Tidak ada data penugasan.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="sign-area">
    <div style="font-size:10px;">Mataram, {{ $decree->issued_date?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
    <div style="height:40px;"></div>
    <div class="fw-bold">{{ $decree->signer?->name ?? '____________________' }}</div>
    <div style="font-size:9px;">{{ $decree->signed_position ?? '' }}</div>
</div>
@endsection