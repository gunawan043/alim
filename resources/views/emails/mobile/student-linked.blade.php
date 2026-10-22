@extends('emails.layouts.base')

@section('title', 'Berhasil Terhubung dengan Santri')
@section('heading', 'Berhasil Terhubung dengan Santri')

@section('salutation')
    <p>Ykh. <strong>{{ $waliName }}</strong>,</p>
@endsection

@section('content')
    <div class="email-success">
        <strong>Alhamdulillah, akun Anda berhasil terhubung dengan santri.</strong>
    </div>

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Nama Santri</td>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <td class="label">Peran Anda</td>
            <td>{{ $roleLabel }}</td>
        </tr>
    </table>

    <p>Sekarang Anda dapat memantau informasi santri melalui portal wali:</p>
    <ul>
        <li>Absensi harian {{ $studentName }}</li>
        <li>Notifikasi kesehatan dan ketidakhadiran</li>
        <li>Raport dan informasi akademik</li>
    </ul>

    <p style="text-align:center">
        <a href="{{ $dashboardUrl }}" class="email-button">Buka Dashboard Wali</a>
    </p>
@endsection
