@extends('cetak.layout')
@section('title', 'Inspeksi Keselamatan Laboratorium')
@section('doc_title', 'CATATAN INSPEKSI KESELAMATAN LABORATORIUM IPA')
@section('doc_subtitle') {{ $inspection->lab?->name }} @endsection
@section('content')
<table class="info">
    <tr><td class="label">Nama Laboratorium</td><td>:</td><td>{{ $inspection->lab?->name }}</td><td class="label">Tanggal Inspeksi</td><td>:</td><td>{{ $inspection->inspection_date?->format('d/m/Y') }}</td></tr>
    <tr><td class="label">Nama Inspektor</td><td>:</td><td>{{ $inspection->inspector?->name ?? '-' }}</td><td class="label">Jabatan Inspektor</td><td>:</td><td>{{ $inspection->inspector_position ?: '-' }}</td></tr>
</table>
<table class="data">
    <thead><tr><th style="width:30px">No.</th><th>Aspek yang diperiksa</th><th style="width:100px">Status (Baik/Tidak Baik)</th><th>Keterangan</th></tr></thead>
    <tbody>@foreach($inspection->items as $item)<tr><td class="center">{{ $item->source_no }}</td><td>{{ $item->aspect }}</td><td class="center">{{ $item->status }}</td><td>{{ $item->notes ?: '-' }}</td></tr>@endforeach</tbody>
</table>
<p><strong>Catatan Temuan dan Rekomendasi:</strong></p>
<p>{!! nl2br(e($inspection->findings ?: '-')) !!}</p>
<p>{!! nl2br(e($inspection->recommendations ?: '')) !!}</p>
<table class="info"><tr><td class="label">Tanggal Penyelesaian</td><td>:</td><td>{{ $inspection->follow_up_date?->format('d/m/Y') ?? '-' }}</td></tr><tr><td class="label">Tindakan Perbaikan</td><td>:</td><td>{{ $inspection->follow_up_action ?: '-' }}</td></tr><tr><td class="label">Penanggung Jawab</td><td>:</td><td>{{ $inspection->follow_up_person ?: '-' }}</td></tr></table>
@endsection
