@extends('cetak.layout')
@section('title', 'Daftar Inventaris Laboratorium')
@section('doc_title', 'DAFTAR INVENTARIS ALAT DAN BAHAN LABORATORIUM IPA')
@section('doc_subtitle') {{ $lab->name }} &bull; {{ $year?->name ?? '-' }} &bull; Semester {{ $semester }} @endsection
@section('content')
<table class="data">
    <thead><tr><th style="width:35px">NO</th><th>NAMA ALAT/BAHAN</th><th style="width:75px">JUMLAH</th><th style="width:75px">SATUAN</th><th style="width:90px">KONDISI</th><th>KET.</th></tr></thead>
    <tbody>
    @forelse($rows as $i => $row)
        @php $code = ['baik'=>'B','rusak_ringan'=>'RR','rusak_sedang'=>'RR','rusak_berat'=>'RB'][$row->condition] ?? '-'; @endphp
        <tr><td class="center">{{ $i+1 }}</td><td>{{ $row->asset_name }}</td><td class="center">{{ $row->quantity }}</td><td>{{ $row->unit ?: 'unit' }}</td><td class="center">{{ $code }}</td><td>{{ $row->notes ?: '-' }}</td></tr>
    @empty
        <tr><td colspan="6" class="center">Belum ada data inventaris.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
