@php
    $cameraUser = auth()->user();
    $cameraUnitName = $cameraUser?->primaryWorkUnit?->workUnit?->name
        ?? $cameraUser?->gtkWorkUnits?->first()?->workUnit?->name
        ?? 'Ponpes Abu Hurairah';
@endphp
<style>
    #alimCameraModal .modal-content { height: 100vh; background: #000; border: 0; }
    .alim-camera-stage { position: relative; width: 100%; height: 100vh; overflow: hidden; background: #000; }
    #alimCameraPreview { position: relative; width: 100%; height: 100%; margin: 0 auto; overflow: hidden; background: #000; transition: width .2s ease; }
    #alimCameraPreview[data-orientation="portrait"] { width: min(100vw, calc(100vh * 9 / 16)); height: 100vh; aspect-ratio: 9 / 16; }
    #alimCameraPreview video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .alim-live-overlay { position: absolute; left: 16px; right: 16px; bottom: 110px; z-index: 3; padding: 16px; border: 1px solid rgba(255,255,255,.28); border-radius: 16px; background: rgba(5,20,27,.34); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); color: #fff; box-shadow: 0 12px 35px rgba(0,0,0,.25); }
    .alim-live-overlay .live-brand { display:flex; justify-content:space-between; align-items:center; gap:10px; font-size:11px; letter-spacing:.02em; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,.18); }
    .alim-live-overlay .live-unit-name { font-size:10px; font-weight:700; max-width:58%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .alim-location-override input { min-width:190px; max-width:280px; background:rgba(0,0,0,.55); border-color:rgba(255,255,255,.35); color:#fff; font-size:11px; }
    .alim-location-override input::placeholder { color:rgba(255,255,255,.65); }
    .alim-live-overlay .live-date { display:flex; align-items:baseline; gap:8px; margin-top:10px; }
    .alim-live-overlay .live-day { font-size:30px; font-weight:900; color:#FFD400; line-height:1; }
    .alim-live-overlay .live-month { font-size:12px; font-weight:700; }
    .alim-live-overlay .live-title { font-size:22px; font-weight:800; line-height:1.1; margin-top:4px; }
    .alim-live-overlay .live-title span { color:#FFD400; }
    .alim-live-overlay .live-subtitle { font-size:10px; opacity:.82; }
    .alim-live-overlay .live-location { display:flex; gap:7px; align-items:flex-start; margin-top:10px; padding-top:9px; border-top:1px solid rgba(255,255,255,.16); }
    .alim-live-overlay .live-location i { color:#FFD400; font-size:15px; }
    .alim-live-overlay .live-place { font-size:12px; font-weight:700; line-height:1.2; }
    .alim-live-overlay .live-coords { font-size:9px; opacity:.72; margin-top:2px; }
    .alim-live-overlay .live-footer { display:flex; justify-content:space-between; gap:8px; margin-top:9px; padding-top:8px; border-top:1px solid rgba(255,255,255,.16); font-size:9px; }
    .alim-live-overlay .live-footer .copyright { color:#FFD400; font-weight:700; text-align:right; }
    .alim-camera-topbar { position:absolute; inset:0 0 auto 0; z-index:4; display:flex; justify-content:space-between; align-items:flex-start; padding:14px; background:linear-gradient(to bottom, rgba(0,0,0,.65), transparent); }
    .alim-camera-close { width:40px; height:40px; border-radius:50%; background:rgba(0,0,0,.55); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .alim-camera-orientation { background:rgba(0,0,0,.48); border:1px solid rgba(255,255,255,.25); border-radius:10px; padding:4px; }
    .alim-camera-orientation .btn { border:0; color:#fff; font-size:11px; }
    .alim-camera-orientation .btn-check:checked + .btn { background:#FFD400; color:#111; }
    .alim-camera-bottombar { position:absolute; left:0; right:0; bottom:0; z-index:5; display:flex; align-items:center; justify-content:center; padding:14px 18px 24px; background:linear-gradient(to top, rgba(0,0,0,.8), transparent); }
    .alim-shutter { width:72px; height:72px; border-radius:50%; border:5px solid rgba(255,255,255,.9); background:#fff; box-shadow:0 0 0 5px rgba(0,0,0,.25); transition:transform .15s; }
    .alim-shutter:active { transform:scale(.94); }
    .alim-camera-hint { position:absolute; bottom:104px; left:0; right:0; text-align:center; color:#fff; font-size:11px; opacity:.85; z-index:5; }
    #alimCameraError { position:absolute; left:16px; right:16px; bottom:158px; z-index:6; }
    @media (max-width: 576px) { .alim-live-overlay { left:10px; right:10px; bottom:100px; padding:12px; } .alim-live-overlay .live-day { font-size:25px; } .alim-live-overlay .live-title { font-size:19px; } .alim-live-overlay .live-footer { font-size:8px; } }
</style>
<div class="modal fade" id="alimCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="alim-camera-stage">
                <div id="alimCameraPreview" class="alim-camera-preview" data-orientation="auto">
                    <video id="alimCameraVideo" autoplay playsinline muted></video>
                    <div class="alim-live-overlay">
                        <div class="live-brand"><span class="d-flex align-items-center gap-2 min-w-0"><img src="{{ asset('build/images/alim-light.png') }}" alt="ALIM" style="height:24px;width:auto"><span class="live-unit-name" title="{{ $cameraUnitName }}">{{ $cameraUnitName }}</span></span><span id="alimLiveTime">WITA --.--</span></div>
                        <div class="live-date"><span class="live-day" id="alimLiveDay">--</span><span class="live-month" id="alimLiveMonthYear">--- ----</span></div>
                        <div class="live-title">Dokumentasi<span>.</span></div>
                        <div class="live-subtitle" id="alimLivePageTitle">Kamera Dokumentasi</div>
                        <div class="live-location"><i class="ri-map-pin-2-fill"></i><div class="min-w-0"><div class="live-place" id="alimLivePlace">Meminta lokasi…</div><div class="live-coords" id="alimLiveCoords">Koordinat belum tersedia</div></div></div>
                        <div class="live-footer"><span>Oleh: <strong>{{ auth()->user()->name ?? 'Pengguna ALIM' }}</strong></span><span class="copyright">© Ponpes Abu Hurairah</span></div>
                    </div>
                </div>
                <div class="alim-camera-topbar">
                    <button type="button" class="btn alim-camera-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="ri-close-line fs-5"></i></button>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="btn-group btn-group-sm alim-camera-orientation" role="group" aria-label="Orientasi foto">
                            <input type="radio" class="btn-check" name="alimCameraOrientation" id="alimOrientationAuto" value="auto" checked>
                            <label class="btn btn-sm" for="alimOrientationAuto">Otomatis</label>
                            <input type="radio" class="btn-check" name="alimCameraOrientation" id="alimOrientationPortrait" value="portrait">
                            <label class="btn btn-sm" for="alimOrientationPortrait">9:16</label>
                        </div>
                        <div class="alim-location-override"><input id="alimLocationOverride" class="form-control form-control-sm" placeholder="Koreksi nama lokasi…" aria-label="Koreksi nama lokasi"></div>
                    </div>
                </div>
                <div id="alimCameraError" class="alert alert-danger small d-none"></div>
                <div class="alim-camera-hint">Arahkan kamera ke objek · watermark tampil langsung di layar</div>
                <div class="alim-camera-bottombar"><button type="button" class="alim-shutter" id="alimCameraCapture" aria-label="Ambil foto"></button></div>
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
    const livePlaceEl = document.getElementById('alimLivePlace');
    const liveCoordsEl = document.getElementById('alimLiveCoords');
    const liveTimeEl = document.getElementById('alimLiveTime');
    const liveDayEl = document.getElementById('alimLiveDay');
    const liveMonthYearEl = document.getElementById('alimLiveMonthYear');
    const livePageTitleEl = document.getElementById('alimLivePageTitle');
    const captureBtn = document.getElementById('alimCameraCapture');
    const errorEl = document.getElementById('alimCameraError');
    let stream = null, targetInput = null, targetPreview = null, coords = null, placeName = null, manualPlace = '', placePromise = null, clockTimer = null;
    const accountName = @json(auth()->user()->name ?? 'Pengguna ALIM');
    const unitName = @json($cameraUnitName);
    const locationOverrideEl = document.getElementById('alimLocationOverride');
    const geocodeEndpoint = @json(route('sarpras.camera.geocode'));
    const logo = new Image();
    logo.src = '{{ asset('build/images/alim-light.png') }}';

    if (locationOverrideEl) {
        locationOverrideEl.addEventListener('input', function () {
            manualPlace = this.value.trim();
            renderLocation();
        });
    }

    function updateClock() {
        const now = new Date();
        const timeZone = 'Asia/Makassar';
        liveTimeEl.textContent = 'WITA ' + new Intl.DateTimeFormat('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', hour12: false }).format(now).replace(':', '.');
        liveDayEl.textContent = new Intl.DateTimeFormat('id-ID', { timeZone, day: '2-digit' }).format(now);
        liveMonthYearEl.textContent = new Intl.DateTimeFormat('id-ID', { timeZone, month: 'long', year: 'numeric' }).format(now).toUpperCase();
    }

    function renderLocation() {
        livePlaceEl.textContent = manualPlace || placeName || (coords ? 'Nama lokasi tidak tersedia' : 'Lokasi tidak tersedia');
        liveCoordsEl.textContent = coords
            ? `Koordinat: ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} · ±${Math.round(coords.accuracy)} m${coords.accuracy > 100 ? ' (akurasi rendah)' : ''}`
            : 'Koordinat belum tersedia';
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
        manualPlace = '';
        if (locationOverrideEl) locationOverrideEl.value = '';
        placePromise = null;
        livePlaceEl.textContent = 'Mencari lokasi…';
        liveCoordsEl.textContent = 'Meminta titik koordinat…';
        if (!navigator.geolocation) { livePlaceEl.textContent = 'GPS tidak tersedia'; return; }
        navigator.geolocation.getCurrentPosition(
            function (position) {
                coords = { lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy };
                renderLocation();
                livePlaceEl.textContent = 'Mencari nama lokasi…';
                placePromise = resolvePlaceName(coords.lat, coords.lng).then(function (name) {
                    placeName = name;
                    renderLocation();
                    return name;
                });
            },
            function () { livePlaceEl.textContent = 'Lokasi tidak diizinkan'; liveCoordsEl.textContent = 'Koordinat tidak tersedia'; },
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
            livePageTitleEl.textContent = (document.title || 'Kamera Dokumentasi').split('|')[0].trim();
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
        previewEl.dataset.orientation = selectedOrientation();
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
        const unit = Math.max(0.78, Math.min(w / 1200, h / 900));
        const now = new Date();
        const timeZone = 'Asia/Makassar';
        const day = new Intl.DateTimeFormat('id-ID', { timeZone, day: '2-digit' }).format(now);
        const month = new Intl.DateTimeFormat('id-ID', { timeZone, month: 'long' }).format(now).toUpperCase();
        const year = new Intl.DateTimeFormat('id-ID', { timeZone, year: 'numeric' }).format(now);
        const time = new Intl.DateTimeFormat('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', hour12: false }).format(now).replace(':', '.');
        const pageTitle = (document.title || 'Dokumentasi Sarpras').split('|')[0].trim();
        const displayPlaceName = manualPlace || placeName;
        const x = Math.round(w * 0.042);
        const panelW = Math.min(w * (portrait ? 0.86 : 0.68), 800 * unit);
        const panelH = 300 * unit;
        const panelY = h - panelH - Math.max(18 * unit, h * 0.025);
        const px = x - 14 * unit;
        const right = px + panelW - 16 * unit;

        // Sedikit gradasi bawah untuk keterbacaan, tanpa menutupi foto.
        const grad = ctx.createLinearGradient(0, h * 0.58, 0, h);
        grad.addColorStop(0, 'rgba(0,0,0,0)');
        grad.addColorStop(1, 'rgba(0,0,0,0.62)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, h * 0.58, w, h * 0.42);

        // Efek kaca: salin area belakang panel, blur, lalu beri tint transparan.
        const glass = document.createElement('canvas');
        glass.width = Math.ceil(panelW);
        glass.height = Math.ceil(panelH);
        const gctx = glass.getContext('2d');
        if ('filter' in gctx) {
            gctx.filter = `blur(${Math.max(8, 13 * unit)}px)`;
        }
        gctx.drawImage(canvas, px, panelY, panelW, panelH, 0, 0, glass.width, glass.height);
        gctx.filter = 'none';

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

        ctx.save();
        ctx.clip();
        ctx.globalAlpha = 0.82;
        ctx.drawImage(glass, px, panelY, panelW, panelH);
        ctx.globalAlpha = 1;
        ctx.fillStyle = 'rgba(5, 20, 27, 0.30)';
        ctx.fillRect(px, panelY, panelW, panelH);
        // Kilau kaca tipis.
        const sheen = ctx.createLinearGradient(px, panelY, px + panelW, panelY + panelH);
        sheen.addColorStop(0, 'rgba(255,255,255,0.10)');
        sheen.addColorStop(0.5, 'rgba(255,255,255,0.025)');
        sheen.addColorStop(1, 'rgba(255,255,255,0.06)');
        ctx.fillStyle = sheen;
        ctx.fillRect(px, panelY, panelW, panelH);
        ctx.restore();

        ctx.strokeStyle = 'rgba(255,255,255,0.28)';
        ctx.lineWidth = Math.max(1, unit);
        ctx.stroke();

        // Aksen kuning.
        ctx.fillStyle = '#FFD400';
        ctx.fillRect(px + 18 * unit, panelY, 68 * unit, 3 * unit);

        // Header: logo + nama satuan kerja, waktu.
        if (logo.complete && logo.naturalWidth > 0) {
            const logoSize = 31 * unit;
            ctx.drawImage(logo, x, panelY + 15 * unit, logoSize, logoSize);
        }
        ctx.textBaseline = 'alphabetic';
        ctx.shadowColor = 'rgba(0,0,0,0.55)';
        ctx.shadowBlur = 7 * unit;
        ctx.textAlign = 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(12 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, unitName, panelW * 0.48), x + 36 * unit, panelY + 33 * unit);

        ctx.textAlign = 'right';
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(12 * unit)}px Arial, sans-serif`;
        ctx.fillText('WITA ' + time, right, panelY + 32 * unit);
        ctx.textAlign = 'left';

        ctx.strokeStyle = 'rgba(255,255,255,0.18)';
        ctx.lineWidth = Math.max(1, unit * 0.8);
        ctx.beginPath();
        ctx.moveTo(x, panelY + 54 * unit);
        ctx.lineTo(right, panelY + 54 * unit);
        ctx.stroke();

        // Tanggal.
        const dateY = panelY + 96 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.font = `900 ${Math.round(46 * unit)}px Arial, sans-serif`;
        ctx.fillText(day, x, dateY);
        const dayWidth = ctx.measureText(day).width;
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(17 * unit)}px Arial, sans-serif`;
        ctx.fillText(month + ' ' + year, x + dayWidth + 11 * unit, dateY - 2 * unit);

        // Judul dan konteks halaman.
        const titleY = panelY + 143 * unit;
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(32 * unit)}px Arial, sans-serif`;
        ctx.fillText('Dokumentasi', x, titleY);
        const titleWidth = ctx.measureText('Dokumentasi').width;
        ctx.fillStyle = '#FFD400';
        ctx.fillText('.', x + titleWidth + 2 * unit, titleY);

        ctx.fillStyle = 'rgba(255,255,255,0.84)';
        ctx.font = `500 ${Math.round(13 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, pageTitle, panelW - 36 * unit), x, panelY + 166 * unit);

        ctx.strokeStyle = 'rgba(255,255,255,0.16)';
        ctx.beginPath();
        ctx.moveTo(x, panelY + 181 * unit);
        ctx.lineTo(right, panelY + 181 * unit);
        ctx.stroke();

        // Lokasi.
        const pinX = x + 5 * unit;
        const pinY = panelY + 202 * unit;
        ctx.fillStyle = '#FFD400';
        ctx.beginPath();
        ctx.arc(pinX, pinY, 6.5 * unit, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(pinX - 4.5 * unit, pinY + 4.5 * unit);
        ctx.lineTo(pinX + 4.5 * unit, pinY + 4.5 * unit);
        ctx.lineTo(pinX, pinY + 14 * unit);
        ctx.closePath();
        ctx.fill();

        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(14 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, displayPlaceName || 'Nama lokasi tidak tersedia', panelW - 44 * unit), x + 19 * unit, panelY + 208 * unit);

        ctx.fillStyle = 'rgba(255,255,255,0.74)';
        ctx.font = `500 ${Math.round(10.5 * unit)}px Arial, sans-serif`;
        const coordinateText = coords
            ? `Koordinat: ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} · ±${Math.round(coords.accuracy)} m`
            : 'Koordinat tidak tersedia';
        ctx.fillText(fitText(ctx, coordinateText, panelW - 44 * unit), x + 19 * unit, panelY + 228 * unit);

        // Footer.
        ctx.strokeStyle = 'rgba(255,255,255,0.16)';
        ctx.beginPath();
        ctx.moveTo(x, panelY + 247 * unit);
        ctx.lineTo(right, panelY + 247 * unit);
        ctx.stroke();

        ctx.fillStyle = 'rgba(255,255,255,0.92)';
        ctx.font = `600 ${Math.round(10.5 * unit)}px Arial, sans-serif`;
        ctx.fillText(fitText(ctx, 'Oleh: ' + accountName, panelW * 0.48), x, panelY + 270 * unit);

        ctx.textAlign = 'right';
        ctx.fillStyle = '#FFD400';
        ctx.font = `700 ${Math.round(10 * unit)}px Arial, sans-serif`;
        ctx.fillText('© Ponpes Abu Hurairah', right, panelY + 270 * unit);
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
            // Crop tengah 9:16 agar hasil vertikal konsisten di kamera depan/belakang.
            const targetRatio = 9 / 16;
            if (sw / sh > targetRatio) {
                sw = sh * targetRatio;
                sx = (video.videoWidth - sw) / 2;
            } else {
                sh = sw / targetRatio;
                sy = (video.videoHeight - sh) / 2;
            }
            canvas.width = 720;
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
