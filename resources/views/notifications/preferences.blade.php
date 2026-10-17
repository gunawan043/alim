@extends('layouts.master')

@section('title') Preferensi Notifikasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Notifikasi @endslot
        @slot('li_2') <a href="{{ route('user.notifications.page', ['userId' => $userId]) }}">Daftar Notifikasi</a> @endslot
        @slot('title') Preferensi Notifikasi @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('user.notifications.preferences.update', ['userId' => $userId]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-send-plane-line me-2 text-primary"></i>Channel Pengiriman</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3 py-2 border-bottom">
                            <div>
                                <div class="fw-medium">Email</div>
                                <div class="small text-muted">Kirim salinan notifikasi ke alamat email akun Anda.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="email_enabled" name="email_enabled" value="1"
                                       {{ old('email_enabled', $prefs->email_enabled) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="d-flex align-items-start justify-content-between gap-3 py-2 border-bottom">
                            <div>
                                <div class="fw-medium">WhatsApp</div>
                                <div class="small text-muted">
                                    Kirim ke nomor WhatsApp pada profil GTK/kontak Anda.
                                    Pengiriman mengikuti gateway yang dipilih admin (Meta Cloud API resmi atau Fonnte).
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="whatsapp_enabled" name="whatsapp_enabled" value="1"
                                       {{ old('whatsapp_enabled', $prefs->whatsapp_enabled) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="d-flex align-items-start justify-content-between gap-3 py-2">
                            <div>
                                <div class="fw-medium">Push / Notifikasi Peramban</div>
                                <div class="small text-muted">Notifikasi langsung di perangkat yang mendukung push.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="push_enabled" name="push_enabled" value="1"
                                       {{ old('push_enabled', $prefs->push_enabled) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-notification-3-line me-2 text-primary"></i>Jenis Notifikasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3 py-2 border-bottom">
                            <div>
                                <div class="fw-medium">Pengingat 5 Menit Sebelum Jam Pelajaran</div>
                                <div class="small text-muted">Pengingat jadwal mengajar beserta tombol buka Scan QR.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="lesson_reminder" name="lesson_reminder" value="1"
                                       {{ old('lesson_reminder', $prefs->lesson_reminder) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="d-flex align-items-start justify-content-between gap-3 py-2">
                            <div>
                                <div class="fw-medium">Pemberitahuan Keterlambatan</div>
                                <div class="small text-muted">Konfirmasi ketika Anda tercatat terlambat masuk kelas.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="late_notification" name="late_notification" value="1"
                                       {{ old('late_notification', $prefs->late_notification) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info small">
                    <i class="ri-information-line me-1"></i>
                    Preferensi ini hanya mengatur <strong>pengiriman ke Anda</strong>. Notifikasi in-app tetap tampil
                    di ikon lonceng. Untuk WhatsApp resmi, admin perlu mengaktifkan Meta Cloud API dan menyiapkan
                    template yang disetujui Meta bila pesan dikirim di luar jendela 24 jam.
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('user.notifications.page', ['userId' => $userId]) }}" class="btn btn-light">
                        <i class="ri-arrow-go-back-line me-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i>Simpan Preferensi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
