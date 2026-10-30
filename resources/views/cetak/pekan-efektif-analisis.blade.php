@extends('cetak.layout')

@section('title', 'Analisis Pekan Efektif')
@section('doc_title', 'ANALISIS PEKAN EFEKTIF')
@section('doc_subtitle')
    TAHUN AJARAN {{ $academicYear?->name ?? '-' }}
@endsection

@section('content')
    @foreach($semesters as $semester)
        <table class="data" style="page-break-inside: avoid; margin-bottom: 14px;">
            <thead>
                <tr>
                    <th style="width:36px">No.</th>
                    <th style="width:70px">Semester</th>
                    <th>Bulan</th>
                    <th style="width:80px">Jumlah Pekan</th>
                    <th style="width:110px">Jumlah Pekan Efektif</th>
                    <th style="width:120px">Jumlah Pekan Tidak Efektif</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semester['months'] as $index => $month)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">{{ $semester['number'] }}</td>
                        <td>{{ $month['month'] }}</td>
                        <td class="center">{{ $month['weeks'] }}</td>
                        <td class="center">{{ $month['effective'] }}</td>
                        <td class="center">{{ $month['not_effective'] }}</td>
                        <td class="small">
                            @forelse($month['notes'] as $note)
                                <div>• {{ $note }}</div>
                            @empty
                                —
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="right" style="font-weight:bold">Jumlah</td>
                    <td class="center" style="font-weight:bold">{{ $semester['total_weeks'] }}</td>
                    <td class="center" style="font-weight:bold">{{ $semester['total_effective'] }}</td>
                    <td class="center" style="font-weight:bold">{{ $semester['total_not_effective'] }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach


    @if($layout['show_signature'] ?? true)
        <table class="sign">
            <tr>
                <td style="width:50%">
                    Mengetahui<br>Kepala Satuan Pendidikan
                    <div class="space"></div>
                    <div class="name">{{ $school?->principal_full_name ?: '................................' }}</div>
                    @if($school?->principal_nupy_display)
                        <div class="nip">NUPY. {{ $school->principal_nupy_display }}</div>
                    @endif
                </td>
                <td style="width:50%">
                    {{ $layout['city'] ?: 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                    <div class="space"></div>
                    <div class="name">{{ $layout['guru_nama'] ?: '................................' }}</div>
                    @if($layout['guru_nip'])
                        <div class="nip">NUPY. {{ $layout['guru_nip'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    @endif
@endsection
