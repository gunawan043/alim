@extends('cetak.layout')
@section('title', 'Kartu Anggota Massal')
@section('doc_title', 'KARTU ANGGOTA PERPUSTAKAAN')
@section('doc_subtitle', $unit->name ?? '')
@section('content')
    <style>
        .card-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .card-grid td { width: 50%; padding: 3mm; vertical-align: top; page-break-inside: avoid; }
        .member-card { width: 85.60mm; height: 53.98mm; border: 1px solid #222; border-radius: 3mm; overflow: hidden; font-family: 'Cambria', 'Caladea', serif; }
        .member-card .head { background: #0a5f9e; color: #fff; padding: 2.5mm 3mm 1.5mm; }
        .member-card .head .unit { font-size: 9pt; font-weight: bold; text-transform: uppercase; }
        .member-card .head .sub { font-size: 6.5pt; }
        .member-card .body { display: flex; gap: 2mm; padding: 2.5mm 3mm; }
        .member-card .photo { width: 18mm; height: 23mm; object-fit: cover; border: 1px solid #ccc; background: #eef2f7; }
        .member-card .name { font-size: 9pt; font-weight: bold; }
        .member-card .meta { font-size: 7pt; color: #333; margin-top: 1mm; }
        .member-card .qr { text-align: center; margin-left: auto; }
        .member-card .qr img { width: 19mm; height: 19mm; }
        .member-card .foot { border-top: 1px solid #ddd; font-size: 6pt; color: #555; padding: 1mm 3mm; }
    </style>
    @foreach($cards->chunk(10) as $page)
        <table class="card-grid"><tbody>
            @foreach($page->chunk(2) as $row)
                <tr>@foreach($row as $entry)
                    @php($member = $entry['member'])
                    <td><div class="member-card"><div class="head"><div class="unit">{{ $member->unit?->name }}</div><div class="sub">Kartu Anggota Perpustakaan</div></div><div class="body">@if($member->student?->photo_path && file_exists(storage_path('app/public/'.$member->student->photo_path)))
                        <img class="photo" src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$member->student->photo_path))) }}" alt="Foto">
                    @else
                        <div class="photo"></div>
                    @endif<div><div class="name">{{ $member->display_name }}</div><div class="meta">{{ $member->member_number }}</div><div class="meta">{{ $member->student?->nis ? 'NIS: '.$member->student->nis : ($member->user?->name ?? 'GTK') }}</div><div class="meta">Berlaku s.d. {{ $entry['card']->valid_until?->format('d/m/Y') ?? '—' }}</div></div><div class="qr"><img src="{{ $entry['qr'] }}" alt="QR"><div class="meta">{{ $entry['card']->card_number }}</div></div></div><div class="foot">Wajib dibawa saat meminjam. QR bukan pengganti kartu fisik.</div></div></td>
                @endforeach</tr>
            @endforeach
        </tbody></table>
        @if(! $loop->last)<div style="page-break-after:always"></div>@endif
    @endforeach
@endsection
