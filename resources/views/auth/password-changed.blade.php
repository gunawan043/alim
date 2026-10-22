@extends('emails.layouts.base')

@section('title', 'Password Akun Berubah')
@section('heading', 'Password Akun Berhasil Diubah')

@section('salutation')
    <p>Ykh. <strong>{{ $name }}</strong>,</p>
@endsection

@section('content')
    <p>
        Password akun ALIM Anda baru saja diubah pada <strong>{{ $changedAt }}</strong>.
    </p>

    @if($ipAddress)
        <p class="email-muted">Alamat IP: <strong>{{ $ipAddress }}</strong></p>
    @endif

    <div class="email-alert">
        Jika Anda tidak melakukan perubahan ini, segera hubungi administrator dan amankan akun Anda.
    </div>
@endsection
