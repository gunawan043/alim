@extends('emails.layouts.base')

@section('title', 'Permintaan Wali Ditolak')
@section('heading', 'Permintaan Wali Ditolak')

@section('salutation')
    <p>Ykh. Bapak/Ibu,</p>
@endsection

@section('content')
    <p>
        Permintaan untuk terhubung dengan <strong>{{ $studentName }}</strong>
        belum dapat disetujui oleh <strong>{{ $approverName }}</strong>.
    </p>

    @if($note)
        <div class="email-alert">
            <strong>Catatan:</strong><br>
            {{ $note }}
        </div>
    @endif

    <p class="email-muted">
        Jika Anda membutuhkan bantuan, silakan hubungi administrator sekolah.
    </p>
@endsection
