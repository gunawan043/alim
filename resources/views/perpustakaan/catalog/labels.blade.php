@extends('cetak.layout')
@section('title', 'Label Buku')
@section('doc_title', 'LABEL BUKU PERPUSTAKAAN')
@section('doc_subtitle', $unit->name ?? '')
@section('content')
    <style>
        @page { size: A4; margin: 8mm; }
        .label-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .label-grid td { width: 63.3mm; height: 33.3mm; padding: 2mm; border: 1px dashed #aaa; vertical-align: top; text-align: center; page-break-inside: avoid; }
        .label-number { font-size: 6pt; color: #666; text-align: left; }
        .label-title { font-family: 'Cambria', 'Caladea', serif; font-size: 7pt; font-weight: bold; line-height: 1.1; height: 8mm; overflow: hidden; }
        .label-code { font-family: 'Cambria', 'Caladea', serif; font-size: 7pt; margin-top: 1mm; }
        .label-unit { font-family: 'Cambria', 'Caladea', serif; font-size: 6pt; color: #555; }
        .label-grid img { width: 18mm; height: 18mm; margin: 1mm auto 0; display: block; }
    </style>
    @php $labelNumber = 0; @endphp
    @foreach($items->chunk(24) as $page)
        <table class="label-grid"><tbody>
            @foreach($page->chunk(3) as $row)
                <tr>@foreach($row as $item)
                    <td>@php $labelNumber++; @endphp<div class="label-number">#{{ $labelNumber }}</div><div class="label-title">{{ \Illuminate\Support\Str::limit($item->title?->title ?? '—', 60) }}</div><img src="{{ $qrData[$item->id] ?? '' }}" alt="QR"><div class="label-code">{{ $item->inventory_code }}</div><div class="label-unit">{{ $unit->name }}</div></td>
                @endforeach</tr>
            @endforeach
        </tbody></table>
        @if(! $loop->last)<div style="page-break-after:always"></div>@endif
    @endforeach
@endsection
