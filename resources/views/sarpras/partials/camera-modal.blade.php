<style>
    .alim-camera-preview { position: relative; width: 100%; aspect-ratio: 16 / 9; max-height: 430px; overflow: hidden; border-radius: .5rem; background: #000; }
    .alim-camera-preview video { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
<div class="modal fade" id="alimCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-camera-line me-1"></i>Ambil Foto Bukti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-2"><i class="ri-map-pin-line me-1"></i><span id="alimCameraLocation">Meminta titik koordinat…</span> · <span id="alimCameraClock">WITA</span></div>
                <div id="alimCameraPreview" class="alim-camera-preview"><video id="alimCameraVideo" autoplay playsinline muted></video></div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Orientasi</span>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Orientasi foto">
                            <input type="radio" class="btn-check" name="alimCameraOrientation" id="alimOrientationAuto" value="auto" checked>
                            <label class="btn btn-outline-secondary" for="alimOrientationAuto">Otomatis</label>
                            <input type="radio" class="btn-check" name="alimCameraOrientation" id="alimOrientationPortrait" value="portrait">
                            <label class="btn btn-outline-secondary" for="alimOrientationPortrait"><i class="ri-portrait-line me-1"></i>Vertikal 3:4</label>
                        </div>
                    </div>
                    <small class="text-muted">Vertikal cocok untuk foto berdiri / dokumentasi bangunan.</small>
                </div>
                <div id="alimCameraError" class="alert alert-danger small mt-2 d-none"></div>
                <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted mt-2">
                    <span><i class="ri-user-line me-1"></i>Diambil oleh: <strong>{{ auth()->user()->name ?? 'Pengguna ALIM' }}</strong></span>
                    <span><i class="ri-copyright-line me-1"></i>Ponpes Abu Hurairah</span>
                </div>
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
    const previewEl = document.getElementById('alimCameraPreview');
    const locationEl = document.getElementById('alimCameraLocation');
    const clockEl = document.getElementById('alimCameraClock');
    const captureBtn = document.getElementById('alimCameraCapture');
    const errorEl = document.getElementById('alimCameraError');
    let stream = null, targetInput = null, targetPreview = null, coords = null, placeName = null, placePromise = null, clockTimer = null;
    const accountName = @json(auth()->user()->name ?? 'Pengguna ALIM');
    const geocodeEndpoint = @json(route('sarpras.camera.geocode'));
    const logo = new Image();
    logo.src = '{{ asset('build/images/alim-light.png') }}';

    function updateClock() {
        clockEl.textContent = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', dateStyle: 'medium', timeStyle: 'medium' }).format(new Date()) + ' WITA';
    }

    function locationLabel() {
        if (!coords) return 'Koordinat tidak tersedia';
        var text = coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6) + ' (±' + Math.round(coords.accuracy) + ' m)';
        return placeName ? placeName + ' · ' + text : text;
    }

    async function resolvePlaceName(lat, lng) {
        // Proxy backend: menghindari CORS Nominatim.
        var controller = new AbortController();
        var timer = setTimeout(function () { controller.abort(); }, 5000);
        try {
            var response = await fetch(geocodeEndpoint + '?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            });
            if (response.ok) {
                var payload = await response.json();
                if (payload.place) return payload.place;
            }
        } catch (error) {
            // lanjut ke fallback
        } finally {
            clearTimeout(timer);
        }

        // Fallback BigDataCloud (CORS aktif, tanpa API key).
        try {
            var fallback = await fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lng) + '&localityLanguage=id');
            if (fallback.ok) {
                var place = await fallback.json();
                var names = [place.locality || place.city, place.principalSubdivision, place.countryName].filter(Boolean);
                if (names.length) return names.join(', ');
            }
        } catch (error) {
            // abaikan, watermark tetap memakai koordinat
        }

        return null;
    }

    function requestLocation() {
        coords = null;
        placeName = null;
        placePromise = null;
        locationEl.textContent = 'Meminta titik koordinat…';
        if (!navigator.geolocation) { locationEl.textContent = 'GPS tidak tersedia'; return; }
        navigator.geolocation.getCurrentPosition(
            function (position) {
                coords = { lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy };
                locationEl.textContent = coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6) + ' · mencari nama lokasi…';
                placePromise = resolvePlaceName(coords.lat, coords.lng).then(function (name) {
                    placeName = name;
                    locationEl.textContent = locationLabel();
                    return name;
                });
            },
            function () { locationEl.textContent = 'Koordinat tidak diizinkan/tidak tersedia'; },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    async function startCamera() {
        if (errorEl) { errorEl.classList.add('d-none'); errorEl.textContent = ''; }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (errorEl) { errorEl.textContent = 'Browser tidak mendukung kamera. Gunakan HTTPS atau buka di Chrome/Safari terbaru.'; errorEl.classList.remove('d-none'); }
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false });
            video.srcObject = stream;
            await video.play();
            updatePreviewRatio();
            requestLocation();
            updateClock();
            clockTimer = setInterval(updateClock, 1000);
        } catch (error) {
            if (errorEl) { errorEl.textContent = 'Kamera tidak dapat dibuka: ' + error.message + '. Pastikan izin kamera aktif dan koneksi HTTPS.'; errorEl.classList.remove('d-none'); }
        }
    }

    function stopCamera() {
        if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
        if (clockTimer) { clearInterval(clockTimer); clockTimer = null; }
    }

    function selectedOrientation() {
        return document.querySelector('input[name="alimCameraOrientation"]:checked')?.value || 'auto';
    }

    function updatePreviewRatio() {
        if (!previewEl) return;
        if (selectedOrientation() === 'portrait') {
            previewEl.style.aspectRatio = '3 / 4';
        } else if (video.videoWidth && video.videoHeight) {
            previewEl.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
        } else {
            previewEl.style.aspectRatio = '16 / 9';
        }
    }

    function fitText(ctx, text, maxWidth) {
        if (ctx.measureText(text).width <= maxWidth) return text;
        var shortened = text;
        while (shortened.length > 4 && ctx.measureText(shortened + '…').width > maxWidth) {
            shortened = shortened.slice(0, -1);
        }
        return shortened + '…';
    }

    function drawWatermark(canvas) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width, h = canvas.height;
        const portrait = h > w;
        const unit = Math.max(0.62, Math.min(w / 1280, h / 900));
        const now = new Date();
        const timeZone = 'Asia/Makassar';
        const day = new Intl.DateTimeFormat('id-ID', { timeZone, day: '2-digit' }).format(now);
        const month = new Intl.DateTimeFormat('id-ID', { timeZone, month: 'long' }).format(now).toUpperCase();
        const year = new Intl.DateTimeFormat('id-ID', { timeZone, year: 'numeric' }).format(now);
        const time = new Intl.DateTimeFormat('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', hour12: false }).format(now).replace(':', '.');
        const pageTitle = (document.title || 'Dokumentasi Sarpras').split('|')[0].trim();
        const x = Math.round(w * 0.042);
        const panelW = Math.min(w * (portrait ? 0.84 : 0.66), 760 * unit);
        const panelH = 272 * unit;
        const panelY = h - panelH - Math.max(18 * unit, h * 0.025);
        const px = x - 14 * unit;
        const right = px + panelW - 16 * unit;

        // Gradasi agar area bawah tetap terbaca tanpa menutupi foto.
        const grad = ctx.createLinearGradient(0, h * 0.5, 0, h);
        grad.addColorStop(0, 'rgba(0,0,0,0)');
        grad.addColorStop(0.55, 'rgba(0,0,0,0.20)');
        grad.addColorStop(1, 'rgba(0,0,0,0.86)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, h * 0.5, w, h * 0.5);

        // Panel utama.
        const radius = 16 * unit;
        ctx.beginPath();
        ctx.moveTo(px + radius, panelY);
        ctx.lineTo(px + panelW - radius, panelY);
        ctx.quadraticCurveTo(px + panelW, panelY, px + panelW, panelY + radius);
        ctx.lineTo(px + panelW, panelY + panelH - radius);
        ctx.quadraticCurveTo(px + panelW, panelY + panelH, px + panelW - radius, panelY + panelH);
        ctx.lineTo(px + radius, panelY + panelH);
        ctx.quadraticCurveTo(px, panelY + panelH, px, panelY + panelH - radius);
        ctx.lineTo(px, panelY + radius);
        ctx.quadraticCurveTo(px, panelY, px + radius, panelY);
        ctx.closePath();
        ctx.fillStyle = 'rgba(5, 20, 27, 0.66)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.16)';
        ctx.lineWidth = Math.max(1, unit);
        ctx.stroke();

        // Aksen kuning tipis.
        ctx.fillStyle = '#FFD400';
        ctx.fillRect(px + 18 * unit, panelY, 62 * unit, 3 * unit);

        // Header: logo, brand, waktu.
        if (logo.complete && logo.naturalWidth > 0) {
            const logoSize = 28 * unit;
            ctx.drawImage(logo, x, panelY + 14 * unit, logoSize, logoSize);
        }
        ctx.textBaseline = 'alphabetic';
        ctx.shadowColor = 'rgba(0,0,0,0.5)';
        ctx.shadowBlur = 6 * unit;
        ctx.textAlign = 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(13 * unit)}px Arial, sans-serif`;
        ctx.fillText('ALIM', x + 36 * unit, panelY + 29 * unit);
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(8 * unit)}px Arial, sans-serif`;
        ctx.fillText('SARPRAS', x + 36 * unit, panelY + 41 * unit);

        ctx.textAlign = 'right';
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(11 * unit)}px Arial, sans-serif`;
        ctx.fillText('WITA ' + time, right, panelY + 30 * unit);
        ctx.textAlign = 'left';

        // Garis header.
        ctx.strokeStyle = 'rgba(255,255,255,0.16)';
        ctx.lineWidth = Math.max(1, unit * 0.8);
        ctx.beginPath();
        ctx.moveTo(x, panelY + 50 * unit);
        ctx.lineTo(right, panelY + 50 * unit);
        ctx.stroke();

        // Tanggal.
        const dateY = panelY + 88 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.font = `900 ${Math.round(42 * unit)}px Arial, sans-serif`;
        ctx.fillText(day, x, dateY);
        const dayWidth = ctx.measureText(day).width;
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(16 * unit)}px Arial, sans-serif`;
        ctx.fillText(month + ' ' + year, x + dayWidth + 10 * unit, dateY - 2 * unit);

        // Judul dan konteks halaman.
        const titleY = panelY + 132 * unit;
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(29 * unit)}px Arial, sans-serif`;
        ctx.fillText('Dokumentasi', x, titleY);
        const titleWidth = ctx.measureText('Dokumentasi').width;
        ctx.fillStyle = '#FFD400';
        ctx.fillText('.', x + titleWidth + 2 * unit, titleY);

        ctx.fillStyle = 'rgba(255,255,255,0.82)';
        ctx.font = `500 ${Math.round(12 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, pageTitle, panelW - 34 * unit), x, panelY + 153 * unit);

        // Garis konten.
        ctx.strokeStyle = 'rgba(255,255,255,0.14)';
        ctx.beginPath();
        ctx.moveTo(x, panelY + 168 * unit);
        ctx.lineTo(right, panelY + 168 * unit);
        ctx.stroke();

        // Lokasi.
        const pinX = x + 5 * unit;
        const pinY = panelY + 187 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.beginPath();
        ctx.arc(pinX, pinY, 6 * unit, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(pinX - 4 * unit, pinY + 4 * unit);
        ctx.lineTo(pinX + 4 * unit, pinY + 4 * unit);
        ctx.lineTo(pinX, pinY + 13 * unit);
        ctx.closePath();
        ctx.fill();

        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(13 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, placeName || 'Nama lokasi tidak tersedia', panelW - 42 * unit), x + 18 * unit, panelY + 193 * unit);

        ctx.fillStyle = 'rgba(255,255,255,0.68)';
        ctx.font = `500 ${Math.round(10 * unit)}px Arial, sans-serif`;
        const coordinateText = coords
            ? `Koordinat: ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} · ±${Math.round(coords.accuracy)} m`
            : 'Koordinat tidak tersedia';
        ctx.fillText(fitText(ctx, coordinateText, panelW - 42 * unit), x + 18 * unit, panelY + 212 * unit);

        // Footer: akun + copyright.
        ctx.strokeStyle = 'rgba(255,255,255,0.14)';
        ctx.beginPath();
        ctx.moveTo(x, panelY + 228 * unit);
        ctx.lineTo(right, panelY + 228 * unit);
        ctx.stroke();

        ctx.fillStyle = 'rgba(255,255,255,0.90)';
        ctx.font = `600 ${Math.round(10 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, 'Oleh: ' + accountName, panelW * 0.48), x, panelY + 250 * unit);

        ctx.textAlign = 'right';
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(9.5 * unit)}px Arial, sans-serif`;
        ctx.fillText('© Ponpes Abu Hurairah', right, panelY + 250 * unit);
        ctx.textAlign = 'left';
        ctx.shadowBlur = 0;
    }

    async function capture() {
        if (!video.videoWidth) {
            if (errorEl) { errorEl.textContent = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.'; errorEl.classList.remove('d-none'); }
            return;
        }
        if (!coords) {
            await new Promise(function (resolve) { setTimeout(resolve, 900); });
        }
        if (placePromise) {
            captureBtn.disabled = true;
            await Promise.race([
                placePromise,
                new Promise(function (resolve) { setTimeout(resolve, 2600); })
            ]);
            captureBtn.disabled = false;
        }
        const orientation = selectedOrientation();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        let sx = 0, sy = 0, sw = video.videoWidth, sh = video.videoHeight;

        if (orientation === 'portrait') {
            // Crop tengah 3:4 agar hasil vertikal konsisten di kamera depan/belakang.
            const targetRatio = 3 / 4;
            if (sw / sh > targetRatio) {
                sw = sh * targetRatio;
                sx = (video.videoWidth - sw) / 2;
            } else {
                sh = sw / targetRatio;
                sy = (video.videoHeight - sh) / 2;
            }
            canvas.width = 960;
            canvas.height = 1280;
        } else {
            const maxWidth = 1280;
            const scale = Math.min(1, maxWidth / sw);
            canvas.width = Math.round(sw * scale);
            canvas.height = Math.round(sh * scale);
        }

        ctx.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
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
    document.querySelectorAll('input[name="alimCameraOrientation"]').forEach(function (input) {
        input.addEventListener('change', updatePreviewRatio);
    });
    video.addEventListener('loadedmetadata', updatePreviewRatio);
    captureBtn.addEventListener('click', capture);

    window.AlimCamera = { open: function (target, preview) { targetInput = target; targetPreview = preview; bootstrap.Modal.getOrCreateInstance(modalEl).show(); } };
})();
</script>
