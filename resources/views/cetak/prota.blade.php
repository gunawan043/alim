@extends('cetak.layout')

@section('title', 'PROGRAM TAHUNAN')
@section('doc_title', 'PROGRAM TAHUNAN')
@section('doc_subtitle', '')

@section('content')
    <table class="info">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $prota->subject?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td><td class="colon">:</td><td>{{ $prota->gradeLevel?->name ?? 'Semua Jenjang' }}</td>
        </tr>
        <tr>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ ucfirst($prota->semester) }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $prota->academicYear?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:70px">Pekan Efektif Ke-</th>
                <th>Tujuan Pembelajaran</th>
                <th style="width:160px">BAB / Materi</th>
                <th style="width:90px">Alokasi Waktu</th>
                <th style="width:130px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prota->items as $index => $item)
                <tr>
                    <td class="center">{{ $item->urutan ?: $index + 1 }}</td>
                    <td>
                        @if($item->tujuanPembelajaran)
                            <strong>{{ $item->tujuanPembelajaran->kode_tp }}</strong> —
                        @endif
                        {{ $item->materi ?: '—' }}
                    </td>
                    <td>{{ $item->bab ?: '—' }}</td>
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

    <p class="small">Catatan: alokasi waktu untuk asesmen menyesuaikan mata pelajaran.</p>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Dibuat Oleh:<br>Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">{{ $prota->teacher?->name ?? '................................' }}</div>
            </td>
            <td style="width:50%">
                {{ $school?->city?->name ?? 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
                Disetujui Oleh<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_name ?? '................................' }}</div>
                @if($school?->principal_nip)<div class="nip">NIP. {{ $school->principal_nip }}</div>@endif
            </td>
        </tr>
    </table>
@endsection
