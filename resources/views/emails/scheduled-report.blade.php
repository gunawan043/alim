@extends('emails.layouts.base')

@section('title', 'Laporan Terjadwal')
@section('heading', 'Laporan Terjadwal')

@section('salutation')
    <p>Ykh. Tim Penerima Laporan,</p>
@endsection

@section('content')
    <p>Berikut laporan terjadwal <strong>{{ $schedule->name ?? 'ALIM' }}</strong>.</p>

    @php
        $overview = $data['overview'] ?? [];
    @endphp

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Total Lowongan</td>
            <td>{{ $overview['total_jobs'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Total Pelamar</td>
            <td>{{ $overview['total_applications'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Diterima</td>
            <td>{{ $overview['hired_count'] ?? 0 }}</td>
        </tr>
    </table>

    <p class="email-muted">
        Laporan lengkap dapat diakses melalui dashboard rekrutmen ALIM.
    </p>
@endsection
