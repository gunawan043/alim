@extends('emails.layouts.base')

@php
    $aksiConfig = match ($aksi) {
        'dibuat' => ['label' => 'Dokumen Baru Dibuat', 'icon' => '🆕'],
        'diperbarui' => ['label' => 'Dokumen Diperbarui', 'icon' => '✏️'],
        'dihapus' => ['label' => 'Dokumen Dihapus', 'icon' => '🗑️'],
        default => ['label' => 'Perubahan Dokumen', 'icon' => '📄'],
    };
@endphp

@section('title', 'Dokumen ISO')
@section('heading', $aksiConfig['label'])

@section('salutation')
    <p>Ykh. <strong>{{ $recipient->name }}</strong>,</p>
@endsection

@section('content')
    <p>
        Terdapat perubahan pada dokumen ISO di divisi
        <strong>{{ $dokumen->divisi->nama ?? '—' }}</strong>.
    </p>

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Kode Dokumen</td>
            <td><strong>{{ $dokumen->kode_dokumen ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nama Dokumen</td>
            <td>{{ $dokumen->nama_dokumen }}</td>
        </tr>
        <tr>
            <td class="label">Kategori</td>
            <td>{{ $dokumen->kategori === 'FORMULIR' ? 'Formulir' : 'Prosedur' }}</td>
        </tr>
        <tr>
            <td class="label">Revisi</td>
            <td>{{ $dokumen->revisi_ke ?? '0' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Berlaku</td>
            <td>{{ $dokumen->tanggal_berlaku?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @if($oldNama && $oldNama !== $dokumen->nama_dokumen)
            <tr>
                <td class="label">Nama Sebelumnya</td>
                <td>{{ $oldNama }}</td>
            </tr>
        @endif
    </table>

    <p style="text-align:center">
        <a href="{{ url('/personalia/'.$recipient->id.'/dokumen-iso') }}" class="email-button">
            Lihat Daftar Dokumen ISO
        </a>
    </p>

    <p class="email-muted">
        Anda menerima email ini karena terdaftar pada divisi terkait.
    </p>
@endsection
