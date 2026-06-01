@extends('layouts.master')

@section('title')
    {{ $job->judul }} — Detail Lowongan
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')

@php
    $toArr = function ($v) {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        return [];
    };

    // Parse JSON untuk kolom yang dipakai halaman detail
    $persyaratanUmum     = $toArr($job->persyaratan_umum);
    $persyaratanKhusus   = $toArr($job->persyaratan_khusus);
    $pendidikan          = $toArr($job->kualifikasi_pendidikan);
    $pengalaman          = $toArr($job->kualifikasi_pengalaman);
    $kompetensi          = $toArr($job->kompetensi_dibutuhkan);
    $fasilitas           = $toArr($job->fasilitas);
    $tahapan             = $toArr($job->tahapan_seleksi);
    $gaji                = $toArr($job->rentang_gaji);

    // Format gaji
    $formattedGaji = null;
    if (is_array($gaji) && isset($gaji['min'], $gaji['max'])) {
        $formattedGaji = 'Rp ' . number_format((int)$gaji['min'], 0, ',', '.')
                       . ' – Rp ' . number_format((int)$gaji['max'], 0, ',', '.');
    } elseif (is_string($job->rentang_gaji) && $job->rentang_gaji !== '') {
        $formattedGaji = $job->rentang_gaji;
    }

    // Sisa hari & status
    $daysLeftRaw = $job->tanggal_selesai
        ? \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($job->tanggal_selesai), false)
        : null;
    $daysLeft = $daysLeftRaw !== null
        ? ($daysLeftRaw >= 0 ? ceil($daysLeftRaw) : floor($daysLeftRaw))
        : 0;
    $isUrgent = $daysLeft <= 7 && $daysLeft > 0;
    $isClosed = $job->status !== 'aktif'
        || ($job->tanggal_selesai && \Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($job->tanggal_selesai)));

    // Info unit kerja
    $workUnit        = $job->workUnit;
    $workUnitName    = $workUnit->name    ?? 'Pondok Pesantren Abu Hurairah';
    $workUnitAddress = $workUnit->address ?? 'Lombok, NTB';
@endphp

