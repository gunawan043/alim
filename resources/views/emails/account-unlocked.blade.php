@extends('emails.layouts.base')

@section('title', 'Akun Dibuka Kembali')
@section('heading', 'Akun Berhasil Dibuka Kembali')

@section('salutation')
    <p>Ykh. <strong>{{ $userName }}</strong>,</p>
@endsection

@section('content')
    <div class="email-success">
        <strong>Alhamdulillah, akun Anda telah dibuka kembali.</strong><br>
        Akun Anda sudah dapat digunakan kembali.
    </div>

    <p>
        Jika Anda tidak merasa meminta pembukaan akun ini, segera hubungi Super Admin.
    </p>
@endsection
