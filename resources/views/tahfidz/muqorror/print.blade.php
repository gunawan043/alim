@extends('layouts.print')
@section('title', 'Muqorror Tahfizh')

@section('content')
<style>
    @page { size: A4 landscape; margin: 1.2cm; }
    body { font-family: 'Times New Roman', serif; font-size: 10px; color: #000; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    h3 { text-align: center; font-size: 12px; margin: 0 0 10px; font-weight: normal; }
    .info { margin-bottom: 8px; font-size: 10px; }
    .info span { display: inline-block; margin-right: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: .5px solid #000; padding: 3px 4px; vertical-align: middle; }
    th { background: #eee; text-align: center; font-size: 9px; }
    .center { text-align: center; }
    .small { font-size: 8.5px; }
    .sign { margin-top: 18px; width: 100%; }
    .sign td { border: 0; text-align: center; font-size: 10px; }
</style>

<h2>Muqorror Tahfizh Al-Qur’an</h2>
<h3>{{ $plan->title }}</h3>

<div class="info">
    <span><strong>Jenjang:</strong> {{ strtoupper($plan->jenjang ?? '-') }}</span>
    <span><strong>Kelas:</strong> {{ $plan->gradeLevel?->name ?? '-' }}</span>
    <span><strong>Paket:</strong> {{ $plan->package?->name ?? '-' }}</span>
    <span><strong>Tahun:</strong> {{ $plan->academicYear?->name ?? '-' }} ({{ ucfirst($plan->semester) }})</span>
    <span><strong>Sekolah:</strong> {{ $plan->school?->name ?? 'Departemen / semua sekolah' }}</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px">No</th><th style="width:70px">Bulan</th><th>Target Hafalan</th><th>Halaman</th><th>Baris/Bulan</th><th>Baris/Hari</th><th>Hari Aktif</th><th>Dari</th><th>Sampai</th><th>STS/SAS</th><th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($plan->months as $i => $month)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ ['','Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'][$month->month] ?? $month->month }}</td>
                <td class="center">{{ $month->target_juz_start ? 'Juz '.$month->target_juz_start : '' }}{{ $month->target_juz_end ? ' – '.$month->target_juz_end : '' }}</td>
                <td class="center">{{ $month->target_pages ?? '' }}</td>
                <td class="center">{{ $month->target_lines_month ?? '' }}</td>
                <td class="center">{{ $month->target_lines_day ?? '' }}</td>
                <td class="center">{{ $month->active_days ?? '' }}</td>
                <td class="center">{{ $month->from_surah_name }}{{ $month->from_ayat ? ':'.$month->from_ayat : '' }}</td>
                <td class="center">{{ $month->to_surah_name }}{{ $month->to_ayat ? ':'.$month->to_ayat : '' }}</td>
                <td class="center">{{ $month->exam_type ? strtoupper($month->exam_type) : '' }}</td>
                <td class="small">{{ $month->notes }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="sign">
    <tr>
        <td style="width:50%">Mengetahui,<br>Kepala Departemen Tahfidz<br><br><br><br>________________________</td>
        <td style="width:50%">Disusun oleh,<br>Tim Kurikulum Tahfidz<br><br><br><br>________________________</td>
    </tr>
</table>
@endsection
