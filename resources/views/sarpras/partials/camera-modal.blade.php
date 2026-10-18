<div class="modal fade" id="alimCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-camera-line me-1"></i>Ambil Foto Bukti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-2"><i class="ri-map-pin-line me-1"></i><span id="alimCameraLocation">Meminta titik koordinat…</span> · <span id="alimCameraClock">WITA</span></div>
                <video id="alimCameraVideo" autoplay playsinline muted style="width:100%;max-height:380px;object-fit:cover;background:#000;border-radius:.5rem"></video>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Foto akan diberi watermark koordinat, waktu WITA, dan logo ALIM.</small>
                    <div class="d-flex gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-success" id="alimCameraCapture"><i class="ri-camera-line me-1"></i>Ambil Foto</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.AlimCamera) return;

    const modalEl = document.getElementById('alimCameraModal');
    if (!modalEl) return;
    const video = document.getElementById('alimCameraVideo');
    const locationEl = document.getElementById('alimCameraLocation');
    const clockEl = document.getElementById('alimCameraClock');
    const captureBtn = document.getElementById('alimCameraCapture');
    let stream = null, targetInput = null, targetPreview = null, coords = null, clockTimer = null;
    const logo = new Image();
    logo.src = '{{ asset('build/images/alim-light.png') }}';

    function updateClock() {
        clockEl.textContent = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', dateStyle: 'medium', timeStyle: 'medium' }).format(new Date()) + ' WITA';
    }

    function requestLocation() {
        coords = null;
        locationEl.textContent = 'Meminta titik koordinat…';
        if (!navigator.geolocation) { locationEl.textContent = 'GPS tidak tersedia'; return; }
        navigator.geolocation.getCurrentPosition(
            function (position) {
                coords = { lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy };
                locationEl.textContent = coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6) + ' (±' + Math.round(coords.accuracy) + ' m)';
            },
            function () { locationEl.textContent = 'Koordinat tidak diizinkan/tidak tersedia'; },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false });
            video.srcObject = stream;
            await video.play();
            requestLocation();
            updateClock();
            clockTimer = setInterval(updateClock, 1000);
        } catch (error) {
            alert('Kamera tidak dapat dibuka: ' + error.message + '. Pastikan izin kamera aktif dan koneksi HTTPS.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }

    function stopCamera() {
        if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
        if (clockTimer) { clearInterval(clockTimer); clockTimer = null; }
    }

    function drawWatermark(canvas) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width, h = canvas.height;
        const barH = Math.max(92, Math.round(h * 0.16));
        ctx.fillStyle = 'rgba(0,0,0,0.58)';
        ctx.fillRect(0, h - barH, w, barH);

        if (logo.complete && logo.naturalWidth > 0) {
            ctx.drawImage(logo, 18, h - barH + (barH - 46) / 2, 46, 46);
        } else {
            ctx.fillStyle = '#fff'; ctx.font = 'bold 18px Arial'; ctx.fillText('ALIM', 18, h - barH + 32);
        }

        const textX = 78;
        ctx.fillStyle = '#fff';
        ctx.font = 'bold ' + Math.max(14, Math.round(w * 0.018)) + 'px Arial';
        ctx.fillText('ALIM · Sarana Prasarana', textX, h - barH + 30);
        ctx.font = Math.max(12, Math.round(w * 0.014)) + 'px Arial';
        ctx.fillText('Koordinat: ' + (coords ? coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6) : 'tidak tersedia'), textX, h - barH + 54);
        ctx.fillText('Waktu: ' + new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', dateStyle: 'medium', timeStyle: 'medium' }).format(new Date()) + ' WITA', textX, h - barH + 76);
        ctx.textAlign = 'right';
        ctx.font = '11px Arial';
        ctx.fillText('Dokumentasi otomatis ALIM', w - 16, h - 12);
        ctx.textAlign = 'left';
    }

    function capture() {
        if (!video.videoWidth) return;
        const canvas = document.createElement('canvas');
        const maxWidth = 1280;
        const scale = Math.min(1, maxWidth / video.videoWidth);
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        drawWatermark(canvas);

        canvas.toBlob(function (blob) {
            if (!blob || !targetInput) return;
            const file = new File([blob], 'foto-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            const dt = new DataTransfer();
            dt.items.add(file);
            const input = document.getElementById(targetInput);
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));

            if (targetPreview) {
                const preview = document.getElementById(targetPreview);
                if (preview) {
                    preview.innerHTML = '<img src="' + URL.createObjectURL(blob) + '" class="img-fluid rounded border mt-1" style="max-height:110px" alt="Foto bukti"><div class="small text-success mt-1"><i class="ri-check-line"></i> Foto kamera siap diunggah</div>';
                }
            }
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }, 'image/jpeg', 0.86);
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        targetInput = button?.dataset.target;
        targetPreview = button?.dataset.preview;
        startCamera();
    });
    modalEl.addEventListener('hidden.bs.modal', stopCamera);
    captureBtn.addEventListener('click', capture);

    window.AlimCamera = { open: function (target, preview) { targetInput = target; targetPreview = preview; bootstrap.Modal.getOrCreateInstance(modalEl).show(); } };
})();
</script>
