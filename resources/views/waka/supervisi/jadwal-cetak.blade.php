@extends('cetak.layout')

@php
    $semesterLabel = $semester == 1 ? 'GANJIL' : ($semester == 2 ? 'GENAP' : '-');
    $tahunAjaran = $academicYear?->name ?? '-';
@endphp

@section('title', 'Jadwal Supervisi Pembelajaran')
@section('doc_title', 'JADWAL SUPERVISI PEMBELAJARAN')
@section('doc_subtitle')
    SEMESTER {{ $semesterLabel }} TAHUN AJARAN {{ strtoupper($tahunAjaran) }}
@endsection

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th style="width:35px">NO</th>
                <th style="width:105px">HARI/TGL</th>
                <th style="width:55px">JAM KE-</th>
                <th style="width:90px">PUKUL</th>
                <th>NAMA GURU</th>
                <th style="width:70px">KELAS</th>
                <th>MATA PELAJARAN</th>
                <th style="width:130px">SUPERVISOR</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row->tanggal_supervisi?->locale('id')->translatedFormat('l, d/m/Y') ?? '-' }}</td>
                <td class="center">{{ $row->slot_index ?: '-' }}</td>
                <td class="center">
                    {{ $row->jam_mulai ? substr($row->jam_mulai, 0, 5) : '-' }}
                    @if($row->jam_selesai)–{{ substr($row->jam_selesai, 0, 5) }}@endif
                </td>
                <td>{{ $row->gtk?->user?->name ?? $row->gtk_name ?? '-' }}</td>
                <td class="center">{{ $row->studyGroup?->name ?? '-' }}</td>
                <td>{{ $row->subject?->name ?? $row->mata_pelajaran ?? '-' }}</td>
                <td>{{ $row->observer?->user?->name ?? $row->observer_name ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="center muted">Belum ada jadwal supervisi pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table style="width:100%; margin-top:28px;" class="small">
        <tr>
            <td style="width:50%; text-align:center;">
                Koordinator Supervisi
                <br><br><br><br>
                <strong>______________________________</strong>
            </td>
            <td style="width:50%; text-align:center;">
                Kepala Satuan Pendidikan
                <br><br><br><br>
                <strong>{{ $school?->kepala_sekolah ?? '______________________________' }}</strong>
            </td>
        </tr>
    </table>
@endsection
