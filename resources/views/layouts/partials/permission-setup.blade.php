{{-- Izin sekali saat login/pertama akses: Kamera (QR), Lokasi (geofence), Notifikasi (info keterlambatan).
     Ditampilkan sekali per peramban (localStorage); tombol besar agar ramah pengguna awam. --}}
@auth
    @php
        $permUser = auth()->user();
        $isTeachingUser = false;

        try {
            $isTeachingUser = method_exists($permUser, 'hasAnyRole') && $permUser->hasAnyRole([
                'Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Bahasa Arab', 'Guru Tahfidz',
                'Kepala Satuan Pendidikan', 'Wakil Kepala Satuan Pendidikan',
            ]);
        } catch (\Throwable $e) {
            $isTeachingUser = false;
        }
    @endphp

    @if($isTeachingUser)
        <div class="modal fade zoomIn" id="permSetupModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body text-center p-4 pt-0">
                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                   colors="primary:#121331,secondary:#08a88a"
                                   style="width:110px;height:110px;"></lord-icon>
                        <h4 class="mt-2 mb-2">Aktifkan Izin (Sekali Saja)</h4>
                        <p class="text-muted mb-3" style="font-size:1rem;">
                            Agar absen QR dan notifikasi berjalan lancar, izinkan akses berikut.
                            Cukup <strong>sekali</strong> — tidak akan ditanya terus-menerus.
                        </p>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg" id="permSetupCamera">
                                <i class="ri-camera-line me-1"></i> Izinkan Kamera (scan QR)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-lg" id="permSetupGeo">
                                <i class="ri-map-pin-line me-1"></i> Izinkan Lokasi (area sekolah)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-lg" id="permSetupNotif">
                                <i class="ri-notification-3-line me-1"></i> Izinkan Notifikasi
                            </button>
                        </div>

                        <div class="mt-3 small fw-semibold" id="permSetupStatus" style="min-height:20px;"></div>

                        <button type="button" class="btn btn-light mt-2" id="permSetupLater" data-bs-dismiss="modal">
                            Atur nanti
                        </button>

                        <p class="text-muted small mt-3 mb-0">
                            Jika pernah menolak: klik ikon <strong>gembok/kunci</strong> di alamat situs → izinkan Kamera/Lokasi/Notifikasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @once
            @push('scripts')
                <script src="https://cdn.lordicon.com/lordicon.js"></script>
                <script>
                    (function () {
                        var KEY = 'alim_perm_setup_v1';
                        try { if (localStorage.getItem(KEY) === '1') return; } catch (e) {}

                        function markDone() { try { localStorage.setItem(KEY, '1'); } catch (e) {} }

                        document.addEventListener('DOMContentLoaded', function () {
                            var el = document.getElementById('permSetupModal');
                            if (!el || !window.bootstrap) return;

                            var statusEl = document.getElementById('permSetupStatus');
                            function status(text) { if (statusEl) statusEl.textContent = text; }

                            var modal = bootstrap.Modal.getOrCreateInstance(el);
                            modal.show();

                            document.getElementById('permSetupLater')?.addEventListener('click', markDone);
                            el.addEventListener('hidden.bs.modal', markDone, { once: true });

                            document.getElementById('permSetupCamera')?.addEventListener('click', function () {
                                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                    status('Perangkat ini tidak mendukung kamera.');
                                    return;
                                }
                                navigator.mediaDevices.getUserMedia({ video: true })
                                    .then(function (s) { s.getTracks().forEach(function (t) { t.stop(); }); status('✅ Kamera aktif.'); })
                                    .catch(function () { status('❌ Izin kamera ditolak — izinkan lewat ikon gembok di alamat situs.'); });
                            });

                            document.getElementById('permSetupGeo')?.addEventListener('click', function () {
                                if (!('geolocation' in navigator)) {
                                    status('Perangkat ini tidak mendukung lokasi.');
                                    return;
                                }
                                navigator.geolocation.getCurrentPosition(
                                    function () { status('✅ Lokasi aktif.'); },
                                    function () { status('❌ Izin lokasi ditolak — izinkan lewat ikon gembok di alamat situs.'); },
                                    { enableHighAccuracy: true, timeout: 10000 }
                                );
                            });

                            document.getElementById('permSetupNotif')?.addEventListener('click', function () {
                                if (!('Notification' in window)) {
                                    status('Perangkat ini tidak mendukung notifikasi.');
                                    return;
                                }
                                Notification.requestPermission().then(function (p) {
                                    status(p === 'granted' ? '✅ Notifikasi aktif.' : '❌ Notifikasi ditolak — izinkan lewat ikon gembok di alamat situs.');
                                });
                            });
                        });
                    })();
                </script>
            @endpush
        @endonce
    @endif
@endauth
