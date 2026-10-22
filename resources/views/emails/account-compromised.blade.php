@extends('emails.layouts.base')

@section('title', 'Peringatan Keamanan')
@section('heading', 'Peringatan Keamanan')

@section('salutation')
    <p>Ykh. <strong>{{ $userName }}</strong>,</p>
@endsection

@section('content')
    <p>
        Sistem keamanan ALIM mendeteksi <strong>{{ $attempts }} percobaan login gagal</strong>
        pada akun Anda dari alamat IP <strong>{{ $ipAddress }}</strong>.
    </p>

    <div class="email-alert">
        <strong>Perlu tindakan:</strong><br>
        Segera reset password Anda untuk mengamankan akun.
    </div>

    <p>
        Jika Anda tidak mengenali aktivitas ini, segera hubungi Super Admin untuk bantuan.
    </p>

    <p>
        <a href="{{ $loginUrl }}" class="email-button">Buka Halaman Login</a>
    </p>
@endsection
