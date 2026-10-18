@extends('layouts.master')
@section('title') Scan Gatepass @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.gatepass.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">Gatepass</a> @endslot
        @slot('title') Scan Gatepass @endslot
    @endcomponent

    <div class="row"><div class="col-lg-6 mx-auto">
        <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Pindai QR Santri</h5></div>
            <div class="card-body">
                <div id="reader" style="width:100%"></div>
                <div class="text-center text-muted small my-2">Arahkan kamera ke QR gatepass santri.</div>
                <div class="alert alert-info py-2 px-3 small d-flex justify-content-between align-items-center" id="offlineQueueInfo">
                    <span><i class="ri-cloud-off-line me-1"></i><span id="offlineQueueText">Antrean offline: 0</span></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="syncOfflineQueue">Sinkronkan</button>
                </div>
                <form method="POST" action="{{ route('user.asrama.gatepass.process', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" id="gatepassForm">
                    @csrf
                    <label class="form-label">Token QR (otomatis dari kamera / tempel manual)</label>
                    <input type="text" name="token" id="gatepassToken" class="form-control" required>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6"><input type="text" name="purpose" class="form-control" placeholder="Tujuan (opsional)"></div>
                        <div class="col-md-6"><input type="text" name="gate" class="form-control" placeholder="Gerbang (opsional)"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="ri-check-double-line me-1"></i>Proses Gatepass</button>
                </form>
            </div>
        </div>
    </div></div>
@endsection

@section('script')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    (function () {
        'use strict';

        var QUEUE_KEY = 'alim_gatepass_queue_v1';
        var form = document.getElementById('gatepassForm');
        var queueText = document.getElementById('offlineQueueText');
        var syncBtn = document.getElementById('syncOfflineQueue');

        function readQueue() {
            try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { return []; }
        }
        function writeQueue(items) { localStorage.setItem(QUEUE_KEY, JSON.stringify(items)); renderQueue(); }
        function renderQueue() {
            var n = readQueue().length;
            if (queueText) queueText.textContent = 'Antrean offline: ' + n;
            if (syncBtn) syncBtn.disabled = n === 0;
        }
        function enqueue(data) {
            var items = readQueue();
            items.push(data);
            writeQueue(items);
        }
        function submitPayload(payload) {
            var body = new FormData();
            Object.keys(payload).forEach(function (k) { body.append(k, payload[k]); });
            var token = form.querySelector('input[name="_token"]');
            if (token) body.append('_token', token.value);
            return fetch(form.action, {
                method: 'POST',
                body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
        }
        async function syncQueue() {
            var items = readQueue();
            if (!items.length || !navigator.onLine) return;
            var remain = [];
            for (var i = 0; i < items.length; i++) {
                try {
                    var res = await submitPayload(items[i]);
                    if (!res.ok) remain.push(items[i]);
                } catch (e) { remain.push(items[i]); }
            }
            writeQueue(remain);
        }

        renderQueue();
        window.addEventListener('online', syncQueue);

        form?.addEventListener('submit', function (event) {
            if (form.dataset.offlineHandled === '1') return;
            event.preventDefault();
            var payload = {
                token: document.getElementById('gatepassToken').value,
                purpose: form.querySelector('[name="purpose"]').value,
                gate: form.querySelector('[name="gate"]').value
            };
            if (!navigator.onLine) {
                enqueue(payload);
                alert('Tidak ada koneksi. Scan disimpan di antrean offline dan akan disinkronkan otomatis.');
                return;
            }
            form.dataset.offlineHandled = '1';
            form.submit();
        });

        syncBtn?.addEventListener('click', syncQueue);

        if (typeof Html5Qrcode === 'undefined') return;
        var scanner = new Html5Qrcode('reader');
        var input = document.getElementById('gatepassToken');

        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 }, function (text) {
            input.value = text;
            scanner.stop().catch(function () {});
            document.getElementById('gatepassForm').submit();
        }).catch(function () {
            var box = document.getElementById('reader');
            if (box) box.innerHTML = '<div class="alert alert-warning small mb-0">Kamera tidak dapat diakses. Tempel token QR secara manual.</div>';
        });
    })();
</script>
@endsection
