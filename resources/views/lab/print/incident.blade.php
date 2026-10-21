@extends('cetak.layout')
@section('title', 'Laporan Insiden Laboratorium')
@section('doc_title', 'LAPORAN INSIDEN/KECELAKAAN LABORATORIUM')
@section('doc_subtitle') {{ $incident->lab?->name }} @endsection
@section('content')
<table class="info">
    <tr><td class="label">Nama Laboratorium</td><td>:</td><td>{{ $incident->lab?->name }}</td><td class="label">Satuan Pendidikan</td><td>:</td><td>{{ $school?->name ?? '-' }}</td></tr>
    <tr><td class="label">Tanggal Kejadian</td><td>:</td><td>{{ $incident->incident_date?->format('d/m/Y') }}</td><td class="label">Waktu Kejadian</td><td>:</td><td>{{ $incident->incident_time ? substr($incident->incident_time,0,5) : '-' }}</td></tr>
    <tr><td class="label">Nama Pelapor</td><td>:</td><td>{{ $incident->reporter?->name ?? '-' }}</td><td class="label">Jabatan Pelapor</td><td>:</td><td>{{ $incident->reporter_position ?: '-' }}</td></tr>
</table>
<p class="section-title">DETAIL INSIDEN</p>
<p><strong>Jenis Insiden:</strong> {{ ucfirst(str_replace('_',' ', $incident->incident_type)) }}{{ $incident->other_type ? ' — '.$incident->other_type : '' }}</p>
<p><strong>Kronologi Kejadian:</strong><br>{!! nl2br(e($incident->chronology)) !!}</p>
<table class="info">
    <tr><td class="label">Lokasi Kejadian</td><td>:</td><td>{{ $incident->location ?: '-' }}</td></tr>
    <tr><td class="label">Nama Korban</td><td>:</td><td>{{ $incident->victim_name ?: '-' }}</td></tr>
    <tr><td class="label">Jenis Cedera</td><td>:</td><td>{{ $incident->injury_type ?: '-' }}</td></tr>
    <tr><td class="label">Pertolongan Pertama</td><td>:</td><td>{{ $incident->first_aid ?: '-' }}</td></tr>
    <tr><td class="label">Alat Terlibat</td><td>:</td><td>{{ $incident->equipment_involved ?: '-' }}</td></tr>
</table>
<p class="section-title">TINDAKAN YANG DIAMBIL</p>
<p><strong>Tindakan Segera:</strong> {{ $incident->immediate_action ?: '-' }}</p>
<p><strong>Tindakan Pencegahan:</strong> {{ $incident->prevention ?: '-' }}</p>
<p><strong>Personel Penanganan:</strong> {{ $incident->personnel ?: '-' }}</p>
@endsection
