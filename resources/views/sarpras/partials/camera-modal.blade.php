<div class="modal fade" id="alimCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-camera-line me-1"></i>Ambil Foto Bukti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-2"><i class="ri-map-pin-line me-1"></i><span id="alimCameraLocation">Meminta titik koordinat…</span> · <span id="alimCameraClock">WITA</span></div>
                <video id="alimCameraVideo" autoplay playsinline muted style="width:100%;max-height:380px;object-fit:cover;background:#000;border-radius:.5rem"></video>
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
        const unit = Math.max(0.55, w / 1280);
        const now = new Date();
        const timeZone = 'Asia/Makassar';
        const day = new Intl.DateTimeFormat('id-ID', { timeZone, day: '2-digit' }).format(now);
        const month = new Intl.DateTimeFormat('id-ID', { timeZone, month: 'long' }).format(now).toUpperCase();
        const year = new Intl.DateTimeFormat('id-ID', { timeZone, year: 'numeric' }).format(now);
        const time = new Intl.DateTimeFormat('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', hour12: false }).format(now).replace(':', '.');
        const pageTitle = (document.title || 'Dokumentasi Sarpras').split('|')[0].trim();
        const x = Math.round(w * 0.045);
        const panelW = Math.min(w * 0.64, 720 * unit);
        const panelH = 272 * unit;
        const panelY = h - panelH - 24 * unit;

        // Gradasi bawah.
        const grad = ctx.createLinearGradient(0, h * 0.52, 0, h);
        grad.addColorStop(0, 'rgba(0,0,0,0)');
        grad.addColorStop(0.5, 'rgba(0,0,0,0.22)');
        grad.addColorStop(1, 'rgba(0,0,0,0.86)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, h * 0.52, w, h * 0.48);

        // Panel kaca gelap.
        const r = 18 * unit;
        const px = x - 16 * unit;
        ctx.beginPath();
        ctx.moveTo(px + r, panelY);
        ctx.lineTo(px + panelW - r, panelY);
        ctx.quadraticCurveTo(px + panelW, panelY, px + panelW, panelY + r);
        ctx.lineTo(px + panelW, panelY + panelH - r);
        ctx.quadraticCurveTo(px + panelW, panelY + panelH, px + panelW - r, panelY + panelH);
        ctx.lineTo(px + r, panelY + panelH);
        ctx.quadraticCurveTo(px, panelY + panelH, px, panelY + panelH - r);
        ctx.lineTo(px, panelY + r);
        ctx.quadraticCurveTo(px, panelY, px + r, panelY);
        ctx.closePath();
        ctx.fillStyle = 'rgba(4, 18, 24, 0.58)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.18)';
        ctx.lineWidth = Math.max(1, 1.2 * unit);
        ctx.stroke();

        // Aksen kuning.
        ctx.fillStyle = '#FFD400';
        ctx.fillRect(px + 20 * unit, panelY, 72 * unit, 4 * unit);

        // Logo ALIM.
        if (logo.complete && logo.naturalWidth > 0) {
            const logoSize = 42 * unit;
            ctx.drawImage(logo, x, panelY + 14 * unit, logoSize, logoSize);
        }

        ctx.textBaseline = 'alphabetic';
        ctx.shadowColor = 'rgba(0,0,0,0.55)';
        ctx.shadowBlur = 7 * unit;
        const dateY = panelY + 84 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.font = `900 ${Math.round(46 * unit)}px Arial, sans-serif`;
        ctx.fillText(day, x + 52 * unit, dateY);
        const dayWidth = ctx.measureText(day).width;
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(19 * unit)}px Arial, sans-serif`;
        ctx.fillText(month + ' ' + year, x + 52 * unit + dayWidth + 12 * unit, dateY - 2 * unit);

        // Waktu WITA di kanan panel.
        ctx.textAlign = 'right';
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(14 * unit)}px Arial, sans-serif`;
        ctx.fillText('WITA ' + time, px + panelW - 18 * unit, panelY + 30 * unit);
        ctx.textAlign = 'left';

        // Judul.
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(36 * unit)}px Arial, sans-serif`;
        ctx.fillText('Dokumentasi', x, panelY + 136 * unit);
        const titleWidth = ctx.measureText('Dokumentasi').width;
        ctx.fillStyle = '#FFD400';
        ctx.fillText('.', x + titleWidth + 2 * unit, panelY + 136 * unit);

        ctx.fillStyle = 'rgba(255,255,255,0.84)';
        ctx.font = `500 ${Math.round(15 * unit)}px Arial, sans-serif`;
        ctx.fillText(pageTitle.slice(0, 58), x, panelY + 161 * unit);

        // Garis pemisah.
        ctx.strokeStyle = 'rgba(255,255,255,0.18)';
        ctx.lineWidth = Math.max(1, unit);
        ctx.beginPath();
        ctx.moveTo(x, panelY + 176 * unit);
        ctx.lineTo(px + panelW - 18 * unit, panelY + 176 * unit);
        ctx.stroke();

        // Pin lokasi.
        const pinX = x + 5 * unit;
        const pinY = panelY + 195 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.beginPath();
        ctx.arc(pinX, pinY, 7 * unit, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(pinX - 5 * unit, pinY + 5 * unit);
        ctx.lineTo(pinX + 5 * unit, pinY + 5 * unit);
        ctx.lineTo(pinX, pinY + 15 * unit);
        ctx.closePath();
        ctx.fill();

        // Nama lokasi.
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(15 * unit)}px Arial, sans-serif`;
        const placeText = placeName || 'Nama lokasi tidak tersedia';
        ctx.fillText(fitText(ctx, placeText, panelW - 48 * unit), x + 20 * unit, panelY + 201 * unit);

        // Koordinat.
        ctx.fillStyle = 'rgba(255,255,255,0.72)';
        ctx.font = `500 ${Math.round(12 * unit)}px Arial, sans-serif`;
        const coordinateText = coords
            ? `${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} · ±${Math.round(coords.accuracy)} m`
            : 'Koordinat tidak tersedia';
        ctx.fillText(coordinateText, x + 20 * unit, panelY + 224 * unit);

        // Kredit kanan bawah.
        ctx.textAlign = 'right';
        ctx.fillStyle = 'rgba(255,255,255,0.95)';
        ctx.font = `700 ${Math.round(13 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, 'Oleh: ' + accountName, w * 0.34), w - 24 * unit, h - 62 * unit);
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(12 * unit)}px Arial, sans-serif`;
        ctx.fillText('© Ponpes Abu Hurairah', w - 24 * unit, h - 43 * unit);
        ctx.fillStyle = 'rgba(255,255,255,0.62)';
        ctx.font = `500 ${Math.round(10 * unit)}px Arial, sans-serif`;
        ctx.fillText('ALIM · Sistem Sarpras', w - 24 * unit, h - 27 * unit);
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
