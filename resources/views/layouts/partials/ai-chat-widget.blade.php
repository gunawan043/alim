@auth
    @php $chatUserId = $userId ?? auth()->id(); @endphp

    <style>
        .ai-chat-fab {
            position: fixed; right: 22px; bottom: 22px; z-index: 1085;
            width: 56px; height: 56px; border: 0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 24px;
            background: linear-gradient(135deg, #405189 0%, #08a88a 100%);
            box-shadow: 0 10px 28px rgba(64, 81, 137, .35);
            transition: transform .2s ease;
        }
        .ai-chat-fab:hover { transform: translateY(-2px) scale(1.03); color: #fff; }
        .ai-chat-fab .ai-chat-dot {
            position: absolute; top: 12px; right: 12px; width: 9px; height: 9px;
            border-radius: 50%; background: #2ec4b6; border: 2px solid #fff;
        }
        .ai-chat-window { width: 420px; max-width: 100vw; }
        .ai-chat-body { background: #f8f9fc; height: calc(100vh - 190px); overflow-y: auto; }
        .ai-chat-bubble { max-width: 88%; padding: 10px 13px; border-radius: 14px; font-size: 13.5px; line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
        .ai-chat-user { margin-left: auto; background: #405189; color: #fff; border-bottom-right-radius: 4px; }
        .ai-chat-assistant { margin-right: auto; background: #fff; color: #212529; border: 1px solid #e9ecf2; border-bottom-left-radius: 4px; }
        .ai-chat-error { background: #fff4f4; border-color: #f5c2c7; color: #842029; }
        .ai-chat-suggestion { font-size: 11.5px; }
        .ai-chat-typing span { display: inline-block; width: 6px; height: 6px; margin: 0 2px; border-radius: 50%; background: #8a93a6; animation: aiTyping 1s infinite; }
        .ai-chat-typing span:nth-child(2) { animation-delay: .15s; }
        .ai-chat-typing span:nth-child(3) { animation-delay: .3s; }
        @keyframes aiTyping { 0%, 60%, 100% { opacity: .25; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @media (max-width: 575.98px) {
            .ai-chat-window { width: 100vw; }
            .ai-chat-fab { right: 14px; bottom: 14px; width: 52px; height: 52px; }
        }
        @media print { .ai-chat-fab, .ai-chat-window { display: none !important; } }
    </style>

    <button type="button" id="aiChatFab" class="ai-chat-fab" aria-label="Buka ALIM Assistant" title="Tanya ALIM AI">
        <i class="ri-sparkling-2-fill"></i>
        <span class="ai-chat-dot"></span>
    </button>

    <div class="offcanvas offcanvas-end ai-chat-window" tabindex="-1" id="aiChatOffcanvas" aria-labelledby="aiChatTitle">
        <div class="offcanvas-header bg-primary bg-gradient text-white">
            <div>
                <h5 class="offcanvas-title mb-0 text-white" id="aiChatTitle">
                    <i class="ri-sparkling-2-line me-1"></i>ALIM Assistant
                </h5>
                <small class="text-white-50">Pendidikan &amp; aplikasi ALIM</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light" id="aiChatNew" title="Percakapan baru">
                    <i class="ri-add-line"></i>
                </button>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
        </div>

        <div class="offcanvas-body p-0 d-flex flex-column">
            <div class="px-3 pt-3">
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    <i class="ri-shield-check-line me-1"></i>
                    AI hanya untuk topik pendidikan &amp; aplikasi. Data pribadi tidak ditampilkan.
                </div>
                <div id="aiChatSuggestions" class="d-flex flex-wrap gap-1 mb-2">
                    @foreach(config('ai_chat.suggestions', []) as $suggestion)
                        <button type="button" class="btn btn-sm btn-light border ai-chat-suggestion ai-chat-suggestion-btn">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </div>

            <div id="aiChatBody" class="ai-chat-body px-3 py-2 d-flex flex-column gap-2" aria-live="polite">
                <div class="text-center text-muted small my-4" id="aiChatEmpty">
                    <i class="ri-robot-2-line fs-2 d-block mb-2"></i>
                    Tanyakan seputar kurikulum, penilaian, tahfidz, atau cara memakai aplikasi ALIM.
                </div>
            </div>

            <div class="border-top p-3 bg-white">
                <div id="aiChatError" class="text-danger small mb-2 d-none"></div>
                <div class="d-flex align-items-end gap-2">
                    <textarea id="aiChatInput" class="form-control" rows="1" maxlength="2000"
                              placeholder="Tulis pertanyaan… (Enter kirim, Shift+Enter baris baru)"></textarea>
                    <button type="button" id="aiChatSend" class="btn btn-primary" title="Kirim">
                        <i class="ri-send-plane-2-line"></i>
                    </button>
                </div>
                <div class="text-muted mt-1" style="font-size:10.5px;">
                    Pengembang aplikasi: Friday. AI dapat membuat kesalahan — verifikasi data penting di menu terkait.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fab = document.getElementById('aiChatFab');
            const canvasEl = document.getElementById('aiChatOffcanvas');
            if (! fab || ! canvasEl) return;

            const body = document.getElementById('aiChatBody');
            const empty = document.getElementById('aiChatEmpty');
            const input = document.getElementById('aiChatInput');
            const sendBtn = document.getElementById('aiChatSend');
            const errorBox = document.getElementById('aiChatError');
            const newBtn = document.getElementById('aiChatNew');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const urls = {
                latest: @json(route('user.ai-chat.latest', ['userId' => $chatUserId])),
                send: @json(route('user.ai-chat.send', ['userId' => $chatUserId])),
            };

            let conversationId = null;
            let loading = false;
            let loaded = false;
            let typingEl = null;

            function setError(message) {
                errorBox.textContent = message || '';
                errorBox.classList.toggle('d-none', ! message);
            }

            function addBubble(role, text, isError) {
                if (empty) empty.style.display = 'none';
                const bubble = document.createElement('div');
                bubble.className = 'ai-chat-bubble ' + (role === 'user' ? 'ai-chat-user' : 'ai-chat-assistant') + (isError ? ' ai-chat-error' : '');
                bubble.textContent = text;
                body.appendChild(bubble);
                body.scrollTop = body.scrollHeight;
                return bubble;
            }

            function showTyping() {
                if (typingEl) return;
                typingEl = document.createElement('div');
                typingEl.className = 'ai-chat-bubble ai-chat-assistant ai-chat-typing';
                typingEl.innerHTML = '<span></span><span></span><span></span>';
                body.appendChild(typingEl);
                body.scrollTop = body.scrollHeight;
            }

            function hideTyping() {
                if (typingEl) { typingEl.remove(); typingEl = null; }
            }

            function resetChat() {
                conversationId = null;
                loaded = true;
                if (body) {
                    body.innerHTML = '';
                }
                const hint = document.createElement('div');
                hint.className = 'text-center text-muted small my-4';
                hint.innerHTML = '<i class="ri-robot-2-line fs-2 d-block mb-2"></i>Tanyakan seputar kurikulum, penilaian, tahfidz, atau cara memakai aplikasi ALIM.';
                body.appendChild(hint);
                setError('');
                input.value = '';
                input.style.height = 'auto';
            }

            async function loadLatest() {
                if (loaded) return;
                loaded = true;
                try {
                    const response = await fetch(urls.latest, { headers: { 'Accept': 'application/json' } });
                    if (! response.ok) return;
                    const data = await response.json();
                    if (data.conversation) conversationId = data.conversation.id;
                    (data.messages || []).forEach(function (message) {
                        addBubble(message.role === 'user' ? 'user' : 'assistant', message.content);
                    });
                } catch (e) {
                    // Diamkan: chat tetap bisa dimulai baru.
                }
            }

            async function sendMessage() {
                const message = (input.value || '').trim();
                if (! message || loading) return;

                loading = true;
                sendBtn.disabled = true;
                setError('');
                addBubble('user', message);
                input.value = '';
                input.style.height = 'auto';
                showTyping();

                try {
                    const response = await fetch(urls.send, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            message: message,
                            conversation_id: conversationId,
                            page: (document.title || '').slice(0, 150),
                        }),
                    });

                    const data = await response.json().catch(function () { return {}; });
                    hideTyping();

                    if (! response.ok || ! data.ok) {
                        addBubble('assistant', data.message || 'AI sedang tidak dapat menjawab.', true);
                    } else {
                        conversationId = data.conversation_id || conversationId;
                        addBubble('assistant', data.reply || '');
                    }
                } catch (e) {
                    hideTyping();
                    addBubble('assistant', 'Koneksi ke AI gagal. Coba lagi.', true);
                } finally {
                    loading = false;
                    sendBtn.disabled = false;
                    input.focus();
                }
            }

            fab.addEventListener('click', function () {
                bootstrap.Offcanvas.getOrCreateInstance(canvasEl).show();
                loadLatest();
                setTimeout(function () { input.focus(); }, 350);
            });

            sendBtn.addEventListener('click', sendMessage);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && ! event.shiftKey) {
                    event.preventDefault();
                    sendMessage();
                }
            });
            input.addEventListener('input', function () {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 120) + 'px';
            });

            newBtn.addEventListener('click', resetChat);

            document.querySelectorAll('.ai-chat-suggestion-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    input.value = button.textContent.trim();
                    input.focus();
                });
            });
        });
    </script>
@endauth
