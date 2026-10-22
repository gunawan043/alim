@extends('emails.layouts.base')

@section('title', 'Reset Password OTP')
@section('heading', 'Reset Password Akun')

@section('salutation')
    <p>Ykh. <strong>{{ $name }}</strong>,</p>
@endsection

@section('content')
    <p>Kami menerima permintaan <strong>reset password</strong> untuk akun Anda.</p>
    <p>Gunakan kode OTP berikut untuk melanjutkan proses reset password:</p>

    <div class="email-code">{{ $otp }}</div>

    <p class="email-muted">Kode ini berlaku selama <strong>{{ $expiryMinutes ?? 10 }} menit</strong>.</p>

    <div class="email-alert">
        Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini dan segera hubungi administrator.
    </div>
@endsection
