@extends('cetak.layout')

@section('title', 'ATP')
@section('doc_title', 'Alur Tujuan Pembelajaran (ATP)')
@section('doc_subtitle')
    {{ $atp->subject?->name ?? '-' }} — {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }} — {{ $atp->academicYear?->name ?? '-' }} — Semester {{ ucfirst($atp->semester) }}
@endsection

@section('content')
    <table class="info">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '-' }}</td>
            <td class="label">Guru Penyusun</td><td class="colon">:</td><td>{{ $atp->teacher?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $atp->subject?->name ?? '-' }}</td>
            <td class="label">Kelas / Fase</td><td class="colon">:</td><td>{{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}{{ $atp->fase ? ' / '.$atp->fase : '' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $atp->academicYear?->name ?? '-' }}</td>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ ucfirst($atp->semester) }}</td>
        </tr>
        <tr>
            <td class="label">JP Efektif Tersedia</td><td class="colon">:</td><td>{{ $jpEfektif }} JP ({{ $weeklyHours }} JP/minggu × {{ $mingguEfektif }} minggu efektif)</td>
            <td class="label">JP Teralokasi</td><td class="colon">:</td>
            <td>
                {{ $jpTerpakai }} JP
                @if($jpTerpakai > $jpEfektif)
                    — melebihi {{ $jpTerpakai - $jpEfektif }} JP
                @elseif($jpTerpakai < $jpEfektif)
                    — kurang {{ $jpEfektif - $jpTerpakai }} JP
                @else
                    — pas
                @endif
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:70px">Kode TP</th>
                <th>Tujuan Pembelajaran</th>
                <th style="width:120px">Elemen</th>
                <th style="width:70px">Alokasi JP</th>
                <th style="width:130px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atp->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $item->tujuanPembelajaran?->kode_tp ?? '—' }}</td>
                    <td>{{ $item->tujuanPembelajaran?->deskripsi ?? '—' }}</td>
                    <td>{{ $item->tujuanPembelajaran?->elemen ?? '—' }}</td>
                    <td class="center">{{ $item->jp_alokasi }}</td>
                    <td class="small">{{ $item->catatan ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center muted">Belum ada TP pada ATP ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Total JP Teralokasi</td>
                <td class="center">{{ $jpTerpakai }}</td>
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
                <div class="name">{{ $atp->teacher?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
