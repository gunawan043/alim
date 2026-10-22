@extends('emails.layouts.base')

@section('title', 'Kode Verifikasi Login')
@section('heading', 'Verifikasi Login')

@section('salutation')
    <p>Ykh. <strong>{{ $name }}</strong>,</p>
@endsection

@section('content')
    <p>Masukkan kode berikut untuk menyelesaikan proses login ke akun ALIM Anda:</p>

    <div class="email-code">{{ $otp }}</div>

    <p class="email-muted">Kode ini berlaku selama <strong>{{ $expiryMinutes }} menit</strong>.</p>

    <div class="email-alert">
        Jika Anda tidak merasa melakukan login, abaikan email ini dan segera hubungi administrator.
    </div>
@endsection
