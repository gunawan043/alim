@extends('emails.layouts.base')

@section('title', 'Permintaan Wali Disetujui')
@section('heading', 'Permintaan Wali Disetujui')

@section('salutation')
    <p>Ykh. Bapak/Ibu,</p>
@endsection

@section('content')
    <div class="email-success">
        <strong>Alhamdulillah, permintaan wali telah disetujui.</strong>
    </div>

    <p>
        Permintaan untuk terhubung dengan <strong>{{ $studentName }}</strong>
        telah disetujui oleh <strong>{{ $approverName }}</strong>.
    </p>

    <p>Silakan buka aplikasi ALIM untuk melihat data santri dan informasi terbaru.</p>
@endsection
