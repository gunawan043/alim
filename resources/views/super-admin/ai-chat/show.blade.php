@extends('layouts.master')
@section('title', 'Detail Chat AI')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.sa.ai-chat.index', ['userId' => $userId]) }}">Monitoring Chat AI</a> @endslot
        @slot('li_2') Detail @endslot
        @slot('title') {{ $conversation->title ?: 'Percakapan' }} @endslot
    @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1">{{ $conversation->title ?: 'Percakapan tanpa judul' }}</h5>
                <div class="small text-muted">
                    {{ $conversation->user?->name ?? 'Pengguna dihapus' }}
                    @if($conversation->user?->email) · {{ $conversation->user->email }} @endif
                    · {{ $conversation->school?->name ?? 'Global' }}
                    · {{ $conversation->last_message_at?->format('d M Y H:i') ?? $conversation->created_at?->format('d M Y H:i') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteChatModal">
                    <i class="ri-delete-bin-line me-1"></i>Hapus Riwayat
                </button>
                <a href="{{ route('user.sa.ai-chat.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
        </div>
        <div class="card-body" style="max-height:70vh;overflow-y:auto;background:#f8f9fc;">
            @forelse($messages as $message)
                <div class="d-flex {{ $message->role === 'user' ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                    <div class="px-3 py-2 rounded-3 {{ $message->role === 'user' ? 'bg-primary text-white' : 'bg-white border' }}"
                         style="max-width:78%;white-space:pre-wrap;font-size:13.5px;">
                        <div class="small opacity-75 mb-1">{{ $message->role === 'user' ? 'Pengguna' : 'ALIM Assistant' }}</div>
                        {{ $message->content }}
                        @if($message->role === 'assistant' && ! empty($message->metadata['model']))
                            <div class="small mt-2 opacity-75">{{ $message->metadata['model'] }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted text-center my-4">Tidak ada pesan.</p>
            @endforelse
        </div>
    </div>

    <form id="deleteChatForm" method="POST" action="{{ route('user.sa.ai-chat.destroy', ['userId' => $userId, 'conversation' => $conversation->id]) }}">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteChatModal"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primary-color="#f7b84b" secondary-color="#f06548"
        title="Hapus Riwayat Chat?"
        message="Seluruh pesan pada percakapan ini akan dihapus permanen."
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger"
        form-id="deleteChatForm" />
@endsection
