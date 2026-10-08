@extends('cetak.layout')

@section('title', 'PROTA')
@section('doc_title', 'Program Tahunan (PROTA)')
@section('doc_subtitle')
    {{ $prota->subject?->name ?? '-' }} — {{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }} — {{ $prota->academicYear?->name ?? '-' }}
@endsection

@section('content')
    <table class="info">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '-' }}</td>
            <td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $prota->subject?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Fase</td><td class="colon">:</td><td>{{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }}{{ $prota->fase ? ' / '.$prota->fase : '' }}</td>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ ucfirst($prota->semester) }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $prota->academicYear?->name ?? '-' }}</td>
            <td class="label">Guru Pengampu</td><td class="colon">:</td><td>{{ $prota->teacher?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Pekan Efektif</td><td class="colon">:</td><td>{{ $prota->minggu_efektif }} minggu efektif</td>
            <td class="label">JP Efektif</td><td class="colon">:</td><td>{{ $prota->jp_efektif }} JP ({{ $prota->jp_per_minggu }} JP/minggu)</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:120px">BAB / Materi</th>
                <th>Tujuan Pembelajaran</th>
                <th style="width:80px">Alokasi Waktu</th>
                <th style="width:130px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prota->items as $index => $item)
                <tr>
                    <td class="center">{{ $item->urutan ?: $index + 1 }}</td>
                    <td>{{ $item->bab ?: '—' }}</td>
                    <td>
                        @if($item->tujuanPembelajaran)
                            <strong>{{ $item->tujuanPembelajaran->kode_tp }}</strong> —
                        @endif
                        {{ $item->materi ?: '—' }}
                    </td>
                    <td class="center">{{ $item->alokasi_jp }} JP</td>
                    <td class="small">{{ $item->keterangan ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted">Belum ada baris PROTA.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="right">Total Alokasi Waktu</td>
                <td class="center">{{ $totalJp }} JP</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_name ?? '................................' }}</div>
                @if($school?->principal_nip)<div class="nip">NIP. {{ $school->principal_nip }}</div>@endif
            </td>
            <td style="width:50%">
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">{{ $prota->teacher?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
