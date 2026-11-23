@extends('layouts.master')

@section('title', 'Kartu Pelajar')

@section('css')
<style>
    .card-showcase {
        position:relative; overflow:hidden; border-radius:1.25rem; padding:1.5rem;
        background:
            radial-gradient(circle at 15% 0%, rgba(56,189,248,.22), transparent 32%),
            radial-gradient(circle at 88% 95%, rgba(20,184,166,.18), transparent 35%),
            linear-gradient(145deg, #0b1220 0%, #0f1f3d 58%, #0b1220 100%);
        border:1px solid rgba(148,163,184,.2);
        box-shadow:0 24px 60px rgba(2,6,23,.28);
    }
    .card-showcase::before {
        content:""; position:absolute; inset:0; pointer-events:none; opacity:.22;
        background-image:linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
        background-size:32px 32px; mask-image:linear-gradient(to bottom, rgba(0,0,0,.7), transparent 70%);
    }
    .card-showcase > * { position:relative; z-index:1; }
    .showcase-eyebrow { font-size:.68rem; letter-spacing:.16em; text-transform:uppercase; color:#7dd3fc; font-weight:700; }
    .showcase-title { color:#f8fafc; font-weight:700; letter-spacing:-.02em; }
    .showcase-sub { color:#94a3b8; font-size:.82rem; }
    .card-preview-stage { display:flex; flex-wrap:wrap; gap:1.75rem; align-items:flex-start; justify-content:center; margin-top:1.25rem; perspective:1100px; }
    .student-card-preview { opacity:0; transform:translateY(18px) scale(.985); will-change:transform, opacity; }
    .card-showcase.cards-ready .student-card-preview { opacity:1; transform:translateY(0) scale(1); transition:opacity .5s cubic-bezier(.2,.8,.2,1), transform .5s cubic-bezier(.2,.8,.2,1), box-shadow .35s ease; }
    .card-showcase.cards-ready .student-card-preview:hover { box-shadow:0 30px 60px rgba(2,6,23,.55), 0 4px 12px rgba(2,6,23,.3); }
    .autosave-status.saved i { animation:status-pop .35s ease; }
    @keyframes status-pop { 0% { transform:scale(.6); opacity:.4; } 100% { transform:scale(1); opacity:1; } }
    @media (prefers-reduced-motion: reduce) {
        .student-card-preview, .card-showcase.cards-ready .student-card-preview { opacity:1 !important; transform:none !important; transition:none !important; }
    }
    .card-preview-wrap { display:flex; flex-direction:column; align-items:center; gap:.55rem; }
    .card-preview-label { font-size:.66rem; text-transform:uppercase; letter-spacing:.14em; color:#94a3b8; font-weight:700; }
    .student-card-preview {
        position:relative; overflow:hidden; border-radius:3.2mm;
        border:1px solid rgba(255,255,255,.35);
        background:linear-gradient(135deg,#e2e8f0 0%,#f8fafc 100%) center/100% 100% no-repeat;
        color:#0f172a;
        box-shadow:0 22px 45px rgba(2,6,23,.45), 0 3px 8px rgba(2,6,23,.25);
        flex:0 0 auto;
    }
    .student-card-preview.front, .student-card-preview.back { width:var(--card-w,85.6mm); height:var(--card-h,53.98mm); }
    .student-card-preview::after {
        content:""; position:absolute; inset:0; pointer-events:none; border-radius:inherit;
        background:linear-gradient(115deg, rgba(255,255,255,.28), transparent 32%, transparent 70%, rgba(255,255,255,.12));
        mix-blend-mode:screen; opacity:.45;
    }
    .card-photo-box { position:absolute; left:var(--photo-left,4mm); top:var(--photo-top,10mm); width:var(--photo-w,20mm); height:var(--photo-h,25mm); z-index:2; }
    .card-photo-box img, .card-photo-box .photo-empty { width:100%; height:100%; object-fit:cover; border-radius:1mm; display:block; }
    .card-photo-box .photo-empty { background:#dbeafe; border:1px dashed #94a3b8; display:flex; align-items:center; justify-content:center; color:#64748b; }
    .card-info-table { position:absolute; left:calc(var(--photo-left,4mm) + var(--photo-w,20mm) + 3mm); right:4mm; top:8mm; border-collapse:collapse; font-family:'Cambria','Caladea',serif; font-size:var(--info-size,7.8pt); line-height:var(--line-height,1.25); z-index:2; }
    .student-card-preview.no-photo .card-info-table { left:4mm; }
    .card-info-table td { padding:0; vertical-align:top; }
    .card-info-table .label { width:21mm; white-space:nowrap; }
    .card-info-table .colon { width:3mm; text-align:center; }
    .card-signature { position:absolute; right:4mm; bottom:3mm; width:40mm; text-align:center; font-family:'Cambria','Caladea',serif; z-index:4; pointer-events:none; }
    .card-signature img { width:var(--sig-w,22mm); height:var(--sig-h,12mm); object-fit:contain; display:block; margin:0 auto; }
    .card-signature .sig-empty { width:var(--sig-w,22mm); height:var(--sig-h,12mm); border-bottom:1px solid #94a3b8; margin:0 auto; }
    .card-signature .sig-title { font-size:var(--sig-font-size,7pt); line-height:1.2; }
    .card-signature .sig-name { font-size:var(--sig-font-size,7pt); font-weight:700; margin-top:.5mm; }
    .card-back-note { position:absolute; left:5mm; right:5mm; top:5mm; font-family:'Cambria','Caladea',serif; font-size:var(--back-size,7.5pt); line-height:var(--back-line-height,1.35); z-index:2; }
    .card-back-qr { position:absolute; left:var(--back-qr-left,60mm); top:var(--back-qr-top,24mm); width:var(--back-qr-size,22mm); height:var(--back-qr-size,22mm); z-index:2; }
    .card-back-qr img { width:100%; height:100%; display:block; }
    .autosave-status { font-size:.75rem; color:#94a3b8; display:inline-flex; align-items:center; gap:.35rem; }
    .autosave-status.saved { color:#5eead4; }
    .autosave-status.error { color:#fca5a5; }
    .student-photo-thumb { width:42px; height:42px; object-fit:cover; border-radius:9px; background:#e2e8f0; }
    .settings-panel { border:1px solid #e2e8f0; border-radius:.9rem; background:#fff; }
    .settings-panel .form-label { font-size:.75rem; color:#64748b; margin-bottom:.25rem; }
    .field-check { font-size:.8rem; }
    @media (max-width: 767.98px) { .student-card-preview { max-width:100%; } .card-showcase { padding:1rem; } }
</style>
@endsection

@section('content')
@php
    $selectedName = $selected?->name ?? 'Nama Santri';
    $selectedNis = $selected?->nis ?: '-';
    $selectedNisn = $selected?->nisn ?: '-';
    $selectedGroup = $selected?->currentClassHistory?->studyGroup;
    $selectedClassText = $selectedGroup ? trim(($selectedGroup->gradeLevel?->name ?? '').' '.$selectedGroup->name) : '-';
    $selectedTtl = trim(($selected?->birth_place ?: '').($selected?->birth_date ? (($selected?->birth_place ? ', ' : '').$selected?->birth_date->format('d/m/Y')) : '')) ?: '-';
    $selectedAddress = $selected?->address ?: '-';
    $showFront = $layout['front'];
    $showBack = $layout['back'];
    $identityValue = $showFront['show_nis'] && $showFront['show_nisn']
        ? $selectedNis.' / '.$selectedNisn
        : ($showFront['show_nis'] ? $selectedNis : ($showFront['show_nisn'] ? $selectedNisn : '-'));
@endphp

@component('components.breadcrumb')
    @slot('li_1') Data Santri @endslot
    @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Daftar Santri</a> @endslot
    @slot('title') Kartu Pelajar @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card-showcase mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <div class="showcase-eyebrow">Identitas Santri</div>
            <h3 class="showcase-title mb-1">Kartu Pelajar {{ $school->name }}</h3>
            <div class="showcase-sub">Format berlaku untuk seluruh rombel di sekolah ini. Perubahan pengaturan tersimpan otomatis.</div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span id="autosaveStatus" class="autosave-status"><i class="ri-cloud-line"></i>Tersimpan otomatis</span>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#cardSettingsPanel">
                <i class="ri-equalizer-line me-1"></i>Atur Desain
            </button>
            @if($selected)
                <a href="{{ route('user.students.cards.print', ['userId' => $userId, 'student_id' => $selected->id]) }}" class="btn btn-sm btn-danger">
                    <i class="ri-file-pdf-line me-1"></i>Cetak PDF
                </a>
            @endif
        </div>
    </div>

    <div class="card-preview-stage">
        <div class="card-preview-wrap">
            <div class="card-preview-label">Sisi Depan</div>
            <div class="student-card-preview front {{ $showFront['show_photo'] ? '' : 'no-photo' }}" id="cardFront"
                 style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;--info-size:{{ $showFront['font_size'] }}pt;--line-height:{{ $showFront['line_height'] }};--photo-w:{{ $showFront['photo_width'] }}mm;--photo-h:{{ $showFront['photo_height'] }}mm;--photo-left:{{ $showFront['photo_left'] }}mm;--photo-top:{{ $showFront['photo_top'] }}mm;--sig-w:{{ $showFront['signature_width'] }}mm;--sig-h:{{ $showFront['signature_height'] }}mm;--sig-font-size:{{ $showFront['signature_font_size'] }}pt;{{ $frontBackground ? "background-image:url('".$frontBackground."');" : '' }}">
                <div class="card-photo-box" data-preview-side="front" data-preview-field="show_photo" style="{{ $showFront['show_photo'] ? '' : 'display:none' }}">
                    @if($selected?->photo_path)
                        <img src="{{ asset('storage/'.$selected->photo_path) }}" alt="Foto {{ $selectedName }}">
                    @else
                        <div class="photo-empty"><i class="ri-user-line"></i></div>
                    @endif
                </div>
                <table class="card-info-table">
                    @if($showFront['show_name'])
                        <tr data-preview-side="front" data-preview-field="show_name"><td class="label">Nama</td><td class="colon">:</td><td>{{ $selectedName }}</td></tr>
                    @endif
                    @if($showFront['show_nis'] || $showFront['show_nisn'])
                        <tr data-preview-side="front" data-preview-field="show_nis"><td class="label">NIS/NISN</td><td class="colon">:</td><td>{{ $identityValue }}</td></tr>
                    @endif
                    @if($showFront['show_ttl'])
                        <tr data-preview-side="front" data-preview-field="show_ttl"><td class="label">TTL</td><td class="colon">:</td><td>{{ $selectedTtl }}</td></tr>
                    @endif
                    @if($showFront['show_address'])
                        <tr data-preview-side="front" data-preview-field="show_address"><td class="label">Alamat</td><td class="colon">:</td><td>{{ $selectedAddress }}</td></tr>
                    @endif
                    @if($showFront['show_class'])
                        <tr data-preview-side="front" data-preview-field="show_class"><td class="label">Kelas</td><td class="colon">:</td><td>{{ $selectedClassText }}</td></tr>
                    @endif
                </table>
                <div class="card-signature" data-preview-side="front" data-preview-field="show_signature" style="{{ $showFront['show_signature'] ? '' : 'display:none' }}">
                    @if($showFront['show_signature_title'])<div class="sig-title" data-preview-side="front" data-preview-field="show_signature_title">{{ $signatureTitle }}</div>@endif
                    @if($signature)
                        <img src="{{ $signature }}" alt="TTD">
                    @else
                        <div class="sig-empty"></div>
                    @endif
                    @if($showFront['show_signature_name'])<div class="sig-name" data-preview-side="front" data-preview-field="show_signature_name">{{ $signatureName }}</div>@endif
                </div>
            </div>
        </div>

        <div class="card-preview-wrap">
            <div class="card-preview-label">Sisi Belakang</div>
            <div class="student-card-preview back" id="cardBack"
                 style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;--back-size:{{ $showBack['font_size'] }}pt;--back-line-height:{{ $showBack['line_height'] }};--back-qr-left:{{ $showBack['back_qr_left'] }}mm;--back-qr-top:{{ $showBack['back_qr_top'] }}mm;--back-qr-size:{{ $showBack['back_qr_size'] }}mm;{{ $backBackground ? "background-image:url('".$backBackground."');" : '' }}">
                <div class="card-back-note" data-preview-side="back" data-preview-field="show_note" style="{{ $showBack['show_note'] ? '' : 'display:none' }}">Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.</div>
                <div class="card-back-qr" data-preview-side="back" data-preview-field="show_back_qr" style="{{ $showBack['show_back_qr'] ? '' : 'display:none' }}">
                    @if($backQr)<img src="{{ $backQr }}" alt="QR Belakang">@endif
                </div>
            </div>
        </div>
    </div>

    @if($selected)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-3" style="border-top:1px solid rgba(148,163,184,.18)">
            <div class="showcase-sub">
                <i class="ri-user-line me-1"></i>{{ $selected->name }} · {{ $selected->nis ?: '-' }} / {{ $selected->nisn ?: '-' }} · {{ $selectedClassText }}
            </div>
            <a href="{{ route('user.students.cards.print', ['userId' => $userId] + request()->only(['q', 'study_group_id'])) }}" class="btn btn-sm btn-outline-light">
                <i class="ri-printer-line me-1"></i>Cetak Semua Hasil Pencarian
            </a>
        </div>
    @endif
</div>

<div class="collapse mb-4" id="cardSettingsPanel">
    <form method="POST" action="{{ route('user.students.cards.settings', ['userId' => $userId]) }}" enctype="multipart/form-data" id="cardSettingsForm" class="settings-panel p-3 p-lg-4">
        @csrf
        <input type="hidden" name="selected_student_id" value="{{ $selected?->id }}">

        <div class="row g-4">
            <div class="col-xl-5">
                <h6 class="mb-3">Background &amp; Tanda Tangan</h6>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Background Depan</label><input type="file" name="front_background" id="frontBackgroundInput" class="form-control form-control-sm" accept="image/*"></div>
                    <div class="col-6"><label class="form-label">Background Belakang</label><input type="file" name="back_background" id="backBackgroundInput" class="form-control form-control-sm" accept="image/*"></div>
                    <div class="col-6"><label class="form-label">Gambar TTD KSP</label><input type="file" name="signature" id="signatureInput" class="form-control form-control-sm" accept="image/*"></div>
                    <div class="col-3"><label class="form-label">Lebar (mm)</label><input type="number" step="0.01" name="card_width_mm" id="cardWidth" class="form-control form-control-sm" value="{{ $setting->card_width_mm }}"></div>
                    <div class="col-3"><label class="form-label">Tinggi (mm)</label><input type="number" step="0.01" name="card_height_mm" id="cardHeight" class="form-control form-control-sm" value="{{ $setting->card_height_mm }}"></div>
                    <div class="col-6"><label class="form-label">Jabatan TTD</label><input type="text" name="signature_title" class="form-control form-control-sm" value="{{ $setting->signature_title ?: $signatureTitle }}"></div>
                    <div class="col-6"><label class="form-label">Nama TTD</label><input type="text" name="signature_name" class="form-control form-control-sm" value="{{ $setting->signature_name ?: $signatureName }}"></div>
                </div>
            </div>

            <div class="col-xl-4">
                <h6 class="mb-3">Informasi Depan</h6>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach(['show_photo' => 'Foto', 'show_name' => 'Nama', 'show_nis' => 'NIS', 'show_nisn' => 'NISN', 'show_ttl' => 'TTL', 'show_address' => 'Alamat', 'show_class' => 'Kelas', 'show_signature' => 'TTD', 'show_signature_name' => 'Nama TTD', 'show_signature_title' => 'Jabatan TTD'] as $key => $label)
                        <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[front][{{ $key }}]" value="1" data-side="front" data-field="{{ $key }}" {{ $showFront[$key] ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="row g-2">
                    <div class="col-4"><label class="form-label">Font Info</label><input type="number" step="0.1" name="layout[front][font_size]" class="form-control form-control-sm preview-number" data-side="front" data-var="info-size" value="{{ $showFront['font_size'] }}"></div>
                    <div class="col-4"><label class="form-label">Jarak Baris</label><input type="number" step="0.05" name="layout[front][line_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="line-height" value="{{ $showFront['line_height'] }}"></div>
                    <div class="col-4"><label class="form-label">Font TTD</label><input type="number" step="0.1" name="layout[front][signature_font_size]" class="form-control form-control-sm preview-number" data-side="front" data-var="sig-font-size" value="{{ $showFront['signature_font_size'] }}"></div>
                    <div class="col-3"><label class="form-label">Lebar Foto</label><input type="number" step="0.5" name="layout[front][photo_width]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-w" value="{{ $showFront['photo_width'] }}"></div>
                    <div class="col-3"><label class="form-label">Tinggi Foto</label><input type="number" step="0.5" name="layout[front][photo_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-h" value="{{ $showFront['photo_height'] }}"></div>
                    <div class="col-3"><label class="form-label">Foto Kiri</label><input type="number" step="0.5" name="layout[front][photo_left]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-left" value="{{ $showFront['photo_left'] }}"></div>
                    <div class="col-3"><label class="form-label">Foto Atas</label><input type="number" step="0.5" name="layout[front][photo_top]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-top" value="{{ $showFront['photo_top'] }}"></div>
                    <div class="col-6"><label class="form-label">Lebar TTD</label><input type="number" step="0.5" name="layout[front][signature_width]" class="form-control form-control-sm preview-number" data-side="front" data-var="sig-w" value="{{ $showFront['signature_width'] }}"></div>
                    <div class="col-6"><label class="form-label">Tinggi TTD</label><input type="number" step="0.5" name="layout[front][signature_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="sig-h" value="{{ $showFront['signature_height'] }}"></div>
                </div>
            </div>

            <div class="col-xl-3">
                <h6 class="mb-3">Belakang</h6>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[back][show_note]" value="1" data-side="back" data-field="show_note" {{ $showBack['show_note'] ? 'checked' : '' }}> Catatan</label>
                    <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[back][show_back_qr]" value="1" data-side="back" data-field="show_back_qr" {{ $showBack['show_back_qr'] ? 'checked' : '' }}> QR</label>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Isi QR</label><select name="layout[back][back_qr_source]" class="form-select form-select-sm"><option value="nis" {{ $showBack['back_qr_source'] === 'nis' ? 'selected' : '' }}>NIS</option><option value="nisn" {{ $showBack['back_qr_source'] === 'nisn' ? 'selected' : '' }}>NISN</option><option value="both" {{ $showBack['back_qr_source'] === 'both' ? 'selected' : '' }}>NIS + NISN</option></select></div>
                    <div class="col-6"><label class="form-label">Ukuran QR</label><input type="number" step="0.5" name="layout[back][back_qr_size]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-size" value="{{ $showBack['back_qr_size'] }}"></div>
                    <div class="col-6"><label class="form-label">QR Kiri</label><input type="number" step="0.5" name="layout[back][back_qr_left]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-left" value="{{ $showBack['back_qr_left'] }}"></div>
                    <div class="col-6"><label class="form-label">QR Atas</label><input type="number" step="0.5" name="layout[back][back_qr_top]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-top" value="{{ $showBack['back_qr_top'] }}"></div>
                    <div class="col-6"><label class="form-label">Font Catatan</label><input type="number" step="0.1" name="layout[back][font_size]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-size" value="{{ $showBack['font_size'] }}"></div>
                    <div class="col-6"><label class="form-label">Jarak Baris</label><input type="number" step="0.05" name="layout[back][line_height]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-line-height" value="{{ $showBack['line_height'] }}"></div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div><h5 class="card-title mb-0">Daftar Santri</h5><p class="text-muted mb-0">Cari nama/NIS/NISN, pilih untuk preview, lalu cetak.</p></div>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama, NIS, NISN..." value="{{ request('q') }}">
            <button class="btn btn-primary btn-sm">Cari</button>
            <a href="{{ route('user.students.cards.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">Reset</a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:64px">Foto</th><th>Nama</th><th>NIS / NISN</th><th>Kelas</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($students as $student)
                    @php $sg = $student->currentClassHistory?->studyGroup; @endphp
                    <tr class="{{ $selected?->id === $student->id ? 'table-primary' : '' }}">
                        <td>
                            @if($student->photo_path)
                                <img src="{{ asset('storage/'.$student->photo_path) }}" alt="{{ $student->name }}" class="student-photo-thumb">
                            @else
                                <span class="student-photo-thumb d-inline-flex align-items-center justify-content-center text-muted"><i class="ri-user-line"></i></span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $student->name }}</td>
                        <td class="small text-muted">{{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}</td>
                        <td class="small">{{ $sg ? trim(($sg->gradeLevel?->name ?? '').' '.$sg->name) : '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('user.students.cards.index', ['userId' => $userId, 'q' => request('q'), 'student_id' => $student->id]) }}" class="btn btn-sm btn-soft-primary">Pilih</a>
                            <a href="{{ route('user.students.cards.print', ['userId' => $userId, 'student_id' => $student->id]) }}" class="btn btn-sm btn-outline-danger"><i class="ri-file-pdf-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Santri tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('script')
<script>
    const showcase = document.querySelector('.card-showcase');
    const previewCards = Array.from(document.querySelectorAll('.student-card-preview'));

    requestAnimationFrame(function () {
        setTimeout(function () { showcase?.classList.add('cards-ready'); }, 80);
    });

    previewCards.forEach(function (card) {
        card.addEventListener('pointermove', function (event) {
            if (event.pointerType === 'touch' || window.innerWidth < 992) return;
            const rect = card.getBoundingClientRect();
            const px = (event.clientX - rect.left) / rect.width - .5;
            const py = (event.clientY - rect.top) / rect.height - .5;
            card.style.transform = 'perspective(900px) rotateX(' + (-py * 5).toFixed(2) + 'deg) rotateY(' + (px * 6).toFixed(2) + 'deg) translateY(-3px)';
        });
        card.addEventListener('pointerleave', function () {
            card.style.transform = '';
        });
    });

    const cardFront = document.getElementById('cardFront');
    const cardBack = document.getElementById('cardBack');
    const settingsForm = document.getElementById('cardSettingsForm');
    const autosaveStatus = document.getElementById('autosaveStatus');
    const mmVars = ['photo-w', 'photo-h', 'photo-left', 'photo-top', 'sig-w', 'sig-h', 'back-qr-left', 'back-qr-top', 'back-qr-size'];
    let saveTimer = null;

    document.querySelectorAll('.preview-toggle').forEach(function (input) {
        input.addEventListener('change', function () {
            const card = input.dataset.side === 'front' ? cardFront : cardBack;
            card?.querySelectorAll('[data-preview-side="' + input.dataset.side + '"][data-preview-field="' + input.dataset.field + '"]').forEach(function (el) {
                el.style.display = input.checked ? '' : 'none';
            });
            if (input.dataset.side === 'front' && input.dataset.field === 'show_photo') {
                card.classList.toggle('no-photo', ! input.checked);
            }
        });
    });

    document.querySelectorAll('.preview-number').forEach(function (input) {
        input.addEventListener('input', function () {
            const card = input.dataset.side === 'front' ? cardFront : cardBack;
            const unit = mmVars.includes(input.dataset.var) ? 'mm' : (input.dataset.var.includes('line-height') ? '' : 'pt');
            card?.style.setProperty('--' + input.dataset.var, input.value + unit);
        });
    });

    function updateSize() {
        const w = document.getElementById('cardWidth').value + 'mm';
        const h = document.getElementById('cardHeight').value + 'mm';
        [cardFront, cardBack].forEach(function (card) { if (card) { card.style.width = w; card.style.height = h; } });
    }
    document.getElementById('cardWidth')?.addEventListener('input', updateSize);
    document.getElementById('cardHeight')?.addEventListener('input', updateSize);

    function previewBackground(inputId, cardId) {
        document.getElementById(inputId)?.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (! file) return;
            const card = document.getElementById(cardId);
            card.style.backgroundImage = 'url(' + URL.createObjectURL(file) + ')';
            card.style.backgroundSize = '100% 100%';
        });
    }
    previewBackground('frontBackgroundInput', 'cardFront');
    previewBackground('backBackgroundInput', 'cardBack');

    document.getElementById('signatureInput')?.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (! file) return;
        const wrap = document.querySelector('[data-preview-side="front"][data-preview-field="show_signature"]');
        const img = wrap?.querySelector('img, .sig-empty');
        if (img) img.outerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="TTD">';
    });

    function scheduleAutosave() {
        if (! settingsForm) return;
        clearTimeout(saveTimer);
        autosaveStatus.className = 'autosave-status';
        autosaveStatus.innerHTML = '<i class="ri-loader-4-line"></i>Menyimpan...';
        saveTimer = setTimeout(saveSettings, 500);
    }

    async function saveSettings() {
        try {
            const response = await fetch(settingsForm.action, {
                method: 'POST',
                body: new FormData(settingsForm),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json().catch(function () { return {}; });
            if (! response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Gagal menyimpan.');
            }
            autosaveStatus.className = 'autosave-status saved';
            autosaveStatus.innerHTML = '<i class="ri-checkbox-circle-line"></i>Tersimpan otomatis';
            ['frontBackgroundInput', 'backBackgroundInput', 'signatureInput'].forEach(function (id) {
                const input = document.getElementById(id);
                if (input) input.value = '';
            });
        } catch (error) {
            autosaveStatus.className = 'autosave-status error';
            autosaveStatus.innerHTML = '<i class="ri-error-warning-line"></i>' + (error.message || 'Gagal menyimpan.');
        }
    }

    settingsForm?.addEventListener('submit', function (event) { event.preventDefault(); scheduleAutosave(); });
    settingsForm?.querySelectorAll('input, select').forEach(function (input) {
        input.addEventListener('change', scheduleAutosave);
        if (input.type !== 'file' && input.type !== 'checkbox' && input.tagName !== 'SELECT') {
            input.addEventListener('input', scheduleAutosave);
        }
    });
</script>
@endsection
