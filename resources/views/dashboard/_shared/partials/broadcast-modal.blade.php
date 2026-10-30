@php
    $first = $broadcasts->first();
    $modalId = 'broadcast-modal-'.substr(md5($user->id), 0, 6);
    $storageKey = 'alim.broadcast.seen.'.$user->id;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true"
     data-alim-broadcast-modal data-storage-key="{{ $storageKey }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-send-plane-2-line me-2 text-primary"></i>Broadcast
                    <span class="badge bg-info-subtle text-info ms-2" id="{{ $modalId }}-counter">
                        {{ $broadcasts->count() }} pesan
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 overflow-auto pb-2 mb-3" id="{{ $modalId }}-tabs">
                    @foreach($broadcasts as $index => $broadcast)
                        <button type="button"
                                class="btn btn-sm {{ $index === 0 ? 'btn-primary' : 'btn-light border' }} flex-shrink-0"
                                data-broadcast-tab="{{ $index }}">
                            {{ \Illuminate\Support\Str::limit($broadcast['title'], 28) }}
                        </button>
                    @endforeach
                </div>

                @foreach($broadcasts as $index => $broadcast)
                    @php
                        $severity = $broadcast['severity'];
                        $color = match ($severity) {
                            'emergency' => 'danger',
                            'urgent' => 'warning',
                            default => 'info',
                        };
                    @endphp
                    <div data-broadcast-panel="{{ $index }}" @if($index !== 0) style="display:none" @endif>
                        @if($broadcast['image'])
                            <img src="{{ $broadcast['image'] }}" alt="Lampiran broadcast" class="img-fluid rounded mb-3" style="max-height:240px;object-fit:cover;">
                        @endif
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst($severity) }}</span>
                            @if($broadcast['ack_required'])
                                <span class="badge bg-danger-subtle text-danger">Wajib dibaca</span>
                            @endif
                            @if($broadcast['published_at'])
                                <span class="badge bg-light text-muted">
                                    {{ \Illuminate\Support\Carbon::parse($broadcast['published_at'])->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        <h5 class="mb-2">{{ $broadcast['title'] }}</h5>
                        <p class="mb-3" style="white-space: pre-wrap;">{{ $broadcast['content'] }}</p>
                        <a href="{{ $broadcast['url'] }}" class="btn btn-sm btn-light border">
                            <i class="ri-external-link-line me-1"></i>Buka detail
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    <i class="ri-check-line me-1"></i>Tutup
                </button>
                @if($first['ack_required'])
                    <button type="button" class="btn btn-primary" data-broadcast-ack="{{ $first['id'] }}">
                        <i class="ri-check-double-line me-1"></i>Saya sudah membaca
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modalEl = document.getElementById(@json($modalId));
        if (!modalEl || !window.bootstrap) return;

        var storageKey = @json($storageKey);
        var items = @json($broadcasts->values());

        function seen() {
            try { return JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) { return []; }
        }

        function markSeen(id) {
            var list = seen();
            if (list.indexOf(id) === -1) {
                list.push(id);
                try { localStorage.setItem(storageKey, JSON.stringify(list)); } catch (e) {}
            }
        }

        var pending = items.filter(function (item) { return seen().indexOf(item.id) === -1; });
        if (!pending.length) return;

        // Tampilkan modal satu kali per pengguna per broadcast.
        window.setTimeout(function () {
            new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: !items.some(function (i) { return i.ack_required; }) }).show();
        }, 800);

        modalEl.querySelectorAll('[data-broadcast-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var index = tab.getAttribute('data-broadcast-tab');
                modalEl.querySelectorAll('[data-broadcast-tab]').forEach(function (other) {
                    other.classList.toggle('btn-primary', other === tab);
                    other.classList.toggle('btn-light', other !== tab);
                    other.classList.toggle('border', other !== tab);
                });
                modalEl.querySelectorAll('[data-broadcast-panel]').forEach(function (panel) {
                    panel.style.display = panel.getAttribute('data-broadcast-panel') === index ? '' : 'none';
                });
            });
        });

        function acknowledge(id) {
            var url = @json(route('user.dashboard.broadcasts.acknowledge', ['userId' => $user->id, 'announcementId' => '__ID__']));
            fetch(url.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(function (response) { return response.json(); })
              .then(function () { markSeen(id); })
              .catch(function () {});
        }

        modalEl.querySelectorAll('[data-broadcast-ack]').forEach(function (button) {
            button.addEventListener('click', function () {
                acknowledge(button.getAttribute('data-broadcast-ack'));
                bootstrap.Modal.getInstance(modalEl).hide();
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            items.forEach(function (item) { markSeen(item.id); });
        });
    })();
</script>
@endpush
