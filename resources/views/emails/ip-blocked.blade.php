@extends('emails.layouts.base')

@section('title', 'Alamat IP Diblokir Sementara')
@section('heading', 'Alamat IP Diblokir Sementara')

@section('salutation')
    <p>Ykh. Tim Super Admin,</p>
@endsection

@section('content')
    <p>
        Sistem keamanan ALIM memblokir sementara alamat IP berikut karena mendeteksi
        <strong>{{ $attempts }} percobaan login gagal</strong>.
    </p>

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Alamat IP</td>
            <td><strong>{{ $ipAddress }}</strong></td>
        </tr>
        <tr>
            <td class="label">Diblokir sampai</td>
            <td>{{ $blockedUntil }}</td>
        </tr>
        <tr>
            <td class="label">Percobaan gagal</td>
            <td>{{ $attempts }} kali</td>
        </tr>
    </table>

    <p class="email-muted">
        Jika ini bukan aktivitas yang dikenal, periksa log keamanan dan pertimbangkan untuk memblokir IP tersebut lebih lama.
    </p>
@endsection
