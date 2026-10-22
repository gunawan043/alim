
<div class="modal fade" id="alimCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-camera-line me-1"></i> Ambil Foto Bukti
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border small mb-2">
                    <i class="ri-map-pin-line me-1"></i>
                    <span id="alimCameraLocation">Meminta titik koordinat…</span>
                    <div class="mt-1">
                        <i class="ri-time-line me-1"></i>
                        <span id="alimCameraClock">WITA</span>
                    </div>
                </div>

                <video
                    id="alimCameraVideo"
                    autoplay
                    playsinline
                    muted
                    style="width:100%;max-height:55vh;object-fit:cover;background:#000;border-radius:.75rem"
                ></video>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <small class="text-muted">
                        Foto diberi watermark lokasi, koordinat, waktu WITA,
                        dan copyright Ponpes Abu Hurairah.
                    </small>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="button" class="btn btn-success" id="alimCameraCapture">
                            <i class="ri-camera-line me-1"></i> Ambil Foto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    if (window.AlimCamera) return;

    const modalEl = document.getElementById('alimCameraModal');
    if (!modalEl) return;

    const video = document.getElementById('alimCameraVideo');
    const locationEl = document.getElementById('alimCameraLocation');
    const clockEl = document.getElementById('alimCameraClock');
    const captureBtn = document.getElementById('alimCameraCapture');

    let stream = null;
    let targetInput = null;
    let targetPreview = null;
    let coords = null;
    let locationName = 'Lokasi tidak tersedia';
    let clockTimer = null;
    let locationRequestId = 0;
    let locationPromise = Promise.resolve();
    let startingCamera = false;

    const TIME_ZONE = 'Asia/Makassar';
    const COPYRIGHT = 'Copyright © Ponpes Abu Hurairah';
    const MAX_WIDTH = 1600;

    const logo = new Image();
    logo.src = '{{ asset('build/images/alim-light.png') }}';

    logo.onerror = function () {
        // Watermark tetap berjalan meskipun logo tidak tersedia.
    };

    function formatDateParts(date) {
        const parts = new Intl.DateTimeFormat('id-ID', {
            timeZone: TIME_ZONE,
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        }).formatToParts(date);

        const get = type =>
            parts.find(part => part.type === type)?.value || '';

        return {
            day: get('day'),
            month: get('month'),
            year: get('year')
        };
    }

    function updateClock() {
        clockEl.textContent =
            new Intl.DateTimeFormat('id-ID', {
                timeZone: TIME_ZONE,
                dateStyle: 'medium',
                timeStyle: 'medium'
            }).format(new Date()) + ' WITA';
    }

    function updateLocationDisplay() {
        if (!coords) return;

        const accuracy = Math.round(coords.accuracy);

        locationEl.textContent =
            `${locationName} · ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} (±${accuracy} m)`;
    }

    function requestLocation() {
        const requestId = ++locationRequestId;

        coords = null;
        locationName = 'Nama lokasi belum tersedia';
        locationEl.textContent = 'Meminta titik koordinat…';

        if (!navigator.geolocation) {
            locationName = 'GPS tidak tersedia';
            locationEl.textContent = locationName;
            return Promise.resolve();
        }

        locationPromise = new Promise(resolve => {
            navigator.geolocation.getCurrentPosition(
                async function (position) {
                    if (requestId !== locationRequestId) {
                        resolve();
                        return;
                    }

                    coords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };

                    locationName = 'Mencari nama lokasi…';
                    updateLocationDisplay();

                    try {
                        const url = new URL(
                            'https://nominatim.openstreetmap.org/reverse'
                        );

                        url.search = new URLSearchParams({
                            format: 'jsonv2',
                            lat: String(coords.lat),
                            lon: String(coords.lng),
                            zoom: '16',
                            'accept-language': 'id'
                        }).toString();

                        const response = await fetch(url.toString(), {
                            headers: {
                                Accept: 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Reverse geocoding gagal');
                        }

                        const data = await response.json();

                        if (requestId !== locationRequestId) {
                            resolve();
                            return;
                        }

                        const address = data.address || {};

                        const locality =
                            address.village ||
                            address.suburb ||
                            address.neighbourhood ||
                            address.quarter ||
                            address.town ||
                            address.city ||
                            address.municipality ||
                            address.county ||
                            '';

                        const region =
                            address.city_district ||
                            address.state_district ||
                            address.state ||
                            '';

                        locationName = [
                            locality,
                            region
                        ].filter(Boolean).filter((value, index, array) =>
                            array.indexOf(value) === index
                        ).join(', ') || data.name || 'Lokasi tidak diketahui';

                    } catch (error) {
                        locationName = 'Nama lokasi tidak tersedia';
                    }

                    updateLocationDisplay();
                    resolve();
                },
                function () {
                    if (requestId === locationRequestId) {
                        coords = null;
                        locationName = 'Lokasi tidak tersedia';
                        locationEl.textContent =
                            'Koordinat tidak diizinkan/tidak tersedia';
                    }

                    resolve();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });

        return locationPromise;
    }

    async function startCamera() {
        if (startingCamera || stream) return;

        startingCamera = true;
        captureBtn.disabled = true;

        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error(
                    'Browser tidak mendukung kamera. Gunakan HTTPS atau localhost.'
                );
            }

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1600 },
                    height: { ideal: 1200 }
                },
                audio: false
            });

            video.srcObject = stream;
            await video.play();

            updateClock();
            clockTimer = setInterval(updateClock, 1000);

            requestLocation();

            captureBtn.disabled = false;
        } catch (error) {
            stopCamera();

            alert(
                'Kamera tidak dapat dibuka: ' +
                error.message +
                '. Pastikan izin kamera aktif dan koneksi menggunakan HTTPS.'
            );

            bootstrap.Modal.getInstance(modalEl)?.hide();
        } finally {
            startingCamera = false;
        }
    }

    function stopCamera() {
        locationRequestId++;

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        video.srcObject = null;

        if (clockTimer) {
            clearInterval(clockTimer);
            clockTimer = null;
        }

        captureBtn.disabled = false;
        startingCamera = false;
    }

    function roundedRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function fitText(ctx, text, maxWidth) {
        if (ctx.measureText(text).width <= maxWidth) return text;

        let result = text;

        while (
            result.length > 1 &&
            ctx.measureText(result + '…').width > maxWidth
        ) {
            result = result.slice(0, -1);
        }

        return result + '…';
    }

    function drawWatermark(canvas) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;
        const u = Math.max(0.55, w / 1280);
        const now = new Date();

        const date = formatDateParts(now);

        const time = new Intl.DateTimeFormat('id-ID', {
            timeZone: TIME_ZONE,
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).format(now);

        const pageTitle = (document.title || 'Dokumentasi ALIM')
            .split('|')[0]
            .trim();

        const x = w * 0.055;
        const panelW = Math.min(w * 0.79, 700 * u);
        const panelH = 310 * u;
        const panelX = x - 16 * u;
        const panelY = h - panelH - 28 * u;
        const radius = 14 * u;

        // Lapisan gradasi bawah untuk menjaga keterbacaan.
        const gradient = ctx.createLinearGradient(0, h * 0.45, 0, h);
        gradient.addColorStop(0, 'rgba(0,0,0,0)');
        gradient.addColorStop(0.48, 'rgba(0,0,0,0.24)');
        gradient.addColorStop(1, 'rgba(0,0,0,0.80)');

        ctx.fillStyle = gradient;
        ctx.fillRect(0, h * 0.45, w, h * 0.55);

        // Panel transparan bergaya editorial.
        roundedRect(ctx, panelX, panelY, panelW, panelH, radius);

        ctx.fillStyle = 'rgba(5, 18, 22, 0.54)';
        ctx.fill();

        ctx.strokeStyle = 'rgba(255,255,255,0.17)';
        ctx.lineWidth = Math.max(1, u);
        ctx.stroke();

        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';

        // Tanggal.
        const dateY = panelY + 37 * u;

        ctx.fillStyle = '#FFE64D';
        ctx.font = `900 ${Math.round(27 * u)}px Arial, sans-serif`;
        ctx.fillText(date.day, x, dateY);

        const dayWidth = ctx.measureText(date.day).width;

        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(14 * u)}px Arial, sans-serif`;
        ctx.fillText(
            `${date.month.toUpperCase()} ${date.year}`,
            x + dayWidth + 9 * u,
            dateY
        );

        // Judul.
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `900 ${Math.round(38 * u)}px Arial, sans-serif`;
        ctx.fillText('Dokumentasi', x, panelY + 83 * u);

        const titleWidth = ctx.measureText('Dokumentasi').width;

        ctx.fillStyle = '#FFE64D';
        ctx.beginPath();
        ctx.arc(
            x + titleWidth + 6 * u,
            panelY + 79 * u,
            3.5 * u,
            0,
            Math.PI * 2
        );
        ctx.fill();

        // Keterangan halaman.
        ctx.fillStyle = 'rgba(255,255,255,0.88)';
        ctx.font = `500 ${Math.round(13 * u)}px Arial, sans-serif`;

        ctx.fillText(
            fitText(ctx, pageTitle, panelW - 45 * u),
            x,
            panelY + 108 * u
        );

        // Nama lokasi.
        const locationY = panelY + 148 * u;

        ctx.fillStyle = '#FFE64D';
        ctx.beginPath();
        ctx.arc(x + 4 * u, locationY - 5 * u, 5 * u, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#FFFFFF';
        ctx.font = `700 ${Math.round(14 * u)}px Arial, sans-serif`;

        const maxLocationWidth = panelW - 48 * u;
        const place = locationName || 'Lokasi tidak tersedia';

        ctx.fillText(
            fitText(ctx, place, maxLocationWidth),
            x + 18 * u,
            locationY
        );

        // Koordinat GPS dan akurasi.
        ctx.fillStyle = 'rgba(255,255,255,0.78)';
        ctx.font = `500 ${Math.round(11 * u)}px Arial, sans-serif`;

        const coordinateText = coords
            ? `${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)} · ±${Math.round(coords.accuracy)} m`
            : 'Koordinat GPS tidak tersedia';

        ctx.fillText(
            fitText(ctx, coordinateText, maxLocationWidth),
            x + 18 * u,
            locationY + 19 * u
        );

        // Waktu.
        ctx.fillStyle = '#FFE64D';
        ctx.font = `700 ${Math.round(12 * u)}px Arial, sans-serif`;
        ctx.fillText(
            `WITA  ${time}`,
            x + 18 * u,
            panelY + 204 * u
        );

        // Garis pemisah.
        ctx.strokeStyle = 'rgba(255,255,255,0.24)';
        ctx.lineWidth = Math.max(1, u);

        ctx.beginPath();
        ctx.moveTo(x, panelY + 224 * u);
        ctx.lineTo(x + panelW - 36 * u, panelY + 224 * u);
        ctx.stroke();

        // Identitas institusi.
        ctx.fillStyle = '#FFFFFF';
        ctx.font = `800 ${Math.round(12 * u)}px Arial, sans-serif`;
        ctx.fillText(
            'PONPES ABU HURAIRAH',
            x,
            panelY + 246 * u
        );

        ctx.fillStyle = 'rgba(255,255,255,0.76)';
        ctx.font = `500 ${Math.round(10 * u)}px Arial, sans-serif`;
        ctx.fillText(
            COPYRIGHT,
            x,
            panelY + 265 * u
        );

        // Logo ALIM di sudut kanan bawah.
        if (logo.complete && logo.naturalWidth > 0) {
            const logoW = 42 * u;
            const logoH =
                logoW * (logo.naturalHeight / logo.naturalWidth);

            ctx.globalAlpha = 0.92;

            ctx.drawImage(
                logo,
                w - logoW - 24 * u,
                h - logoH - 24 * u,
                logoW,
                logoH
            );

            ctx.globalAlpha = 1;
        }
    }

    async function capture() {
        if (!video.videoWidth || !stream) {
            alert('Kamera belum siap. Silakan tunggu sebentar.');
            return;
        }

        captureBtn.disabled = true;

        try {
            // Tunggu hasil reverse geocoding, maksimal 4 detik.
            await Promise.race([
                locationPromise,
                new Promise(resolve => setTimeout(resolve, 4000))
            ]);

            const canvas = document.createElement('canvas');
            const scale = Math.min(1, MAX_WIDTH / video.videoWidth);

            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);

            const ctx = canvas.getContext('2d');

            ctx.drawImage(
                video,
                0,
                0,
                canvas.width,
                canvas.height
            );

            drawWatermark(canvas);

            const blob = await new Promise(resolve =>
                canvas.toBlob(resolve, 'image/jpeg', 0.90)
            );

            if (!blob) {
                throw new Error('Gagal membuat file foto.');
            }

            if (!targetInput) {
                throw new Error('Input file tujuan tidak ditemukan.');
            }

            const input = document.getElementById(targetInput);

            if (!input) {
                throw new Error(
                    `Elemen input #${targetInput} tidak ditemukan.`
                );
            }

            const file = new File(
                [blob],
                `alim-dokumentasi-${Date.now()}.jpg`,
                {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                }
            );

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            input.files = dataTransfer.files;

            input.dispatchEvent(
                new Event('change', { bubbles: true })
            );

            if (targetPreview) {
                const preview = document.getElementById(targetPreview);

                if (preview) {
                    const objectUrl = URL.createObjectURL(blob);

                    preview.replaceChildren();

                    const image = document.createElement('img');
                    image.src = objectUrl;
                    image.alt = 'Foto bukti dokumentasi';
                    image.className =
                        'img-fluid rounded border mt-1';
                    image.style.maxHeight = '180px';

                    const caption = document.createElement('div');
                    caption.className =
                        'small text-success mt-1';
                    caption.innerHTML =
                        '<i class="ri-check-line"></i> Foto kamera siap diunggah';

                    preview.append(image, caption);
                }
            }

            bootstrap.Modal.getInstance(modalEl)?.hide();

        } catch (error) {
            alert('Gagal mengambil foto: ' + error.message);
        } finally {
            captureBtn.disabled = false;
        }
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        targetInput = button?.dataset.target || targetInput;
        targetPreview = button?.dataset.preview || targetPreview;
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        startCamera();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        stopCamera();
    });

    captureBtn.addEventListener('click', capture);

    window.AlimCamera = {
        open: function (target, preview) {
            targetInput = target;
            targetPreview = preview;

            bootstrap.Modal
                .getOrCreateInstance(modalEl)
                .show();
        }
    };
})();
</script>
