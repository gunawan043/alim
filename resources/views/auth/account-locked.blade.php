@extends('emails.layouts.base')

@section('title', 'Akun Terkunci')
@section('heading', 'Akun Terkunci Sementara')

@section('salutation')
    <p>Ykh. <strong>{{ $userName }}</strong>,</p>
@endsection

@section('content')
    <div class="email-danger">
        <strong>Akun Anda dikunci sementara.</strong><br>
        Sistem mendeteksi <strong>{{ $attempts }} percobaan login gagal</strong> dari alamat IP
        <strong>{{ $ipAddress }}</strong>.
    </div>

    <p>
        Silakan hubungi Super Admin atau HRD untuk memverifikasi dan membuka kembali akun Anda.
    </p>

    <p>
        Demi keamanan, jangan membagikan password atau kode OTP kepada siapa pun.
    </p>

    <p>
        <a href="{{ $loginUrl }}" class="email-button">Kembali ke Login</a>
    </p>
@endsection
