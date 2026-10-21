@extends('cetak.layout')
@section('title', 'Berita Acara Praktikum')
@section('doc_title', 'BERITA ACARA PRAKTIKUM')
@section('doc_subtitle') {{ $report->activity?->lab?->name }} @endsection
@section('content')
<p>Kegiatan praktikum ini dilaksanakan pada hari <strong>{{ $report->report_date?->translatedFormat('l') }}</strong> tanggal <strong>{{ $report->report_date?->translatedFormat('d F Y') }}</strong> dengan keterangan sebagai berikut:</p>
<table class="info">
    <tr><td class="label">Waktu</td><td>:</td><td>{{ $report->time_start ? substr($report->time_start,0,5) : '-' }} – {{ $report->time_end ? substr($report->time_end,0,5) : '-' }}</td></tr>
    <tr><td class="label">Judul Praktikum</td><td>:</td><td>{{ $report->title }}</td></tr>
    <tr><td class="label">Mata Pelajaran</td><td>:</td><td>{{ $report->subject_name ?: '-' }}</td></tr>
    <tr><td class="label">Kelas</td><td>:</td><td>{{ $report->class_name ?: '-' }}</td></tr>
    <tr><td class="label">Jumlah peserta</td><td>:</td><td>{{ $report->participants_total }}</td></tr>
    <tr><td class="label">Jumlah peserta hadir</td><td>:</td><td>{{ $report->participants_present }}</td></tr>
    <tr><td class="label">Jumlah peserta absen</td><td>:</td><td>{{ $report->participants_absent }}</td></tr>
</table>
<p><strong>Nama peserta tidak hadir:</strong></p>
<ol>
    @forelse(($report->absent_names ?? []) as $name)<li>{{ $name }}</li>@empty<li>................................................</li>@endforelse
</ol>
<p><strong>Catatan:</strong> {{ $report->notes ?: '-' }}</p>
<table class="sign" style="margin-top:30px"><tr><td>Guru Mata Pelajaran<div class="space"></div><div class="name">{{ $report->teacher?->name ?? '..............................' }}</div></td><td>Koordinator Laboratorium<div class="space"></div><div class="name">{{ $report->activity?->lab?->coordinator?->name ?? '..............................' }}</div></td></tr></table>
@endsection
