@extends('cetak.layout')
@section('title', 'Kartu Anggota')
@section('doc_title', 'KARTU ANGGOTA PERPUSTAKAAN')
@section('doc_subtitle', $member->unit?->name ?? '')
@section('content')
    <style>
        .member-card { width: 85.60mm; height: 53.98mm; border: 1px solid #222; border-radius: 3mm; overflow: hidden; margin: 0 auto 8mm; page-break-inside: avoid; font-family: 'Cambria', 'Caladea', serif; }
        .member-card .head { background: #0a5f9e; color: #fff; padding: 3mm 4mm 2mm; }
        .member-card .head .unit { font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .member-card .head .sub { font-size: 7pt; }
        .member-card .body { display: flex; gap: 3mm; padding: 3mm 4mm; }
        .member-card .photo { width: 20mm; height: 25mm; object-fit: cover; border: 1px solid #ccc; background: #eef2f7; }
        .member-card .name { font-size: 10pt; font-weight: bold; }
        .member-card .meta { font-size: 7.5pt; color: #333; margin-top: 1mm; }
        .member-card .qr { text-align: center; margin-left: auto; }
        .member-card .qr img { width: 22mm; height: 22mm; }
        .member-card .foot { border-top: 1px solid #ddd; font-size: 6.5pt; color: #555; padding: 1mm 4mm; }
    </style>
    <div class="member-card">
        <div class="head"><div class="unit">{{ $member->unit?->name }}</div><div class="sub">Kartu Anggota Perpustakaan</div></div>
        <div class="body">
            @if(($member->student?->photo_path && file_exists(storage_path('app/public/'.$member->student->photo_path)))<img class="photo" src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$member->student->photo_path))) }}" alt="Foto">@else<div class="photo"></div>@endif
            <div>
                <div class="name">{{ $member->display_name }}</div>
                <div class="meta">{{ $member->member_number }}</div>
                <div class="meta">{{ $member->student?->nis ? 'NIS: '.$member->student->nis : ($member->user?->name ?? 'GTK') }}</div>
                <div class="meta">Berlaku s.d. {{ $card->valid_until?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div class="qr"><img src="{{ $qr }}" alt="QR"><div class="meta">{{ $card->card_number }}</div></div>
        </div>
        <div class="foot">Kartu ini wajib dibawa saat meminjam. QR bukan pengganti kartu fisik.</div>
    </div>
@endsection
