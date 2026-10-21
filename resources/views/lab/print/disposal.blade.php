@extends('cetak.layout')
@section('title', 'Penghapusan Inventaris Laboratorium')
@section('doc_title', 'PENGHAPUSAN INVENTARIS LABORATORIUM IPA')
@section('doc_subtitle') {{ $disposal->lab?->name }} @endsection
@section('content')
<table class="data">
    <thead><tr><th style="width:30px">No</th><th>Nama Alat/Bahan</th><th style="width:55px">Jumlah</th><th style="width:55px">Satuan</th><th style="width:55px">Kondisi</th><th>Alasan Penghapusan</th><th>Cara Penghapusan</th></tr></thead>
    <tbody>
    @foreach($disposal->items as $i => $item)
        <tr><td class="center">{{ $i+1 }}</td><td>{{ $item->item_name }}</td><td class="center">{{ $item->quantity }}</td><td>{{ $item->unit }}</td><td class="center">{{ $item->condition_code ?: '-' }}</td><td>{{ $item->reason }}</td><td>{{ $item->method }}</td></tr>
    @endforeach
    @for($i=$disposal->items->count(); $i<5; $i++)
        <tr><td class="center">{{ $i+1 }}</td><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
    @endfor
    </tbody>
</table>
<table class="info" style="margin-top:14px"><tr><td class="label">Tanggal Penghapusan</td><td>:</td><td>{{ $disposal->disposal_date?->format('d/m/Y') ?? '-' }}</td></tr><tr><td class="label">Tindakan Lanjut</td><td>:</td><td>{{ $disposal->follow_up ?: '-' }}</td></tr></table>
<table class="sign" style="margin-top:26px"><tr><td>Koordinator Laboratorium<div class="space"></div><div class="name">{{ $disposal->lab?->coordinator?->name ?? '..............................' }}</div></td><td>Kepala Satuan Pendidikan<div class="space"></div><div class="name">{{ $school?->kepala_sekolah ?? '..............................' }}</div></td></tr></table>
@endsection
