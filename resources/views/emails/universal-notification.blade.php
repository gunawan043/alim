@extends('emails.layouts.base')

@section('title', $notification->title ?? 'Notifikasi ALIM')
@section('heading', $notification->title ?? 'Notifikasi ALIM')

@section('salutation')
    <p>Ykh. <strong>{{ $user->name }}</strong>,</p>
@endsection

@section('content')
    <p>{{ $notification->message }}</p>

    @if($notification->reference_code)
        <p class="email-muted">Referensi: <strong>{{ $notification->reference_code }}</strong></p>
    @endif

    @if($notification->action_url)
        <p>
            <a href="{{ $notification->action_url }}" class="email-button">
                {{ $notification->action_text ?: 'Lihat Detail' }}
            </a>
        </p>
    @endif

    <p class="email-muted">
        Dikirim: {{ $notification->created_at->format('d/m/Y H:i') }}<br>
        Modul: {{ ucfirst($notification->module) }}
    </p>
@endsection
