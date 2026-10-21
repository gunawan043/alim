@extends('cetak.layout')
@section('title', 'Peminjaman dan Pengembalian Alat Laboratorium')
@section('doc_title', 'PEMINJAMAN DAN PENGEMBALIAN ALAT LABORATORIUM IPA')
@section('doc_subtitle') {{ $loan->lab?->name }} @endsection
@section('content')
<table class="info"><tr><td class="label">Laboratorium</td><td>:</td><td>{{ $loan->lab?->name ?? '-' }}</td></tr></table>
<p class="section-title">I. DATA PEMINJAMAN</p>
<div class="section-body">
    <table class="inside">
        <tr><td class="label">Nama Peminjam</td><td>:</td><td>{{ $loan->borrower_name }}</td></tr>
        <tr><td class="label">Instansi</td><td>:</td><td>{{ $loan->institution ?: '-' }}</td></tr>
        <tr><td class="label">Tanggal Peminjaman</td><td>:</td><td>{{ $loan->loan_date?->format('d/m/Y') ?? '-' }}</td></tr>
        <tr><td class="label">Tujuan Peminjaman</td><td>:</td><td>{{ $loan->purpose }}</td></tr>
    </table>
</div>
<table class="data" style="margin-top:10px">
    <thead><tr><th style="width:30px">No</th><th>Nama Alat</th><th style="width:60px">Jumlah</th><th style="width:100px">Kondisi Awal</th><th>Keterangan</th></tr></thead>
    <tbody>
    @foreach($loan->items as $i => $item)
        <tr><td class="center">{{ $i+1 }}</td><td>{{ $item->item_name }}</td><td class="center">{{ $item->quantity }} {{ $item->unit }}</td><td class="center">{{ $item->condition_on_loan }}</td><td>{{ $item->notes ?: '-' }}</td></tr>
    @endforeach
    @for($i=$loan->items->count(); $i<5; $i++)
        <tr><td class="center">{{ $i+1 }}</td><td>&nbsp;</td><td></td><td></td><td></td></tr>
    @endfor
    </tbody>
</table>
<p class="small">Dokumen sumber menyediakan lima baris alat; baris kosong dipertahankan pada cetak.</p>
@endsection
