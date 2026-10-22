@extends('emails.layouts.base')

@section('title', 'Verifikasi Wali Baru')
@section('heading', 'Permintaan Verifikasi Wali Baru')

@section('salutation')
    <p>Ykh. <strong>{{ $waliName }}</strong>,</p>
@endsection

@section('content')
    <p>
        Bapak/Ibu <strong>{{ $waliName }}</strong> mengajukan permintaan untuk menjadi
        <strong>{{ $roleLabel }}</strong> dari <strong>{{ $studentName }}</strong>.
    </p>

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Nama Santri</td>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <td class="label">Peran yang diminta</td>
            <td>{{ $roleLabel }}</td>
        </tr>
        <tr>
            <td class="label">Batas waktu</td>
            <td>{{ $expiresAt }}</td>
        </tr>
    </table>

    <p>Jika Anda mengenal orang tersebut dan menyetujui permintaan ini, silakan pilih:</p>

    <p>
        <a href="{{ $approveUrl }}" class="email-button">Setujui Permintaan</a>
    </p>
    <p>
        <a href="{{ $rejectUrl }}" class="email-button" style="background:#b91c1c">Tolak Permintaan</a>
    </p>

    <p class="email-muted">
        Jika Anda tidak mengenali permintaan ini, abaikan email ini atau hubungi administrator sekolah.
    </p>
@endsection
