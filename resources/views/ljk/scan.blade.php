@extends('layouts.master')
@section('title') Pindai LJK — {{ $exam->judul }} @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php
        $badge = ['aktif' => 'primary', 'pengumpulan' => 'warning', 'review' => 'warning', 'final' => 'success', 'tersinkron' => 'success'][$exam->status] ?? 'secondary';
        $sudahDipindai = $participants->filter(fn ($p) => in_array($p->status, [\App\Models\LjkParticipant::STATUS_DIPINDAI, \App\Models\LjkParticipant::STATUS_DIVERIFIKASI, \App\Models\LjkParticipant::STATUS_FINAL], true))->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('li_3')
            <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}">{{ $exam->code }}</a>
        @endslot
        @slot('title') Pindai LJK @endslot
    @endcomponent

    {{-- KEPALA UJIAN --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <h5 class="card-title mb-1">
                        {{ $exam->judul }}
                        <span class="badge bg-info-subtle text-info ms-1"><code>{{ $exam->code }}</code></span>
                        <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $exam->statusLabel() }}</span>
                    </h5>
                    <p class="text-muted mb-0 stat-label">
                        {{ $exam->studyGroup?->name ?? '-' }} · {{ $exam->subject?->name ?? '-' }} ·
                        {{ $exam->jumlah_soal }} soal · {{ $participants->count() }} peserta
                        ({{ $sudahDipindai }} sudah dipindai)
                    </p>
                </div>
                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-soft-primary">
                            <i class="ri-list-check-2 align-bottom me-1"></i> Hasil &amp; Verifikasi
                        </a>
                        <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-soft-secondary">
                            <i class="ri-printer-line align-bottom me-1"></i> Cetak Lembar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- KAMERA --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-camera-3-line me-1 text-primary"></i>Kamera HP</h5>
                        <span class="badge bg-secondary-subtle text-secondary" id="camera-status">Kamera belum aktif</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="camera-error" class="alert alert-warning d-none"></div>

                    <div class="ljk-camera-shell mb-3">
                        <video id="ljk-video" playsinline autoplay muted></video>
                        <canvas id="ljk-canvas" class="d-none"></canvas>

                        <div id="guide-overlay" class="ljk-camera-guide">
                            <div class="frame">
                                <span class="badge bg-dark hint">Seluruh lembar di dalam kotak</span>
                                <span class="ljk-corner tl"></span>
                                <span class="ljk-corner tr"></span>
                                <span class="ljk-corner bl"></span>
                                <span class="ljk-corner br"></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" id="btn-start" class="btn btn-primary">
                            <i class="ri-camera-lens-line me-1"></i> Aktifkan Kamera
                        </button>
                        <button type="button" id="btn-capture" class="btn btn-success" disabled>
                            <i class="ri-camera-line me-1"></i> Ambil Foto Lembar
                        </button>
                        <button type="button" id="btn-stop" class="btn btn-soft-secondary" disabled>
                            <i class="ri-stop-circle-line me-1"></i> Matikan
                        </button>
                        <label class="btn btn-soft-dark mb-0">
                            <i class="ri-upload-2-line me-1"></i> Unggah dari Galeri
                            <input type="file" id="file-input" accept="image/*" capture="environment" class="d-none">
                        </label>
                    </div>

                    <div class="alert alert-info mb-0 py-2" style="font-size:0.8125rem;">
                        <i class="ri-information-line me-1"></i>
                        Satu foto = satu lembar. Foto dikecilkan otomatis sebelum diunggah; pemrosesan OMR berjalan di server.
                    </div>
                </div>
            </div>
        </div>

        {{-- IDENTITAS + HASIL --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-id-card-line me-1 text-primary"></i>Identitas Lembar</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Peserta</label>
                        <select id="participant-select" class="form-select">
                            <option value="">— pilih peserta —</option>
                            @foreach ($participants as $participant)
                                <option value="{{ $participant->id }}">
                                    {{ $participant->nomor_peserta }} · {{ $participant->student?->name }}
                                    @if ($participant->sheet) ({{ $participant->sheet->sheet_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn btn-soft-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#qr-modal">
                        <i class="ri-qr-scan-2-line me-1"></i> Pindai QR Lembar (otomatis)
                    </button>

                    <div id="qr-result" class="alert alert-success py-2 d-none mb-2" style="font-size:0.8125rem;"></div>

                    <div class="d-flex align-items-start gap-2 text-muted stat-label">
                        <i class="ri-shield-check-line text-success fs-5"></i>
                        <span>QR/data strip adalah identitas mesin lembar. Server tetap memverifikasi
                            keasliannya meskipun peserta dipilih manual.</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-article-line me-1 text-primary"></i>Panduan Foto</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ljk-guide-step">
                        <span class="step-no">1</span>
                        <span>Letakkan lembar di permukaan datar, seluruh tepi masuk ke dalam bingkai.</span>
                    </div>
                    <div class="ljk-guide-step">
                        <span class="step-no">2</span>
                        <span>Pastikan <strong>4 kotak hitam di sudut</strong> lembar terlihat penuh (lihat penanda putih pada panduan).</span>
                    </div>
                    <div class="ljk-guide-step">
                        <span class="step-no">3</span>
                        <span>Foto dari atas, HP sejajar lembar, hindari bayangan badan/HP menutup lembar.</span>
                    </div>
                    <div class="ljk-guide-step">
                        <span class="step-no">4</span>
                        <span>Gunakan pencahayaan cukup — hasil terbaik di ruangan terang.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-list-check-2 me-1 text-primary"></i>Hasil Pembacaan</h5>
                        <a href="#" id="link-periksa" class="btn btn-sm btn-soft-primary d-none">
                            <i class="ri-search-eye-line me-1"></i> Periksa
                        </a>
                    </div>
                </div>
                <div class="card-body" id="result-panel">
                    <div class="text-center py-4">
                        <div class="avatar-lg mx-auto mb-2">
                            <span class="avatar-title bg-light rounded-circle fs-1">
                                <i class="ri-scan-2-line text-muted"></i>
                            </span>
                        </div>
                        <p class="text-muted mb-0">Belum ada pemindaian pada sesi ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL QR --}}
    <div class="modal fade" id="qr-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-qr-scan-2-line me-1"></i>Pindai QR Lembar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="qr-reader" style="width:100%"></div>
                    <div class="text-muted stat-label mt-2">Arahkan kamera ke QR di kanan atas lembar LJK.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    // Precompute URL agar tidak ada tanda kurung bersarang di dalam @json().
    $ljkUploadUrl = route('user.ljk.scan.store', ['userId' => $userId, 'ljkUuid' => $exam->id]);
    $ljkReviewUrl = route('user.ljk.hasil.peserta', ['userId' => $userId, 'ljkUuid' => $exam->id, 'participantUuid' => '__ID__']);
@endphp

@section('script')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const examId = @json($exam->id);
    const userId = @json($userId);
    const uploadUrl = @json($ljkUploadUrl);
    const reviewUrlBase = @json($ljkReviewUrl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const video = document.getElementById('ljk-video');
    const canvas = document.getElementById('ljk-canvas');
    const btnStart = document.getElementById('btn-start');
    const btnCapture = document.getElementById('btn-capture');
    const btnStop = document.getElementById('btn-stop');
    const fileInput = document.getElementById('file-input');
    const participantSelect = document.getElementById('participant-select');
    const resultPanel = document.getElementById('result-panel');
    const cameraError = document.getElementById('camera-error');
    const cameraStatus = document.getElementById('camera-status');
    const qrResult = document.getElementById('qr-result');
    const linkPeriksa = document.getElementById('link-periksa');

    let stream = null;
    let qrPayload = null;
    let busy = false;
    const MAX_EDGE = 1800;

    function showError(message) {
        cameraError.textContent = message;
        cameraError.classList.remove('d-none');
    }

    async function startCamera() {
        cameraError.classList.add('d-none');
        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('Browser tidak mendukung kamera (perlu HTTPS atau localhost).');
            }
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 2560 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            btnCapture.disabled = false;
            btnStop.disabled = false;
            btnStart.disabled = true;
            cameraStatus.textContent = 'Kamera aktif';
            cameraStatus.className = 'badge bg-success-subtle text-success';
        } catch (e) {
            showError('Tidak dapat mengakses kamera: ' + e.message + ' — gunakan tombol "Unggah dari Galeri".');
            cameraStatus.textContent = 'Kamera gagal';
            cameraStatus.className = 'badge bg-danger-subtle text-danger';
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.srcObject = null;
        btnCapture.disabled = true;
        btnStop.disabled = true;
        btnStart.disabled = false;
        cameraStatus.textContent = 'Kamera belum aktif';
        cameraStatus.className = 'badge bg-secondary-subtle text-secondary';
    }

    function downscaleToBlob(source, width, height) {
        const ratio = Math.min(1, MAX_EDGE / Math.max(width, height));
        const w = Math.round(width * ratio);
        const h = Math.round(height * ratio);
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(source, 0, 0, w, h);
        return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    }

    async function captureFromVideo() {
        if (!video.videoWidth) return;
        const blob = await downscaleToBlob(video, video.videoWidth, video.videoHeight);
        await upload(blob);
    }

    fileInput.addEventListener('change', async function () {
        const file = fileInput.files?.[0];
        if (!file) return;
        const img = new Image();
        img.onload = async function () {
            const blob = await downscaleToBlob(img, img.naturalWidth, img.naturalHeight);
            await upload(blob);
        };
        img.src = URL.createObjectURL(file);
        fileInput.value = '';
    });

    async function upload(blob) {
        if (busy) return;
        busy = true;
        btnCapture.disabled = true;
        cameraStatus.textContent = 'Memproses…';
        cameraStatus.className = 'badge bg-warning-subtle text-warning';

        const form = new FormData();
        form.append('image', blob, 'ljk.jpg');
        if (qrPayload) {
            form.append('qr_payload', qrPayload);
        } else if (participantSelect.value) {
            form.append('participant_id', participantSelect.value);
        }

        try {
            const res = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: form,
            });
            const json = await res.json();
            renderResult(res.ok && json.success, json);
        } catch (e) {
            renderResult(false, { message: 'Gagal mengunggah: ' + e.message });
        } finally {
            busy = false;
            if (stream) { btnCapture.disabled = false; }
            cameraStatus.textContent = stream ? 'Kamera aktif' : 'Kamera belum aktif';
            cameraStatus.className = stream ? 'badge bg-success-subtle text-success' : 'badge bg-secondary-subtle text-secondary';
        }
    }

    function badgeFor(status) {
        const map = {
            terbaca: 'success', kosong: 'secondary', ganda: 'danger',
            meragukan: 'warning', tidak_dapat_diproses: 'dark', manual: 'info',
        };
        return map[status] || 'secondary';
    }

    function renderResult(ok, json) {
        linkPeriksa.classList.add('d-none');

        if (!ok || !json.success) {
            resultPanel.innerHTML = ''
                + '<div class="alert alert-danger mb-0"><strong>Tidak dapat diproses.</strong><br>'
                + (json.message || 'Coba ambil ulang foto.')
                + '</div>';
            return;
        }

        const data = json.data;
        const counts = data.summary.counts;
        const participant = data.participant || {};

        let html = ''
            + '<div class="d-flex align-items-center gap-2 mb-2">'
            + '<span class="avatar-title bg-primary-subtle rounded fs-4" style="width:38px;height:38px;">'
            + '<i class="ri-user-line text-primary"></i></span>'
            + '<div><div class="fw-semibold">' + (participant.name || 'Peserta tidak teridentifikasi') + '</div>'
            + '<div class="text-muted stat-label">'
            + (participant.nomor_peserta ? 'No. ' + participant.nomor_peserta : '')
            + (data.sheet_code ? ' · Lembar ' + data.sheet_code : '')
            + '</div></div></div>';

        if (json.duplicate) {
            html += '<div class="alert alert-info py-2 mb-2" style="font-size:0.8125rem;">Gambar ini sudah pernah diproses — tidak diulang.</div>';
        }

        html += '<div class="d-flex flex-wrap gap-1 mb-2">'
            + '<span class="badge bg-success-subtle text-success">Terbaca: ' + (counts.terbaca || 0) + '</span>'
            + '<span class="badge bg-secondary-subtle text-secondary">Kosong: ' + (counts.kosong || 0) + '</span>'
            + '<span class="badge bg-danger-subtle text-danger">Ganda: ' + (counts.ganda || 0) + '</span>'
            + '<span class="badge bg-warning-subtle text-warning">Meragukan: ' + (counts.meragukan || 0) + '</span>'
            + (counts.manual ? '<span class="badge bg-info-subtle text-info">Manual: ' + counts.manual + '</span>' : '')
            + '</div>';

        if (data.summary.needs_review > 0) {
            html += '<div class="alert alert-warning py-2 mb-2" style="font-size:0.8125rem;">'
                + data.summary.needs_review + ' nomor perlu diperiksa guru sebelum nilai disahkan.</div>';
        }

        const autoSync = data.auto_sync || null;
        if (autoSync && autoSync.written) {
            html += '<div class="alert alert-success py-2 mb-2" style="font-size:0.8125rem;">'
                + '<i class="ri-check-double-line me-1"></i>Nilai <strong>langsung tersimpan</strong> ke kolom nilai resmi'
                + (autoSync.mode ? ' (' + autoSync.mode + ')' : '') + '.</div>';
        } else if (autoSync && autoSync.reason === 'pending_review') {
            html += '<div class="alert alert-warning py-2 mb-2" style="font-size:0.8125rem;">'
                + '<i class="ri-time-line me-1"></i>Nilai akan masuk otomatis setelah nomor ⚠ diperiksa.</div>';
        } else if (autoSync && autoSync.reason === 'book_locked') {
            html += '<div class="alert alert-danger py-2 mb-2" style="font-size:0.8125rem;">'
                + '<i class="ri-lock-line me-1"></i>Buku nilai terkunci — nilai belum dapat disimpan.</div>';
        } else if (autoSync && autoSync.reason === 'paket_changed') {
            html += '<div class="alert alert-danger py-2 mb-2" style="font-size:0.8125rem;">'
                + '<i class="ri-alert-line me-1"></i>Naskah paket berubah — sinkronisasi otomatis ditahan.</div>';
        }

        if (data.participant && data.participant.nilai_akhir !== null) {
            html += '<div class="mb-2">Nilai sementara: <strong>' + Number(data.participant.nilai_akhir).toFixed(2) + '</strong></div>';
        }

        html += '<div class="ljk-answer-grid">';
        (data.answers || []).forEach(function (answer) {
            const cls = badgeFor(answer.status);
            const label = answer.selected ? ' ' + answer.selected : '';
            const mark = answer.needs_review ? ' ⚠' : '';
            html += '<span class="badge bg-' + cls + '-subtle text-' + cls + '" title="' + answer.status_label + '">'
                + answer.nomor + ':' + label + mark + '</span>';
        });
        html += '</div>';

        resultPanel.innerHTML = html;

        if (participant.id) {
            linkPeriksa.href = reviewUrlBase.replace('__ID__', participant.id);
            linkPeriksa.classList.remove('d-none');
        }
    }

    btnStart.addEventListener('click', startCamera);
    btnStop.addEventListener('click', stopCamera);
    btnCapture.addEventListener('click', captureFromVideo);
    window.addEventListener('beforeunload', stopCamera);

    // ── QR scanner (html5-qrcode, mengikuti pola ALIM) ───────────────────
    const qrModal = document.getElementById('qr-modal');
    let qrScanner = null;

    qrModal.addEventListener('shown.bs.modal', async function () {
        if (typeof Html5Qrcode === 'undefined') {
            qrResult.textContent = 'Pustaka pemindai QR tidak tersedia (offline). Pilih peserta manual.';
            qrResult.classList.remove('d-none');
            return;
        }
        qrScanner = new Html5Qrcode('qr-reader');
        try {
            await qrScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                function (decodedText) {
                    qrPayload = decodedText;
                    qrResult.textContent = 'QR terbaca: ' + decodedText.substring(0, 60) + '…';
                    qrResult.classList.remove('d-none');
                    qrScanner.stop().catch(() => {});
                    bootstrap.Modal.getInstance(qrModal).hide();
                },
                function () {}
            );
        } catch (e) {
            qrResult.textContent = 'Kamera QR tidak tersedia: ' + e;
            qrResult.classList.remove('d-none');
        }
    });

    qrModal.addEventListener('hidden.bs.modal', function () {
        if (qrScanner) {
            qrScanner.stop().catch(() => {});
            qrScanner = null;
        }
    });

    participantSelect.addEventListener('change', function () {
        if (participantSelect.value) {
            qrPayload = null;
            qrResult.classList.add('d-none');
        }
    });
})();
</script>
@endsection
