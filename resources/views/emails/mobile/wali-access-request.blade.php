@extends('emails.layouts.base')

@section('title', 'Permintaan Wali Baru')
@section('heading', 'Permintaan Wali Baru')

@section('salutation')
    <p>Ykh. Wali Utama,</p>
@endsection

@section('content')
    <p>
        <strong>{{ $requesterName }}</strong> ({{ $requesterEmail }}) mengajukan permintaan
        untuk terhubung sebagai wali dari <strong>{{ $studentName }}</strong>.
    </p>

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Nama Santri</td>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <td class="label">Pemohon</td>
            <td>{{ $requesterName }}</td>
        </tr>
        <tr>
            <td class="label">Email Pemohon</td>
            <td>{{ $requesterEmail }}</td>
        </tr>
        <tr>
            <td class="label">Berlaku Sampai</td>
            <td>{{ $expiresAt }}</td>
        </tr>
    </table>

    <div class="email-alert">
        Buka aplikasi ALIM, lalu periksa menu <strong>Permintaan Wali</strong> untuk menyetujui atau menolak permintaan ini.
        Referensi: <strong>{{ $token }}</strong>
    </div>

    <p class="email-muted">
        Jika Anda tidak mengenali pemohon, abaikan email ini atau hubungi administrator sekolah.
    </p>
@endsection
