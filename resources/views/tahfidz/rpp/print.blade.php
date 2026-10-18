@extends('layouts.print')
@section('title', $plan->title)

@section('content')
<style>
    @page { size: A4 portrait; margin: 1.5cm; }
    body { font-family: 'Times New Roman', serif; font-size: 11px; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 4px; text-transform: uppercase; }
    .meta { margin: 0 0 10px; font-size: 10.5px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: .5px solid #000; padding: 4px; vertical-align: top; }
    th { background: #eee; text-align: center; }
    .center { text-align: center; }
    .sign { margin-top: 24px; }
    .sign td { border: 0; text-align: center; }
</style>
<h2>{{ $plan->type === 'iqro' ? 'Rencana Pembelajaran Iqro’' : 'Rencana Pembelajaran Tahfizh Al-Qur’an' }}</h2>
<div class="meta">
    <strong>Kelas:</strong> {{ $plan->gradeLevel?->name ?? '-' }} &nbsp;
    <strong>Semester:</strong> {{ ucfirst($plan->semester) }} &nbsp;
    <strong>TP:</strong> {{ $plan->academicYear?->name ?? '-' }} &nbsp;
    <strong>Bulan KBM:</strong> {{ $plan->kbm_month ?? '-' }}<br>
    <strong>Target Bulanan:</strong> {{ $plan->monthly_target ?? '-' }}
</div>
<table>
    <thead>
        @if($plan->type === 'iqro')
            <tr><th style="width:25px">No</th><th style="width:80px">Bagian</th><th>Kegiatan Guru</th><th>Kegiatan Siswa</th><th style="width:70px">Alokasi</th></tr>
        @else
            <tr><th style="width:50px">Pekan</th><th style="width:70px">Hari</th><th>Target Pekanan</th><th>Target Harian</th><th style="width:140px">Keterangan</th></tr>
        @endif
    </thead>
    <tbody>
        @foreach($plan->items as $i => $item)
            @if($plan->type === 'iqro')
                <tr><td class="center">{{ $i+1 }}</td><td>{{ $item->section }}</td><td>{!! nl2br(e($item->teacher_activity)) !!}</td><td>{!! nl2br(e($item->student_activity)) !!}</td><td class="center">{{ $item->duration_minutes }}</td></tr>
            @else
                <tr><td class="center">{{ $item->week }}</td><td>{{ $item->learning_day }}</td><td>{{ $item->week_target }}</td><td>{!! nl2br(e($item->daily_target)) !!}</td><td>{{ $item->notes }}</td></tr>
            @endif
        @endforeach
    </tbody>
</table>
<table class="sign"><tr><td style="width:50%">Mengetahui,<br>Kepala Satuan Pendidikan<br><br><br>________________________</td><td style="width:50%">Guru Tahfizh,<br><br><br><br>________________________</td></tr></table>
@endsection
