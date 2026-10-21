@extends('cetak.layout')
@section('title', 'Pemeriksaan Berkala Laboratorium')
@section('doc_title', 'PEMERIKSAAN BERKALA ALAT DAN BAHAN LABORATORIUM IPA')
@section('doc_subtitle') {{ $inspection->lab?->name }} &bull; {{ $inspection->performed_date?->translatedFormat('d F Y') ?? '-' }} @endsection
@section('content')
<table class="data">
    <thead><tr><th style="width:30px">No</th><th>Nama Alat</th><th style="width:45px">Jml</th><th style="width:100px">Kondisi Alat (B/RR/RB)</th><th>Tindakan Pemeliharaan</th><th style="width:90px">Tanggal Pemeriksaan</th><th>Kondisi Setelah/Tindak Lanjut</th></tr></thead>
    <tbody>
    @forelse($inspection->items as $i => $item)
        <tr><td class="center">{{ $i+1 }}</td><td>{{ $item->item_name }}</td><td class="center">{{ $item->quantity }}</td><td class="center">{{ $item->condition_code }}</td><td>{{ $item->maintenance_action ?: '-' }}</td><td class="center">{{ $item->inspected_at?->format('d/m/Y') ?? '-' }}</td><td>{{ $item->follow_up ?: '-' }}</td></tr>
    @empty
        <tr><td colspan="7" class="center">Belum ada item pemeriksaan.</td></tr>
    @endforelse
    </tbody>
</table>
<p class="small">Keterangan: B = Baik; RR = Rusak Ringan; RB = Rusak Berat. Pemeriksaan minimal setiap tiga bulan sekali.</p>
@endsection
