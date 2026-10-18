@extends('layouts.master')
@section('title', 'Monitoring Chat AI')

@section('css')
<style>
    .ai-monitor-admin .ctext-wrap-content {
        background: linear-gradient(135deg, #0d8f7a, #08a88a) !important;
        color: #fff !important;
    }
    .ai-monitor-admin .ctext-wrap-content p { color: #fff; }
    .ai-monitor-admin .chat-time { color: #55706b; }
    .chat-list.right .ctext-wrap-content {
        background: linear-gradient(135deg, #405189, #356f91) !important;
        color: #fff !important;
    }
    .chat-list.right .ctext-wrap-content p { color: #fff; }

    [data-bs-theme="dark"] .ai-monitor-conversation,
    [data-bs-theme="dark"] .chat-conversation { background: #141a26 !important; }
    [data-bs-theme="dark"] .ai-monitor-admin .ctext-wrap-content {
        background: linear-gradient(135deg, #0b6b5c, #0d8f7a) !important;
    }
    [data-bs-theme="dark"] .chat-leftsidebar,
    [data-bs-theme="dark"] .user-chat { background: #1b2230; }
    [data-bs-theme="dark"] .chat-leftsidebar .chat-list.active { background: #22304a; }
    [data-bs-theme="dark"] .chat-input {
        background: #20293a !important;
        border-color: #344158 !important;
        color: #e8eefb;
    }
    [data-bs-theme="dark"] .chat-input::placeholder { color: #9aa8c0; }
</style>
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $selected = $selected ?? null;
        $messages = $messages ?? collect();
    @endphp

    <div class="chat-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">
        {{-- ══════════ LEFT: daftar percakapan ══════════ --}}
        <div class="chat-leftsidebar">
            <div class="px-4 pt-4 mb-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Chat AI</h5>
                        <p class="text-muted mb-0 small">Monitoring percakapan pengguna</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">{{ $conversations->total() }}</span>
                </div>

                <form method="GET" action="{{ route('user.sa.ai-chat.index', ['userId' => $userId]) }}" class="mt-3">
                    <div class="search-box mb-2">
                        <input type="search" name="search" value="{{ $search }}" class="form-control bg-light border-light" placeholder="Cari pengguna / judul...">
                        <i class="ri-search-2-line search-icon"></i>
                    </div>
                    <div class="row g-1">
                        <div class="col-7">
                            <select name="school_id" class="form-select form-select-sm">
                                <option value="">Semua sekolah</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5 d-grid">
                            <button class="btn btn-sm btn-primary" title="Filter"><i class="ri-filter-3-line me-1"></i>Filter</button>
                        </div>
                        <div class="col-6">
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" title="Dari tanggal">
                        </div>
                        <div class="col-6">
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" title="Sampai tanggal">
                        </div>
                    </div>
                </form>
            </div>

            <div class="chat-room-list pt-1" style="max-height:calc(100vh - 310px);overflow-y:auto;">
                <ul class="list-unstyled chat-list chat-user-list mb-0">
                    @forelse($conversations as $conversation)
                        @php
                            $isSelected = $selected && $selected->id === $conversation->id;
                            $initial = strtoupper(substr($conversation->user?->name ?: '?', 0, 1));
                        @endphp
                        <li class="{{ $isSelected ? 'active' : '' }}">
                            <a href="{{ route('user.sa.ai-chat.index', ['userId' => $userId, 'conversation' => $conversation->id]) }}"
                               class="d-flex align-items-start gap-2 text-reset">
                                <div class="flex-shrink-0">
                                    <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                                        {{ $initial }}
                                    </div>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                        <h6 class="text-truncate mb-0 fs-13">{{ $conversation->user?->name ?? 'Pengguna dihapus' }}</h6>
                                        <small class="text-muted flex-shrink-0" style="font-size:9px;">
                                            {{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at?->format('d/m') }}
                                        </small>
                                    </div>
                                    <p class="text-truncate text-muted mb-1" style="font-size:11px;">{{ $conversation->title ?: 'Tanpa judul' }}</p>
                                    <div class="d-flex align-items-center gap-1">
                                        @if($conversation->mode === 'support')
                                            <span class="badge bg-warning-subtle text-warning" style="font-size:9px;">Bantuan</span>
                                            @if($conversation->status === 'replied')
                                                <span class="badge bg-success-subtle text-success" style="font-size:9px;">Dibalas</span>
                                            @elseif($conversation->status === 'closed')
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size:9px;">Tutup</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger" style="font-size:9px;">Perlu respon</span>
                                            @endif
                                        @else
                                            <span class="badge bg-info-subtle text-info" style="font-size:9px;">AI</span>
                                        @endif
                                        <span class="text-muted" style="font-size:9px;">{{ $conversation->messages_count }} pesan</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-5 text-center text-muted small">Belum ada percakapan.</li>
                    @endforelse
                </ul>
            </div>

            @if($conversations->hasPages())
                <div class="px-3 py-2 border-top">{{ $conversations->links() }}</div>
            @endif
        </div>

        {{-- ══════════ RIGHT: isi percakapan ══════════ --}}
        <div class="user-chat w-100 overflow-hidden">
            @if($selected)
                <div class="chat-content d-lg-flex">
                    <div class="w-100 overflow-hidden position-relative">
                        <div class="p-3 user-chat-topbar">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold">
                                        {{ strtoupper(substr($selected->user?->name ?: '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fs-15">{{ $selected->user?->name ?? 'Pengguna dihapus' }}</h5>
                                        <div class="text-muted small">
                                            {{ $selected->user?->email }}
                                            @if($selected->school) · {{ $selected->school->name }} @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @if($selected->mode === 'support')
                                        <span class="badge bg-warning-subtle text-warning">Bantuan Super Admin</span>
                                        @if($selected->status === 'closed')
                                            <span class="badge bg-secondary-subtle text-secondary">Ditutup</span>
                                        @elseif($selected->status === 'replied')
                                            <span id="adminConversationStatus" class="badge bg-success-subtle text-success">Sudah dibalas</span>
                                        @else
                                            <span id="adminConversationStatus" class="badge bg-danger-subtle text-danger">Perlu respon</span>
                                        @endif
                                    @else
                                        <span class="badge bg-info-subtle text-info">Percakapan AI</span>
                                    @endif

                                    @if($selected->mode === 'support')
                                        @if($selected->status === 'closed')
                                            <form method="POST" action="{{ route('user.sa.ai-chat.reopen', ['userId' => $userId, 'conversation' => $selected->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success"><i class="ri-lock-unlock-line me-1"></i>Buka</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('user.sa.ai-chat.close', ['userId' => $userId, 'conversation' => $selected->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary"><i class="ri-lock-line me-1"></i>Tutup</button>
                                            </form>
                                        @endif
                                    @endif

                                    <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteChatModal">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="chat-conversation ai-monitor-conversation p-3 p-lg-4" id="adminChatConversation" data-simplebar
                             style="max-height:calc(100vh - 300px);overflow-y:auto;">
                            <ul class="list-unstyled chat-conversation-list mb-0" id="adminConversationList">
                                @forelse($messages as $message)
                                    @php
                                        $isAdmin = $message->role === 'admin';
                                        $isUser = $message->role === 'user';
                                        $label = $isAdmin ? 'Super Admin' : ($isUser ? 'Pengguna' : 'ALIM AI');
                                        $initial = $isAdmin ? 'SA' : strtoupper(substr($selected->user?->name ?: '?', 0, 1));
                                    @endphp
                                    <li class="chat-list {{ $isUser ? 'right' : 'left' }} {{ $isAdmin ? 'ai-monitor-admin' : '' }}">
                                        <div class="conversation-list">
                                            <div class="chat-avatar">
                                                <div class="avatar-xs rounded-circle {{ $isAdmin ? 'bg-success' : ($isUser ? 'bg-primary' : 'bg-info') }} text-white d-flex align-items-center justify-content-center fw-semibold">
                                                    {{ $isAdmin ? 'SA' : ($isUser ? $initial : 'AI') }}
                                                </div>
                                            </div>
                                            <div class="user-chat-content">
                                                <div class="ctext-wrap">
                                                    <div class="ctext-wrap-content">
                                                        <p class="mb-0" style="white-space:pre-wrap;">{{ $message->content }}</p>
                                                    </div>
                                                </div>
                                                <div class="chat-time">
                                                    {{ $message->created_at?->format('d M Y H:i') }} · {{ $label }}
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center text-muted py-5">Belum ada pesan.</li>
                                @endforelse
                            </ul>
                        </div>

                        @if($selected->mode === 'support')
                            @if($selected->status === 'closed')
                                <div class="chat-input-section p-3 p-lg-4 text-center text-muted small">
                                    Percakapan bantuan sudah ditutup. Buka kembali untuk membalas.
                                </div>
                            @else
                                <div class="chat-input-section p-3 p-lg-4">
                                    <form method="POST" id="adminReplyForm"
                                          action="{{ route('user.sa.ai-chat.reply', ['userId' => $userId, 'conversation' => $selected->id]) }}">
                                        @csrf
                                        <div class="row g-0 align-items-center">
                                            <div class="col-auto">
                                                <div class="avatar-xs rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-2 fw-semibold">SA</div>
                                            </div>
                                            <div class="col">
                                                <input type="text" name="message" id="adminReplyInput"
                                                       class="form-control chat-input"
                                                       placeholder="Balas sebagai Super Admin..." autocomplete="off" required maxlength="2000">
                                                <div id="adminReplyError" class="text-danger small mt-1 d-none"></div>
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" id="adminReplySubmit" class="btn btn-success chat-send ms-2">
                                                    <i class="ri-send-plane-2-fill"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        @else
                            <div class="chat-input-section p-3 p-lg-4 text-center text-muted small">
                                <i class="ri-robot-2-line me-1"></i>Percakapan AI bersifat baca-saja. Balasan manual hanya tersedia pada mode Bantuan Super Admin.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center justify-content-center h-100 p-5 text-center text-muted">
                    <div>
                        <i class="ri-chat-3-line fs-1 d-block mb-3"></i>
                        <h5>Pilih percakapan</h5>
                        <p class="mb-0">Pilih percakapan di kiri untuk melihat transkrip dan membalas mode bantuan.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($selected)
        <form id="deleteChatForm" method="POST" action="{{ route('user.sa.ai-chat.destroy', ['userId' => $userId, 'conversation' => $selected->id]) }}">
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
    @endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('adminReplyForm');
        const input = document.getElementById('adminReplyInput');
        const submit = document.getElementById('adminReplySubmit');
        const errorBox = document.getElementById('adminReplyError');
        const list = document.getElementById('adminConversationList');
        const conversation = document.getElementById('adminChatConversation');
        const status = document.getElementById('adminConversationStatus');
        if (! form || ! input || ! list) return;

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (form.dataset.busy === '1') return;
            form.dataset.busy = '1';
            const message = (input.value || '').trim();
            if (! message) return;

            submit.disabled = true;
            if (errorBox) { errorBox.textContent = ''; errorBox.classList.add('d-none'); }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ message: message }),
                });
                const data = await response.json().catch(function () { return {}; });

                if (! response.ok || ! data.ok) {
                    throw new Error(data.message || 'Balasan gagal dikirim.');
                }

                const item = document.createElement('li');
                item.className = 'chat-list left';

                const conversationList = document.createElement('div');
                conversationList.className = 'conversation-list';

                const avatar = document.createElement('div');
                avatar.className = 'chat-avatar';
                const avatarInner = document.createElement('div');
                avatarInner.className = 'avatar-xs rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-semibold';
                avatarInner.textContent = 'SA';
                avatar.appendChild(avatarInner);

                const content = document.createElement('div');
                content.className = 'user-chat-content';

                const textWrap = document.createElement('div');
                textWrap.className = 'ctext-wrap';
                const textContent = document.createElement('div');
                textContent.className = 'ctext-wrap-content';
                const paragraph = document.createElement('p');
                paragraph.className = 'mb-0';
                paragraph.style.whiteSpace = 'pre-wrap';
                paragraph.textContent = data.message;
                textContent.appendChild(paragraph);
                textWrap.appendChild(textContent);

                const time = document.createElement('div');
                time.className = 'chat-time';
                const moment = data.created_at ? new Date(data.created_at) : new Date();
                time.textContent = moment.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' · Super Admin';

                content.appendChild(textWrap);
                content.appendChild(time);
                conversationList.appendChild(avatar);
                conversationList.appendChild(content);
                item.appendChild(conversationList);
                list.appendChild(item);

                input.value = '';
                input.focus();
                if (status) {
                    status.textContent = 'Sudah dibalas';
                    status.className = 'badge bg-success-subtle text-success';
                }
                if (conversation) conversation.scrollTop = conversation.scrollHeight;
            } catch (e) {
                if (errorBox) {
                    errorBox.textContent = e.message || 'Balasan gagal dikirim.';
                    errorBox.classList.remove('d-none');
                }
            } finally {
                form.dataset.busy = '0';
                submit.disabled = false;
            }
        });
    });
</script>
@endpush

@endsection
