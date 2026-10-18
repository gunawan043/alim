@extends('layouts.master')
@section('title') Kamera Dokumentasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Kamera Dokumentasi
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-camera-line me-2 text-primary"></i>Ambil Foto Bukti</h5><p class="text-muted small mb-0">Foto otomatis diberi watermark koordinat, tanggal/jam WITA, dan logo ALIM.</p></div>
                <div class="card-body">
                    <div class="alert alert-info small"><i class="ri-information-line me-1"></i>Gunakan kamera belakang, izinkan akses lokasi, lalu unduh hasil foto. Foto juga bisa dipakai pada laporan kebersihan/listrik/sampah.</div>
                    <input type="file" id="camera_photo" name="camera_photo" class="form-control mb-3" accept="image/*">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="camera_photo" data-preview="camera_photo_preview"><i class="ri-camera-line me-1"></i>Buka Kamera + Watermark</button>
                    <div id="camera_photo_preview" class="mt-3"></div>
                    <div id="downloadWrap" class="mt-3 d-none"><a id="downloadPhoto" class="btn btn-success" download><i class="ri-download-2-line me-1"></i>Unduh Foto</a></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-info">
                <div class="card-header bg-info-subtle"><h5 class="card-title mb-0 text-info">Panduan</h5></div>
                <div class="card-body small text-muted">
                    <ol class="ps-3 mb-0">
                        <li class="mb-2">Aktifkan GPS/lokasi perangkat.</li>
                        <li class="mb-2">Izinkan kamera saat browser meminta.</li>
                        <li class="mb-2">Ambil foto sebelum dan sesudah pekerjaan.</li>
                        <li class="mb-2">Unduh foto atau unggah ke Google Drive.</li>
                        <li>Gunakan foto sebagai bukti laporan.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @include('sarpras.partials.camera-modal')
@endsection

@push('script')
<script>
    (function () {
        const input = document.getElementById('camera_photo');
        const downloadWrap = document.getElementById('downloadWrap');
        const download = document.getElementById('downloadPhoto');
        if (!input) return;
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            download.href = url;
            download.download = file.name || 'foto-alim.jpg';
            downloadWrap.classList.remove('d-none');
        });
    })();
</script>
@endpush