{{-- Header / Banner --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card mt-n4 mx-n4">
            <div class="bg-primary">
                <div class="card-body px-4 pb-4">
                    <div class="row mb-3">
                        <div class="col-md">
                            <div class="row align-items-center g-3">
                                <div class="col-md-auto">
                                    <div class="avatar-md">
                                        <div class="avatar-title bg-white rounded-circle">
                                            <i class="ri-briefcase-2-line fs-2 text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div>
                                        <h4 class="fw-bold mb-3 text-white">{{ $job->judul }}</h4>
                                        <div class="hstack gap-3 flex-wrap text-white">
                                            <div><i class="ri-building-line align-bottom me-1"></i> {{ $workUnitName }}</div>
                                            <div class="vr bg-white opacity-50"></div>
                                            <div><i class="ri-map-pin-2-line align-bottom me-1"></i> {{ $workUnitAddress }}</div>
                                            <div class="vr bg-white opacity-50"></div>
                                            <div>
                                                Kode :
                                                <span class="fw-medium">{{ $job->kode_lowongan ?? '-' }}</span>
                                            </div>
                                            <div class="vr bg-white opacity-50"></div>
                                            <div>
                                                Post Date :
                                                <span class="fw-medium">
                                                    {{ $job->tanggal_mulai ? \Carbon\Carbon::parse($job->tanggal_mulai)->format('d M, Y') : '-' }}
                                                </span>
                                            </div>
                                            <div class="vr bg-white opacity-50"></div>
                                            <div class="badge rounded-pill bg-white text-dark fs-12">
                                                {{ $job->jenis_pegawai ?? $job->status_pegawai ?? '-' }}
                                            </div>
                                            @if($isUrgent)
                                                <div class="badge rounded-pill bg-warning text-dark fs-12">
                                                    <i class="ri-alarm-warning-line me-1"></i> Segera Tutup
                                                </div>
                                            @endif
                                            @if($isClosed)
                                                <div class="badge rounded-pill bg-danger text-white fs-12">
                                                    <i class="ri-lock-line me-1"></i> Ditutup
                                                </div>
                                            @else
                                                <div class="badge rounded-pill bg-success text-white fs-12">
                                                    <i class="ri-checkbox-circle-line me-1"></i> Aktif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <div class="hstack gap-1 flex-wrap mt-4 mt-md-0">
                                <a href="{{ route('user.ats.jobs.edit', ['userId' => $userId, 'job' => $job->id]) }}"
                                   class="btn btn-icon btn-sm bg-white bg-opacity-20 text-white border-0 fs-16"
                                   title="Edit lowongan">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-icon btn-sm bg-info bg-opacity-20 text-white border-0 fs-16"
                                        onclick="copyLink()" title="Salin link lowongan">
                                    <i class="ri-share-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-n5">
    {{-- KOLOM KIRI — Konten utama --}}
    <div class="col-xxl-7">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-4">
                <i class="ri-checkbox-circle-line fs-18"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 d-flex align-items-center gap-2 mb-4">
                <i class="ri-error-warning-line fs-18"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Deskripsi Pekerjaan --}}
        @if($job->deskripsi_pekerjaan)
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-file-text-line me-2 text-primary"></i> Deskripsi Pekerjaan
                    </h5>
                    <div class="text-muted mb-0" style="line-height:1.8;">
                        {{ $job->deskripsi_pekerjaan }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Persyaratan Umum --}}
        @if(!empty($persyaratanUmum))
            <div class="card mt-n2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-list-check-2 me-2 text-success"></i> Persyaratan Umum
                    </h5>
                    <div>
                        @foreach($persyaratanUmum as $item)
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="ri-checkbox-circle-fill text-success mt-1"></i>
                                <span class="text-muted">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Persyaratan Khusus --}}
        @if(!empty($persyaratanKhusus))
            <div class="card mt-n2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-medal-line me-2 text-warning"></i> Persyaratan Khusus
                    </h5>
                    <div>
                        @foreach($persyaratanKhusus as $item)
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="ri-checkbox-circle-fill text-warning mt-1"></i>
                                <span class="text-muted">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Pendidikan & Pengalaman --}}
        @if(!empty($pendidikan) || !empty($pengalaman))
            <div class="row">
                @if(!empty($pendidikan))
                    <div class="col-md-6">
                        <div class="card mt-n2 h-100">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    <i class="ri-graduation-cap-line me-2 text-info"></i> Kualifikasi Pendidikan
                                </h5>
                                <ul class="list-unstyled mb-0">
                                    @foreach($pendidikan as $item)
                                        <li class="d-flex align-items-start gap-2 mb-2">
                                            <i class="ri-checkbox-circle-fill text-info mt-1"></i>
                                            <span class="text-muted">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($pengalaman))
                    <div class="col-md-6">
                        <div class="card mt-n2 h-100">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    <i class="ri-briefcase-line me-2 text-primary"></i> Pengalaman Kerja
                                </h5>
                                <ul class="list-unstyled mb-0">
                                    @foreach($pengalaman as $item)
                                        <li class="d-flex align-items-start gap-2 mb-2">
                                            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
                                            <span class="text-muted">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Kompetensi --}}
        @if(!empty($kompetensi))
            <div class="card mt-2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-brain-line me-2 text-purple"></i> Kompetensi
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($kompetensi as $item)
                            <span class="badge bg-success-subtle text-success p-2 rounded-3">
                                <i class="ri-price-tag-3-line me-1"></i> {{ $item }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Fasilitas --}}
        @if(!empty($fasilitas))
            <div class="card mt-n2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-gift-line me-2 text-danger"></i> Fasilitas
                    </h5>
                    <div>
                        @foreach($fasilitas as $item)
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="ri-checkbox-circle-fill text-danger mt-1"></i>
                                <span class="text-muted">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Tahapan Seleksi --}}
        @if(!empty($tahapan))
            <div class="card mt-n2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-flow-chart me-2 text-primary"></i> Tahapan Seleksi
                    </h5>
                    <div class="stepper" style="display: flex; flex-direction: column; gap: 16px;">
                        @foreach($tahapan as $index => $step)
                            @php
                                $palette = ['primary', 'info', 'warning', 'success', 'danger', 'secondary'];
                                $color   = $palette[$index % count($palette)];
                            @endphp
                            <div class="d-flex gap-3 align-items-start">
                                <div class="avatar-sm">
                                    <div class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded-circle">
                                        {{ $index + 1 }}
                                    </div>
                                </div>
                                <div class="flex-grow-1 mt-2">
                                    <h6 class="fw-semibold mb-1">{{ $step }}</h6>
                                    <p class="text-muted small mb-0">Tahap {{ $index + 1 }} dari proses seleksi</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Pipeline Summary (khusus personalia) --}}
        @if(isset($applicationStats))
            <div class="card mt-n2">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="ri-git-branch-line me-2 text-primary"></i> Ringkasan Pelamar
                    </h5>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="border:1px solid #f1f5f9;border-radius:8px;">
                                <div style="font-size:1.4rem;font-weight:700;color:#3b82f6;">
                                    {{ $applicationStats['total'] ?? 0 }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Total</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="border:1px solid #f1f5f9;border-radius:8px;">
                                <div style="font-size:1.4rem;font-weight:700;color:#ca8a04;">
                                    {{ $applicationStats['menunggu'] ?? 0 }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Menunggu</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="border:1px solid #f1f5f9;border-radius:8px;">
                                <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">
                                    {{ $applicationStats['seleksi'] ?? 0 }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Seleksi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="border:1px solid #f1f5f9;border-radius:8px;">
                                <div style="font-size:1.4rem;font-weight:700;color:#16a34a;">
                                    {{ $applicationStats['diterima'] ?? 0 }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Diterima</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Aksi (khusus personalia) --}}
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('user.ats.jobs.edit', ['userId' => $userId, 'job' => $job->id]) }}" class="btn btn-primary">
                <i class="ri-edit-line me-1"></i> Edit Lowongan
            </a>
            @if($job->status == 'aktif')
                <button class="btn btn-outline-danger" onclick="closeJob()">
                    <i class="ri-close-circle-line me-1"></i> Tutup Lowongan
                </button>
            @else
                <button class="btn btn-outline-success" onclick="closeJob()">
                    <i class="ri-checkbox-circle-line me-1"></i> Aktifkan Lowongan
                </button>
            @endif
            <button class="btn btn-outline-info" onclick="duplicateJob()">
                <i class="ri-file-copy-line me-1"></i> Duplikat
            </button>
            <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- KOLOM KANAN — Sidebar --}}
    <div class="col-xxl-5">

        {{-- Aksi Cepat --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ri-flashlight-line me-2 text-primary"></i> Aksi Cepat
                </h5>
            </div>
            <div class="card-body">
                <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}?job_id={{ $job->id }}"
                   class="btn btn-soft-primary w-100 mb-2 justify-content-start">
                    <i class="ri-file-list-3-line me-2 fs-16"></i> Lihat Semua Pelamar
                    <span class="badge bg-primary ms-auto">{{ $applicationStats['total'] ?? 0 }}</span>
                </a>
                <a href="{{ route('user.ats.jobs.applications', ['userId' => $userId, 'job' => $job->id]) }}"
                   class="btn btn-soft-info w-100 mb-2 justify-content-start">
                    <i class="ri-bar-chart-line me-2 fs-16"></i> Pipeline & Statistik
                </a>
                <a href="{{ route('user.ats.pipeline.board', ['userId' => $userId, 'jobId' => $job->id]) }}"
                   class="btn btn-soft-success w-100 mb-2 justify-content-start">
                    <i class="ri-layout-grid-line me-2 fs-16"></i> Board Pipeline
                </a>
                <button type="button" onclick="exportReport()" class="btn btn-soft-danger w-100 justify-content-start">
                    <i class="ri-file-excel-line me-2 fs-16"></i> Export Data (Excel)
                </button>
            </div>
        </div>

        {{-- Ringkasan Lowongan --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ri-information-line me-2 text-primary"></i> Ringkasan Lowongan
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table mb-0">
                        <tbody>
                            @if($job->kode_lowongan)
                                <tr>
                                    <td class="fw-medium"><i class="ri-price-tag-3-line me-1 text-muted"></i> Kode Lowongan</td>
                                    <td><span class="badge bg-secondary">{{ $job->kode_lowongan }}</span></td>
                                </tr>
                            @endif
                            @if($job->posisi)
                                <tr>
                                    <td class="fw-medium"><i class="ri-user-2-line me-1 text-muted"></i> Posisi</td>
                                    <td>{{ $job->posisi }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="fw-medium"><i class="ri-building-line me-1 text-muted"></i> Unit Kerja</td>
                                <td>{{ $workUnitName }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="ri-map-pin-2-line me-1 text-muted"></i> Lokasi</td>
                                <td>{{ $workUnitAddress }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="ri-pencil-ruler-2-line me-1 text-muted"></i> Jenis Pegawai</td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $job->jenis_pegawai ?? $job->status_pegawai ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                            @if($job->kuota)
                                <tr>
                                    <td class="fw-medium"><i class="ri-group-line me-1 text-muted"></i> Kuota</td>
                                    <td>
                                        {{ $job->kuota_terisi ?? 0 }} / {{ $job->kuota }} posisi terisi
                                        @php
                                            $pct = $job->kuota > 0 ? round((($job->kuota_terisi ?? 0) / $job->kuota) * 100) : 0;
                                        @endphp
                                        <div class="progress mt-1" style="height:5px;">
                                            <div class="progress-bar bg-success"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @if($formattedGaji)
                                <tr>
                                    <td class="fw-medium"><i class="ri-money-dollar-circle-line me-1 text-muted"></i> Rentang Gaji</td>
                                    <td class="fw-semibold text-success">{{ $formattedGaji }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="fw-medium"><i class="ri-calendar-line me-1 text-muted"></i> Tanggal Mulai</td>
                                <td>{{ $job->tanggal_mulai ? \Carbon\Carbon::parse($job->tanggal_mulai)->format('d M, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="ri-calendar-close-line me-1 text-muted"></i> Batas Waktu</td>
                                <td class="{{ $isUrgent ? 'text-danger fw-bold' : '' }}">
                                    {{ $job->tanggal_selesai ? \Carbon\Carbon::parse($job->tanggal_selesai)->format('d M, Y') : '-' }}
                                    @if($isUrgent)
                                        <br><small class="text-danger">
                                            <i class="ri-alarm-warning-line me-1"></i>{{ $daysLeft }} hari lagi
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="ri-bar-chart-box-line me-1 text-muted"></i> Status</td>
                                <td>
                                    @if($isClosed)
                                        <span class="badge bg-danger">Ditutup</span>
                                    @else
                                        <span class="badge bg-success">Aktif</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Lokasi --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ri-map-pin-2-line me-2 text-info"></i> Lokasi
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="ri-map-pin-line text-danger me-1"></i> {{ $workUnitAddress }}
                </p>
                <div class="ratio ratio-4x3">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d252140.12727516213!2d116.04574384999999!3d-8.669743999999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dcdb7d23e8c1eb5%3A0x9c9e3c6f9c9e3c6f!2sLombok%2C%20NTB!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid"
                        style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function closeJob() {
        const isAktif = @json($job->status == 'aktif');
        Swal.fire({
            title: isAktif ? 'Tutup Lowongan?' : 'Aktifkan Lowongan?',
            text: isAktif
                ? 'Lowongan ini tidak akan bisa dilamar lagi sampai diaktifkan kembali.'
                : 'Lowongan akan dipublikasikan dan dapat dilamar oleh pelamar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isAktif ? '#e11d48' : '#16a34a',
            confirmButtonText: isAktif ? 'Ya, Tutup' : 'Ya, Aktifkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch("{{ route('user.ats.jobs.toggle-status', ['userId' => $userId, 'job' => $job->id]) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil', data.message ?? 'Status lowongan diperbarui.', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', data.message ?? 'Terjadi kesalahan.', 'error');
                    }
                }).catch(() => {
                    Swal.fire('Gagal', 'Tidak dapat menghubungi server.', 'error');
                });
            }
        });
    }

    function duplicateJob() {
        fetch("{{ route('user.ats.jobs.duplicate', ['userId' => $userId, 'job' => $job->id]) }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                Swal.fire('Berhasil', 'Lowongan berhasil diduplikasi.', 'success').then(() => {
                    window.location.href = data.redirect
                        ?? "{{ route('user.ats.jobs.index', ['userId' => $userId]) }}";
                });
            } else {
                Swal.fire('Gagal', data.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(
            () => Swal.fire({ icon: 'success', title: 'Tersalin!', text: 'Link lowongan berhasil disalin.', timer: 1500, showConfirmButton: false }),
            () => Swal.fire('Gagal', 'Tidak dapat menyalin link.', 'error')
        );
    }

    function exportReport() {
        window.location.href = "{{ route('user.ats.applications.export-excel', ['userId' => $userId]) }}?job_id={{ $job->id }}";
    }
</script>
@endsection
