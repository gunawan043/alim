@auth
    @php $chatUserId = $userId ?? auth()->id(); @endphp

    <style>
        :root {
            --ai-ink: #1f2a44;
            --ai-muted: #6b7487;
            --ai-line: #e5e9f2;
            --ai-surface: #ffffff;
            --ai-canvas: #f5f7fb;
            --ai-brand: #405189;
            --ai-brand-2: #08a88a;
        }

        .ai-chat-fab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1085;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 18px 0 14px;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 999px;
            color: #fff;
            background: linear-gradient(135deg, var(--ai-brand) 0%, #2f6f8f 55%, var(--ai-brand-2) 100%);
            box-shadow: 0 12px 28px rgba(31, 42, 68, .28), 0 2px 6px rgba(31, 42, 68, .16);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .01em;
            transition-property: transform, box-shadow, filter;
            transition-duration: 160ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-fab:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(31, 42, 68, .32), 0 3px 8px rgba(31, 42, 68, .18);
            filter: saturate(1.05);
        }
        .ai-chat-fab:active { transform: scale(.97); }
        .ai-chat-fab:focus-visible {
            outline: 3px solid rgba(8, 168, 138, .35);
            outline-offset: 3px;
        }
        .ai-chat-fab-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .16);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .18);
            font-size: 17px;
            position: relative;
        }
        .ai-chat-fab-icon::after {
            content: "";
            position: absolute;
            top: 1px;
            right: 1px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #5eead4;
            box-shadow: 0 0 0 2px #2f6f8f;
        }
        .ai-chat-fab.is-hidden {
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(.96);
            pointer-events: none;
        }

        .ai-chat-panel {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1086;
            width: min(400px, calc(100vw - 32px));
            height: min(620px, calc(100vh - 48px));
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(31, 42, 68, .12);
            border-radius: 20px;
            background: var(--ai-surface);
            box-shadow: 0 24px 60px rgba(31, 42, 68, .24), 0 8px 20px rgba(31, 42, 68, .12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(14px) scale(.98);
            transform-origin: bottom right;
            transition-property: opacity, transform, visibility;
            transition-duration: 180ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-panel.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .ai-chat-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            color: #fff;
            background:
                radial-gradient(120% 160% at 10% 0%, rgba(255, 255, 255, .16) 0%, transparent 55%),
                linear-gradient(135deg, #35457a 0%, #405189 52%, #0d8f7a 100%);
        }
        .ai-chat-brand { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .ai-chat-brand > div { min-width: 0; }
        .ai-chat-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .16);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .22);
            font-size: 19px;
        }
        .ai-chat-brand h6 {
            margin: 0;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .01em;
            text-wrap: balance;
        }
        .ai-chat-brand .ai-chat-sub {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
            color: rgba(255, 255, 255, .76);
            font-size: 11px;
            font-weight: 500;
        }
        .ai-chat-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #5eead4;
            box-shadow: 0 0 0 3px rgba(94, 234, 212, .18);
        }
        .ai-chat-head-actions { display: flex; align-items: center; gap: 4px; }
        .ai-chat-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 10px;
            color: rgba(255, 255, 255, .86);
            background: transparent;
            font-size: 18px;
            transition-property: background-color, color, transform;
            transition-duration: 140ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-icon-btn:hover { color: #fff; background: rgba(255, 255, 255, .14); }
        .ai-chat-icon-btn:active { transform: scale(.94); }
        .ai-chat-icon-btn:focus-visible { outline: 2px solid rgba(255, 255, 255, .55); outline-offset: 2px; }

        .ai-chat-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 18px 16px 10px;
            background:
                radial-gradient(70% 40% at 100% 0%, rgba(8, 168, 138, .05) 0%, transparent 60%),
                var(--ai-canvas);
            scrollbar-width: thin;
            scrollbar-color: #c9d1e0 transparent;
        }
        .ai-chat-body::-webkit-scrollbar { width: 6px; }
        .ai-chat-body::-webkit-scrollbar-thumb { background: #c9d1e0; border-radius: 999px; }

        .ai-chat-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100%;
            padding: 8px 6px 18px;
            text-align: center;
        }
        .ai-chat-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            margin-bottom: 12px;
            border-radius: 18px;
            color: var(--ai-brand);
            background: linear-gradient(145deg, #eef1fb, #e8f7f3);
            box-shadow: inset 0 0 0 1px rgba(64, 81, 137, .08);
            font-size: 27px;
        }
        .ai-chat-empty h6 {
            margin: 0 0 5px;
            color: var(--ai-ink);
            font-size: 14px;
            font-weight: 700;
            text-wrap: balance;
        }
        .ai-chat-empty p {
            max-width: 290px;
            margin: 0;
            color: var(--ai-muted);
            font-size: 12px;
            line-height: 1.55;
            text-wrap: pretty;
        }
        .ai-chat-suggestions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7px;
            width: 100%;
            margin-top: 16px;
        }
        .ai-chat-suggestion {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            min-height: 46px;
            padding: 9px 10px;
            border: 1px solid var(--ai-line);
            border-radius: 12px;
            color: #3b465c;
            background: rgba(255, 255, 255, .82);
            font-size: 11.5px;
            line-height: 1.4;
            text-align: left;
            transition-property: border-color, background-color, transform, box-shadow;
            transition-duration: 140ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-suggestion:hover {
            border-color: #c8d2e6;
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(31, 42, 68, .07);
        }
        .ai-chat-suggestion:active { transform: scale(.99); }
        .ai-chat-suggestion i { color: var(--ai-brand-2); font-size: 14px; margin-top: 1px; }

        .ai-chat-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            margin-bottom: 13px;
        }
        .ai-chat-row.is-user { justify-content: flex-end; }
        .ai-chat-avatar-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 28px;
            height: 28px;
            margin-bottom: 2px;
            border-radius: 9px;
            color: #fff;
            background: linear-gradient(140deg, var(--ai-brand), var(--ai-brand-2));
            box-shadow: 0 3px 8px rgba(64, 81, 137, .2);
            font-size: 13px;
        }
        .ai-chat-bubble-wrap { max-width: 82%; }
        .ai-chat-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 2px 4px;
            color: var(--ai-muted);
            font-size: 10px;
            font-weight: 500;
        }
        .ai-chat-row.is-user .ai-chat-meta { justify-content: flex-end; }
        .ai-chat-bubble {
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.58;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            text-wrap: pretty;
        }
        .ai-chat-row.is-assistant .ai-chat-bubble {
            border: 1px solid var(--ai-line);
            border-bottom-left-radius: 5px;
            color: #28344a;
            background: #fff;
            box-shadow: 0 1px 2px rgba(31, 42, 68, .04);
        }
        .ai-chat-row.is-user .ai-chat-bubble {
            border-bottom-right-radius: 5px;
            color: #fff;
            background: linear-gradient(135deg, #405189, #356f91);
            box-shadow: 0 4px 12px rgba(64, 81, 137, .18);
        }
        .ai-chat-row.is-admin .ai-chat-bubble {
            background: linear-gradient(135deg, #0d8f7a, #08a88a);
            box-shadow: 0 4px 12px rgba(8, 168, 138, .18);
        }
        .ai-chat-system {
            align-self: center;
            max-width: 92%;
            padding: 5px 10px;
            border-radius: 999px;
            color: #6b7487;
            background: rgba(255, 255, 255, .72);
            border: 1px solid var(--ai-line);
            font-size: 10.5px;
            text-align: center;
        }
        .ai-chat-bubble.is-error {
            color: #842029;
            border-color: #f1c7cc;
            background: #fff7f7;
        }
        .ai-chat-typing {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 39px;
            padding: 10px 13px;
        }
        .ai-chat-typing span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #94a0b8;
            animation: aiChatTyping 1.05s infinite ease-in-out;
        }
        .ai-chat-typing span:nth-child(2) { animation-delay: .14s; }
        .ai-chat-typing span:nth-child(3) { animation-delay: .28s; }
        @keyframes aiChatTyping {
            0%, 60%, 100% { opacity: .35; transform: translateY(0); }
            30% { opacity: 1; transform: translateY(-3px); }
        }

        .ai-chat-composer {
            flex: 0 0 auto;
            padding: 10px 14px 12px;
            border-top: 1px solid var(--ai-line);
            background: #fff;
        }
        .ai-chat-composer-box {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 7px 7px 7px 12px;
            border: 1px solid #dbe2ee;
            border-radius: 16px;
            background: #fbfcff;
            transition-property: border-color, box-shadow, background-color;
            transition-duration: 150ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-composer-box:focus-within {
            border-color: rgba(64, 81, 137, .45);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(64, 81, 137, .09);
        }
        .ai-chat-input {
            flex: 1 1 auto;
            min-height: 30px;
            max-height: 112px;
            padding: 4px 0;
            border: 0;
            outline: 0;
            resize: none;
            color: var(--ai-ink);
            background: transparent;
            font-family: inherit;
            font-size: 13px;
            letter-spacing: normal;
            line-height: 1.5;
        }
        .ai-chat-input::placeholder { color: #98a2b5; }
        .ai-chat-send {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, var(--ai-brand), var(--ai-brand-2));
            box-shadow: 0 5px 12px rgba(64, 81, 137, .22);
            font-size: 18px;
            transition-property: transform, box-shadow, opacity;
            transition-duration: 140ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-send:hover { transform: translateY(-1px); box-shadow: 0 7px 16px rgba(64, 81, 137, .28); }
        .ai-chat-send:active { transform: scale(.95); }
        .ai-chat-send:disabled { opacity: .45; transform: none; box-shadow: none; cursor: not-allowed; }
        .ai-chat-send:focus-visible { outline: 3px solid rgba(8, 168, 138, .3); outline-offset: 2px; }
        .ai-chat-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 6px;
            color: #8b95a7;
            font-size: 10px;
            font-variant-numeric: tabular-nums;
        }
        .ai-chat-count { flex: 0 0 auto; }
        .ai-chat-error {
            display: none;
            margin-bottom: 7px;
            padding: 7px 9px;
            border: 1px solid #f1c7cc;
            border-radius: 9px;
            color: #842029;
            background: #fff7f7;
            font-size: 11px;
        }
        .ai-chat-error.is-visible { display: block; }
        .ai-chat-history {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 12px 14px;
            background: var(--ai-canvas);
        }
        .ai-chat-history-item {
            width: 100%;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 10px;
            margin-bottom: 7px;
            border: 1px solid var(--ai-line);
            border-radius: 12px;
            background: #fff;
            text-align: left;
            transition-property: border-color, box-shadow, transform;
            transition-duration: 140ms;
            transition-timing-function: ease-out;
        }
        .ai-chat-history-item:hover {
            border-color: #c8d2e6;
            box-shadow: 0 4px 12px rgba(31, 42, 68, .07);
            transform: translateY(-1px);
        }
        .ai-chat-history-item .ai-chat-history-icon {
            width: 32px;
            height: 32px;
            flex: 0 0 auto;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ai-brand);
            background: #eef1fb;
        }
        .ai-chat-history-item .ai-chat-history-title {
            color: var(--ai-ink);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.35;
            text-wrap: pretty;
        }
        .ai-chat-history-item .ai-chat-history-meta {
            color: var(--ai-muted);
            font-size: 10px;
            margin-top: 2px;
        }

        @media (max-width: 575.98px) {
            .ai-chat-fab {
                right: 16px;
                bottom: 16px;
                padding: 0 14px 0 10px;
                min-height: 48px;
            }
            .ai-chat-fab-label { display: none; }
            .ai-chat-panel {
                right: 8px;
                bottom: 8px;
                width: calc(100vw - 16px);
                height: calc(100vh - 16px);
                border-radius: 16px;
            }
            .ai-chat-head { padding: 12px 14px; }
            .ai-chat-suggestions { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            .ai-chat-fab, .ai-chat-panel, .ai-chat-icon-btn, .ai-chat-suggestion,
            .ai-chat-composer-box, .ai-chat-send, .ai-chat-typing span {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
        @media print {
            .ai-chat-fab, .ai-chat-panel { display: none !important; }
        }
    </style>

    <button type="button" id="aiChatFab" class="ai-chat-fab" aria-label="Buka ALIM Assistant" aria-expanded="false" aria-controls="aiChatPanel">
        <span class="ai-chat-fab-icon"><i class="ri-sparkling-2-fill"></i></span>
        <span class="ai-chat-fab-label">Tanya ALIM AI</span>
    </button>

    <div id="aiChatPanel" class="ai-chat-panel" role="dialog" aria-modal="false" aria-labelledby="aiChatTitle" aria-hidden="true">
        <div class="ai-chat-head">
            <div class="ai-chat-brand">
                <span class="ai-chat-avatar"><i class="ri-sparkling-2-fill"></i></span>
                <div class="min-w-0">
                    <h6 id="aiChatTitle">ALIM Assistant</h6>
                    <div class="ai-chat-sub">
                        <span class="ai-chat-status-dot"></span>
                        <span id="aiChatSubtitle">Pendidikan &amp; aplikasi ALIM</span>
                    </div>
                </div>
            </div>
            <div class="ai-chat-head-actions">
                <button type="button" class="ai-chat-icon-btn" id="aiChatHistoryBtn" title="Riwayat percakapan" aria-label="Riwayat percakapan">
                    <i class="ri-history-line"></i>
                </button>
                <button type="button" class="ai-chat-icon-btn" id="aiChatNew" title="Percakapan baru" aria-label="Percakapan baru">
                    <i class="ri-add-line"></i>
                </button>
                <button type="button" class="ai-chat-icon-btn" id="aiChatClose" title="Tutup" aria-label="Tutup chat">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div>

        <div id="aiChatBody" class="ai-chat-body" aria-live="polite">
            <div class="ai-chat-empty" id="aiChatEmpty">
                <span class="ai-chat-empty-icon"><i class="ri-chat-smile-2-line"></i></span>
                <h6>Ada yang bisa dibantu?</h6>
                <p>Tanyakan seputar kurikulum, penilaian, tahfidz, atau cara memakai aplikasi ALIM.</p>
                <div class="ai-chat-suggestions" id="aiChatSuggestions">
                    @foreach(config('ai_chat.suggestions', []) as $suggestion)
                        <button type="button" class="ai-chat-suggestion ai-chat-suggestion-btn">
                            <i class="ri-arrow-right-up-line"></i>
                            <span>{{ $suggestion }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="aiChatHistory" class="ai-chat-history d-none" aria-label="Riwayat percakapan"></div>

        <div class="ai-chat-composer">
            <div class="d-flex align-items-center justify-content-between px-3 pt-2" style="font-size:11px;">
                <button type="button" id="aiChatSupportToggle" class="btn btn-link btn-sm p-0 text-decoration-none">
                    <i class="ri-customer-service-2-line me-1"></i>Hubungi Super Admin
                </button>
                <span id="aiChatModeBadge" class="badge bg-info-subtle text-info">AI</span>
            </div>
            <div id="aiChatError" class="ai-chat-error" role="alert"></div>
            <div class="ai-chat-composer-box">
                <textarea id="aiChatInput" class="ai-chat-input" rows="1" maxlength="2000"
                          placeholder="Tulis pertanyaan…" aria-label="Pesan untuk ALIM Assistant"></textarea>
                <button type="button" id="aiChatSend" class="ai-chat-send" title="Kirim" aria-label="Kirim pesan">
                    <i class="ri-send-plane-2-fill"></i>
                </button>
            </div>
            <div class="ai-chat-foot">
                <span>Data pribadi hanya untuk tugas resmi. Verifikasi data penting.</span>
                <span class="ai-chat-count" id="aiChatCount">0/2000</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fab = document.getElementById('aiChatFab');
            const panel = document.getElementById('aiChatPanel');
            if (! fab || ! panel) return;

            const body = document.getElementById('aiChatBody');
            const historyEl = document.getElementById('aiChatHistory');
            const input = document.getElementById('aiChatInput');
            const sendBtn = document.getElementById('aiChatSend');
            const closeBtn = document.getElementById('aiChatClose');
            const newBtn = document.getElementById('aiChatNew');
            const historyBtn = document.getElementById('aiChatHistoryBtn');
            const supportToggle = document.getElementById('aiChatSupportToggle');
            const modeBadge = document.getElementById('aiChatModeBadge');
            const subtitle = document.getElementById('aiChatSubtitle');
            const errorBox = document.getElementById('aiChatError');
            const count = document.getElementById('aiChatCount');
            const composer = panel.querySelector('.ai-chat-composer');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const urls = {
                conversations: @json(route('user.ai-chat.conversations', ['userId' => $chatUserId])),
                send: @json(route('user.ai-chat.send', ['userId' => $chatUserId])),
                show: @json(route('user.ai-chat.show', ['userId' => $chatUserId, 'conversation' => '__ID__'])),
            };

            const initialEmpty = body.querySelector('.ai-chat-empty')?.cloneNode(true);
            let conversationId = null;
            let supportMode = false;
            let supportClosed = false;
            let loading = false;
            let typingEl = null;
            let pollTimer = null;
            let historyOpen = false;
            let renderedIds = new Set();

            const timeNow = function () {
                return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            };

            const formatTime = function (value) {
                if (! value) return timeNow();
                const date = new Date(value);
                return isNaN(date.getTime())
                    ? timeNow()
                    : date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            };

            const setError = function (message) {
                errorBox.textContent = message || '';
                errorBox.classList.toggle('is-visible', !! message);
            };

            const scrollToBottom = function () {
                body.scrollTop = body.scrollHeight;
            };

            const stopPolling = function () {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            };

            const updateModeUi = function () {
                if (supportMode) {
                    subtitle.textContent = 'Bantuan Super Admin';
                    subtitle.previousElementSibling?.classList.add('bg-warning');
                    modeBadge.textContent = supportClosed ? 'Bantuan ditutup' : 'Bantuan';
                    modeBadge.className = 'badge ' + (supportClosed ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning');
                    supportToggle.innerHTML = '<i class="ri-arrow-left-line me-1"></i>Kembali ke AI';
                    input.placeholder = supportClosed ? 'Percakapan bantuan sudah ditutup' : 'Tulis pesan untuk Super Admin…';
                } else {
                    subtitle.textContent = 'Pendidikan & aplikasi ALIM';
                    subtitle.previousElementSibling?.classList.remove('bg-warning');
                    modeBadge.textContent = 'AI';
                    modeBadge.className = 'badge bg-info-subtle text-info';
                    supportToggle.innerHTML = '<i class="ri-customer-service-2-line me-1"></i>Hubungi Super Admin';
                    input.placeholder = 'Tulis pertanyaan…';
                }
            };

            const clearBody = function () {
                body.querySelectorAll('.ai-chat-row, .ai-chat-empty, .ai-chat-system').forEach(function (el) { el.remove(); });
                renderedIds = new Set();
            };

            const addSystem = function (text) {
                body.querySelectorAll('.ai-chat-empty').forEach(function (el) { el.remove(); });
                const note = document.createElement('div');
                note.className = 'ai-chat-system';
                note.textContent = text;
                body.appendChild(note);
                scrollToBottom();
            };

            const addMessage = function (role, text, time, isError, id) {
                if (id && renderedIds.has(String(id))) return;
                if (id) renderedIds.add(String(id));

                body.querySelectorAll('.ai-chat-empty').forEach(function (el) { el.remove(); });

                const isUser = role === 'user';
                const isAdmin = role === 'admin';
                const row = document.createElement('div');
                row.className = 'ai-chat-row ' + (isUser || isAdmin ? 'is-user' : 'is-assistant') + (isAdmin ? ' is-admin' : '');

                if (! isUser && ! isAdmin) {
                    const avatar = document.createElement('span');
                    avatar.className = 'ai-chat-avatar-sm';
                    avatar.innerHTML = '<i class="ri-sparkling-2-fill"></i>';
                    row.appendChild(avatar);
                }

                const wrap = document.createElement('div');
                wrap.className = 'ai-chat-bubble-wrap';

                const meta = document.createElement('div');
                meta.className = 'ai-chat-meta';
                meta.textContent = (isAdmin ? 'Super Admin' : (isUser ? 'Anda' : 'ALIM Assistant')) + ' · ' + formatTime(time);
                wrap.appendChild(meta);

                const bubble = document.createElement('div');
                bubble.className = 'ai-chat-bubble' + (isError ? ' is-error' : '');
                bubble.textContent = text;
                wrap.appendChild(bubble);
                row.appendChild(wrap);

                body.appendChild(row);
                scrollToBottom();
            };

            const showTyping = function () {
                if (typingEl) return;
                typingEl = document.createElement('div');
                typingEl.className = 'ai-chat-row is-assistant';

                const avatar = document.createElement('span');
                avatar.className = 'ai-chat-avatar-sm';
                avatar.innerHTML = '<i class="ri-sparkling-2-fill"></i>';

                const bubble = document.createElement('div');
                bubble.className = 'ai-chat-bubble ai-chat-typing';
                bubble.innerHTML = '<span></span><span></span><span></span>';

                typingEl.appendChild(avatar);
                typingEl.appendChild(bubble);
                body.appendChild(typingEl);
                scrollToBottom();
            };

            const hideTyping = function () {
                if (typingEl) { typingEl.remove(); typingEl = null; }
            };

            const resetChat = function () {
                stopPolling();
                conversationId = null;
                supportMode = false;
                supportClosed = false;
                historyOpen = false;
                hideTyping();
                clearBody();
                setError('');
                historyEl.classList.add('d-none');
                body.classList.remove('d-none');
                composer?.classList.remove('d-none');

                if (initialEmpty) {
                    body.appendChild(initialEmpty.cloneNode(true));
                } else {
                    const hint = document.createElement('div');
                    hint.className = 'ai-chat-empty';
                    hint.innerHTML = '<span class="ai-chat-empty-icon"><i class="ri-chat-smile-2-line"></i></span>'
                        + '<h6>Ada yang bisa dibantu?</h6>'
                        + '<p>Tanyakan seputar kurikulum, penilaian, tahfidz, atau cara memakai aplikasi ALIM.</p>';
                    body.appendChild(hint);
                }

                updateModeUi();
                input.value = '';
                input.style.height = 'auto';
                count.textContent = '0/2000';
                input.focus();
            };

            const showHistory = async function () {
                stopPolling();
                historyOpen = true;
                body.classList.add('d-none');
                historyEl.classList.remove('d-none');
                composer?.classList.add('d-none');
                historyEl.innerHTML = '<div class="text-center text-muted small py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat riwayat…</div>';

                try {
                    const response = await fetch(urls.conversations, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    const items = data.items || [];

                    historyEl.innerHTML = '';
                    const header = document.createElement('div');
                    header.className = 'd-flex align-items-center justify-content-between mb-2';
                    const title = document.createElement('h6');
                    title.className = 'mb-0 fs-13';
                    title.textContent = 'Riwayat Percakapan';
                    const back = document.createElement('button');
                    back.type = 'button';
                    back.className = 'btn btn-sm btn-light';
                    back.innerHTML = '<i class="ri-arrow-left-line me-1"></i>Kembali';
                    back.addEventListener('click', hideHistory);
                    header.appendChild(title);
                    header.appendChild(back);
                    historyEl.appendChild(header);

                    if (! items.length) {
                        const empty = document.createElement('div');
                        empty.className = 'text-center text-muted small py-5';
                        empty.textContent = 'Belum ada riwayat percakapan.';
                        historyEl.appendChild(empty);
                        return;
                    }

                    items.forEach(function (item) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'ai-chat-history-item';
                        button.addEventListener('click', function () { loadConversation(item.id); });

                        const icon = document.createElement('span');
                        icon.className = 'ai-chat-history-icon';
                        icon.innerHTML = item.mode === 'support'
                            ? '<i class="ri-customer-service-2-line"></i>'
                            : '<i class="ri-sparkling-2-line"></i>';

                        const copy = document.createElement('span');
                        copy.className = 'flex-grow-1 min-w-0';
                        const itemTitle = document.createElement('div');
                        itemTitle.className = 'ai-chat-history-title';
                        itemTitle.textContent = item.title || 'Tanpa judul';
                        const meta = document.createElement('div');
                        meta.className = 'ai-chat-history-meta';
                        const when = item.last_message_at ? new Date(item.last_message_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
                        meta.textContent = (item.messages_count || 0) + ' pesan' + (when ? ' · ' + when : '');
                        copy.appendChild(itemTitle);
                        copy.appendChild(meta);

                        const badge = document.createElement('span');
                        badge.className = 'badge ' + (item.mode === 'support' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info');
                        badge.textContent = item.mode === 'support' ? (item.status === 'closed' ? 'Tutup' : 'Bantuan') : 'AI';

                        button.appendChild(icon);
                        button.appendChild(copy);
                        button.appendChild(badge);
                        historyEl.appendChild(button);
                    });
                } catch (e) {
                    historyEl.innerHTML = '<div class="text-center text-muted small py-4">Gagal memuat riwayat.</div>';
                }
            };

            const hideHistory = function () {
                historyOpen = false;
                historyEl.classList.add('d-none');
                body.classList.remove('d-none');
                composer?.classList.remove('d-none');
                if (supportMode && conversationId) startPolling();
                input.focus();
            };

            const loadConversation = async function (id) {
                try {
                    const response = await fetch(urls.show.replace('__ID__', encodeURIComponent(id)), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (! response.ok) return;
                    const data = await response.json();
                    renderConversation(data);
                } catch (e) {
                    setError('Gagal memuat percakapan.');
                }
            };

            const renderConversation = function (data) {
                const conversation = data.conversation || {};
                conversationId = conversation.id || null;
                supportMode = conversation.mode === 'support';
                supportClosed = conversation.status === 'closed';

                hideTyping();
                clearBody();
                setError('');
                historyOpen = false;
                historyEl.classList.add('d-none');
                body.classList.remove('d-none');
                composer?.classList.remove('d-none');
                updateModeUi();

                (data.messages || []).forEach(function (message) {
                    addMessage(message.role, message.content, message.created_at, false, message.id);
                });

                if (! (data.messages || []).length) {
                    addSystem('Belum ada pesan pada percakapan ini.');
                }

                if (supportMode && conversationId && ! supportClosed) {
                    startPolling();
                } else {
                    stopPolling();
                }

                scrollToBottom();
                input.focus();
            };

            const refreshConversation = async function () {
                if (! conversationId || ! supportMode) return;
                try {
                    const response = await fetch(urls.show.replace('__ID__', encodeURIComponent(conversationId)), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (! response.ok) return;
                    const data = await response.json();
                    supportClosed = data.conversation?.status === 'closed';
                    (data.messages || []).forEach(function (message) {
                        addMessage(message.role, message.content, message.created_at, false, message.id);
                    });
                    updateModeUi();
                    if (supportClosed) stopPolling();
                } catch (e) {
                    // Diam; polling akan mencoba lagi.
                }
            };

            const startPolling = function () {
                stopPolling();
                if (! supportMode || ! conversationId || supportClosed) return;
                pollTimer = setInterval(refreshConversation, 10000);
            };

            const openPanel = function () {
                panel.classList.add('is-open');
                panel.setAttribute('aria-hidden', 'false');
                fab.classList.add('is-hidden');
                fab.setAttribute('aria-expanded', 'true');
                if (supportMode && conversationId) startPolling();
                setTimeout(function () { input.focus(); }, 180);
            };

            const closePanel = function () {
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                fab.classList.remove('is-hidden');
                fab.setAttribute('aria-expanded', 'false');
                stopPolling();
                fab.focus();
            };

            const startSupport = function () {
                resetChat();
                supportMode = true;
                supportClosed = false;
                updateModeUi();
                addSystem('Mode Bantuan Super Admin aktif. Tulis pertanyaan atau kendala Anda; balasan akan muncul di sini.');
                input.focus();
            };

            const sendMessage = async function () {
                const message = (input.value || '').trim();
                if (! message || loading || supportClosed) return;

                loading = true;
                sendBtn.disabled = true;
                setError('');
                addMessage('user', message);
                input.value = '';
                input.style.height = 'auto';
                count.textContent = '0/2000';

                if (! supportMode) showTyping();
                else addSystem('Pesan dikirim ke Super Admin. Menunggu balasan…');

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
                            mode: supportMode ? 'support' : 'ai',
                        }),
                    });

                    const data = await response.json().catch(function () { return {}; });
                    hideTyping();

                    if (! response.ok || ! data.ok) {
                        addMessage('assistant', data.message || 'AI sedang tidak dapat menjawab.', null, true);
                    } else {
                        conversationId = data.conversation_id || conversationId;
                        if (data.support) {
                            supportMode = true;
                            supportClosed = data.status === 'closed';
                            updateModeUi();
                            if (conversationId) {
                                await loadConversation(conversationId);
                            }
                            startPolling();
                        } else {
                            addMessage('assistant', data.reply || '');
                        }
                    }
                } catch (e) {
                    hideTyping();
                    addMessage('assistant', 'Koneksi gagal. Coba lagi.', null, true);
                } finally {
                    loading = false;
                    sendBtn.disabled = false;
                    input.focus();
                }
            };

            fab.addEventListener('click', openPanel);
            closeBtn.addEventListener('click', closePanel);
            newBtn.addEventListener('click', resetChat);
            historyBtn.addEventListener('click', function () {
                if (historyOpen) hideHistory(); else showHistory();
            });
            supportToggle.addEventListener('click', function () {
                if (supportMode) resetChat(); else startSupport();
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
                input.style.height = Math.min(input.scrollHeight, 112) + 'px';
                count.textContent = input.value.length + '/2000';
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && panel.classList.contains('is-open')) {
                    if (historyOpen) { hideHistory(); } else { closePanel(); }
                }
            });

            // Delegasi agar tombol saran tetap bekerja setelah empty state di-clone.
            body.addEventListener('click', function (event) {
                const button = event.target.closest('.ai-chat-suggestion-btn');
                if (! button) return;
                input.value = button.textContent.trim();
                input.dispatchEvent(new Event('input'));
                input.focus();
            });

            updateModeUi();
        });
    </script>
@endauth
