@extends('emails.layouts.base')

@section('title', $isOverdue ? 'Tugas Terlambat' : 'Reminder Tugas')
@section('heading', $isOverdue ? 'Tugas Terlambat' : 'Reminder Tugas')

@section('salutation')
    <p>Ykh. <strong>{{ $owner->name }}</strong>,</p>
@endsection

@section('content')
    @if($isOverdue)
        <div class="email-danger">
            <strong>Perhatian:</strong> tugas ini sudah melewati tenggat waktu.
        </div>
    @endif

    <table class="email-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Judul</td>
            <td><strong>{{ $todo->title }}</strong></td>
        </tr>
        <tr>
            <td class="label">Prioritas</td>
            <td>{{ $priority }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ $todo->statusLabel }}</td>
        </tr>
        <tr>
            <td class="label">Tenggat</td>
            <td>{{ $dueText }}</td>
        </tr>
        <tr>
            <td class="label">Progres</td>
            <td>{{ $todo->progress_percent ?? 0 }}%</td>
        </tr>
    </table>

    @if($todo->description)
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($todo->description), 180) }}</p>
    @endif

    @if($todo->delegatedByUser)
        <p class="email-muted">Didelegasikan oleh: {{ $todo->delegatedByUser->name }}</p>
    @endif

    <p style="text-align:center">
        <a href="{{ $url }}" class="email-button">Buka Tugas</a>
    </p>
@endsection
