@extends('cetak.layout')
@section('title', 'Jadwal Kalibrasi Laboratorium')
@section('doc_title', 'JADWAL KALIBRASI ALAT LABORATORIUM IPA')
@section('doc_subtitle') {{ $lab->name }} &bull; {{ $year?->name ?? '-' }} &bull; Semester {{ $semester }} @endsection
@section('content')
<table class="data">
    <thead><tr><th style="width:30px">No</th><th>Nama Alat</th><th style="width:45px">Jml</th><th style="width:110px">Tanggal Kalibrasi Terakhir</th><th style="width:110px">Jadwal Kalibrasi Berikutnya</th><th style="width:80px">Jenis Kalibrasi (I/E)</th><th style="width:70px">Status (OS/OD)</th></tr></thead>
    <tbody>
    @forelse($rows as $i => $row)
        <tr><td class="center">{{ $i+1 }}</td><td>{{ $row->asset?->asset_name ?? '-' }}</td><td class="center">{{ $row->asset?->quantity ?? 1 }}</td><td class="center">{{ $row->last_calibration_date?->format('d/m/Y') ?? '-' }}</td><td class="center">{{ $row->next_calibration_date?->format('d/m/Y') ?? '-' }}</td><td class="center">{{ $row->calibration_type }}</td><td class="center">{{ $row->status }}</td></tr>
    @empty
        <tr><td colspan="7" class="center">Belum ada data kalibrasi.</td></tr>
    @endforelse
    </tbody>
</table>
<p class="small">I = Internal; E = Eksternal. OS = On Schedule; OD = Overdue. Kalibrasi minimal satu tahun sekali atau sesuai manual alat.</p>
@endsection
