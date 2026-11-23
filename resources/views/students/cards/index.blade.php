@extends('layouts.master')

@section('title', 'Kartu Pelajar')

@section('css')
<style>
    .card-preview-stage { display:flex; flex-wrap:wrap; gap:1.25rem; align-items:flex-start; }
    .student-card-preview {
        position:relative; overflow:hidden; border:1px solid #cbd5e1; border-radius:3mm;
        background:#f8fafc center/100% 100% no-repeat; color:#0f172a;
        box-shadow:0 6px 18px rgba(15,23,42,.12); flex:0 0 auto;
    }
    .student-card-preview.front, .student-card-preview.back { width:var(--card-w,85.6mm); height:var(--card-h,53.98mm); }
    .card-photo-box { position:absolute; left:var(--photo-left,4mm); top:var(--photo-top,10mm); width:var(--photo-w,20mm); height:var(--photo-h,25mm); }
    .card-photo-box img, .card-photo-box .photo-empty { width:100%; height:100%; object-fit:cover; border-radius:1mm; display:block; }
    .card-photo-box .photo-empty { background:#dbeafe; border:1px dashed #94a3b8; display:flex; align-items:center; justify-content:center; color:#64748b; }
    .card-front-qr { position:absolute; left:var(--photo-left,4mm); top:calc(var(--photo-top,10mm) + var(--photo-h,25mm) + 1mm); width:var(--qr-size,14mm); height:var(--qr-size,14mm); }
    .card-front-qr img { width:100%; height:100%; display:block; }
    .card-info-table { position:absolute; left:calc(var(--photo-left,4mm) + var(--photo-w,20mm) + 3mm); right:4mm; top:8mm; border-collapse:collapse; font-family:'Cambria','Caladea',serif; font-size:var(--info-size,7.8pt); line-height:var(--line-height,1.25); }
    .student-card-preview.no-photo .card-info-table { left:4mm; }
    .card-info-table td { padding:0; vertical-align:top; }
    .card-info-table .label { width:21mm; white-space:nowrap; }
    .card-info-table .colon { width:3mm; text-align:center; }
    .card-signature { position:absolute; right:4mm; bottom:3mm; width:35mm; text-align:center; font-family:'Cambria','Caladea',serif; }
    .card-signature img { width:var(--sig-w,22mm); height:var(--sig-h,12mm); object-fit:contain; display:block; margin:0 auto; }
    .card-signature .sig-empty { width:var(--sig-w,22mm); height:var(--sig-h,12mm); border-bottom:1px solid #94a3b8; margin:0 auto; }
    .card-signature .sig-name { font-size:calc(var(--info-size,7.8pt) - .5pt); font-weight:700; margin-top:.5mm; }
    .card-signature .sig-title { font-size:calc(var(--info-size,7.8pt) - 1pt); }
    .card-back-note { position:absolute; left:5mm; right:5mm; top:5mm; font-family:'Cambria','Caladea',serif; font-size:var(--back-size,7.5pt); line-height:var(--back-line-height,1.35); }
    .card-back-qr { position:absolute; left:var(--back-qr-left,60mm); top:var(--back-qr-top,24mm); width:var(--back-qr-size,22mm); height:var(--back-qr-size,22mm); }
    .card-back-qr img { width:100%; height:100%; display:block; }
    .card-preview-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:.35rem; }
    .field-check { font-size:.8rem; }
    @media (max-width: 767.98px) { .student-card-preview { max-width:100%; } }
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
    $infoLeft = $showFront['show_photo'] ? ($showFront['photo_left'] + $showFront['photo_width'] + 3) : 4;
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

<div class="card mb-3">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title mb-0">Kartu Pelajar {{ $school->name }}</h5>
            <p class="text-muted mb-0">Format kartu berlaku untuk seluruh rombel di sekolah ini.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($selected)
                <a href="{{ route('user.students.cards.print', ['userId' => $userId, 'student_id' => $selected->id]) }}" class="btn btn-danger btn-sm">
                    <i class="ri-file-pdf-line me-1"></i>Cetak PDF Santri Ini
                </a>
            @endif
            @if($students->isNotEmpty())
                <a href="{{ route('user.students.cards.print', ['userId' => $userId] + request()->only(['q', 'study_group_id'])) }}" class="btn btn-outline-danger btn-sm">
                    <i class="ri-file-pdf-line me-1"></i>Cetak PDF Hasil Pencarian
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-7"><input type="text" name="q" class="form-control" placeholder="Cari nama, NIS, atau NISN..." value="{{ request('q') }}"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Cari</button></div>
            <div class="col-md-2"><a href="{{ route('user.students.cards.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a></div>
        </form>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Nama</th><th>NIS / NISN</th><th>Kelas</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        <tr class="{{ $selected?->id === $student->id ? 'table-primary' : '' }}">
                            <td class="fw-semibold">{{ $student->name }}</td>
                            <td class="small text-muted">{{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}</td>
                            <td class="small">{{ $student->currentClassHistory?->studyGroup ? trim(($student->currentClassHistory->studyGroup->gradeLevel?->name ?? '').' '.$student->currentClassHistory->studyGroup->name) : '-' }}</td>
                            <td class="text-end"><a href="{{ route('user.students.cards.index', ['userId' => $userId, 'q' => request('q'), 'student_id' => $student->id]) }}" class="btn btn-sm btn-soft-primary">Pilih</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Santri tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card border">
                    <div class="card-header bg-light"><h6 class="mb-0">Preview Kartu</h6></div>
                    <div class="card-body">
                        <div class="card-preview-stage">
                            <div>
                                <div class="card-preview-label">Depan</div>
                                <div class="student-card-preview front {{ $showFront['show_photo'] ? '' : 'no-photo' }}" id="cardFront"
                                     style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;--info-size:{{ $showFront['font_size'] }}pt;--line-height:{{ $showFront['line_height'] }};--photo-w:{{ $showFront['photo_width'] }}mm;--photo-h:{{ $showFront['photo_height'] }}mm;--photo-left:{{ $showFront['photo_left'] }}mm;--photo-top:{{ $showFront['photo_top'] }}mm;--qr-size:{{ $showFront['qr_size'] }}mm;--sig-w:{{ $showFront['signature_width'] }}mm;--sig-h:{{ $showFront['signature_height'] }}mm;{{ $frontBackground ? "background-image:url('".$frontBackground."');" : '' }}">
                                    <div class="card-photo-box" data-preview-side="front" data-preview-field="show_photo" style="{{ $showFront['show_photo'] ? '' : 'display:none' }}">
                                        @if($selected?->photo_path)
                                            <img src="{{ asset('storage/'.$selected->photo_path) }}" alt="Foto {{ $selectedName }}">
                                        @else
                                            <div class="photo-empty"><i class="ri-user-line"></i></div>
                                        @endif
                                    </div>
                                    <div class="card-front-qr" data-preview-side="front" data-preview-field="show_front_qr" style="{{ $showFront['show_front_qr'] ? '' : 'display:none' }}">
                                        @if($frontQr)<img src="{{ $frontQr }}" alt="QR NIS">@endif
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
                                        @if($showFront['show_validity'])
                                            <tr data-preview-side="front" data-preview-field="show_validity"><td class="label">Masa Berlaku</td><td class="colon">:</td><td>{{ $validityText }}</td></tr>
                                        @endif
                                    </table>
                                    <div class="card-signature" data-preview-side="front" data-preview-field="show_signature" style="{{ $showFront['show_signature'] ? '' : 'display:none' }}">
                                        @if($signature)
                                            <img src="{{ $signature }}" alt="TTD">
                                        @else
                                            <div class="sig-empty"></div>
                                        @endif
                                        @if($showFront['show_signature_name'])<div class="sig-name" data-preview-side="front" data-preview-field="show_signature_name">{{ $signatureName }}</div>@endif
                                        @if($showFront['show_signature_title'])<div class="sig-title" data-preview-side="front" data-preview-field="show_signature_title">{{ $signatureTitle }}</div>@endif
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="card-preview-label">Belakang</div>
                                <div class="student-card-preview back" id="cardBack"
                                     style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;--back-size:{{ $showBack['font_size'] }}pt;--back-line-height:{{ $showBack['line_height'] }};--back-qr-left:{{ $showBack['back_qr_left'] }}mm;--back-qr-top:{{ $showBack['back_qr_top'] }}mm;--back-qr-size:{{ $showBack['back_qr_size'] }}mm;{{ $backBackground ? "background-image:url('".$backBackground."');" : '' }}">
                                    <div class="card-back-note" data-preview-side="back" data-preview-field="show_note" style="{{ $showBack['show_note'] ? '' : 'display:none' }}">Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.</div>
                                    <div class="card-back-qr" data-preview-side="back" data-preview-field="show_back_qr" style="{{ $showBack['show_back_qr'] ? '' : 'display:none' }}">
                                        @if($backQr)<img src="{{ $backQr }}" alt="QR Belakang">@endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted mt-3">Preview HTML/CSS responsif. PDF dibuat saat tombol cetak diklik.</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <form method="POST" action="{{ route('user.students.cards.settings', ['userId' => $userId]) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="selected_student_id" value="{{ $selected?->id }}">

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Background &amp; TTD</h6></div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small">Background Depan</label><input type="file" name="front_background" id="frontBackgroundInput" class="form-control form-control-sm" accept="image/*"></div>
                                <div class="col-6"><label class="form-label small">Background Belakang</label><input type="file" name="back_background" id="backBackgroundInput" class="form-control form-control-sm" accept="image/*"></div>
                                <div class="col-6"><label class="form-label small">Gambar TTD</label><input type="file" name="signature" id="signatureInput" class="form-control form-control-sm" accept="image/*"></div>
                                <div class="col-3"><label class="form-label small">Lebar (mm)</label><input type="number" step="0.01" name="card_width_mm" id="cardWidth" class="form-control form-control-sm" value="{{ $setting->card_width_mm }}"></div>
                                <div class="col-3"><label class="form-label small">Tinggi (mm)</label><input type="number" step="0.01" name="card_height_mm" id="cardHeight" class="form-control form-control-sm" value="{{ $setting->card_height_mm }}"></div>
                                <div class="col-6"><label class="form-label small">Nama Penanda Tangan</label><input type="text" name="signature_name" class="form-control form-control-sm" value="{{ $setting->signature_name ?: $signatureName }}"></div>
                                <div class="col-6"><label class="form-label small">Jabatan</label><input type="text" name="signature_title" class="form-control form-control-sm" value="{{ $setting->signature_title ?: $signatureTitle }}"></div>
                                <div class="col-12"><label class="form-label small">Masa Berlaku</label><input type="text" name="validity_text" class="form-control form-control-sm" value="{{ $setting->validity_text ?: $validityText }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Informasi Depan</h6></div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                @foreach(['show_photo' => 'Foto', 'show_front_qr' => 'QR Bawah Foto', 'show_name' => 'Nama', 'show_nis' => 'NIS', 'show_nisn' => 'NISN', 'show_ttl' => 'TTL', 'show_address' => 'Alamat', 'show_class' => 'Kelas', 'show_validity' => 'Masa Berlaku', 'show_signature' => 'TTD', 'show_signature_name' => 'Nama Penanda Tangan', 'show_signature_title' => 'Jabatan Penanda Tangan'] as $key => $label)
                                    <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[front][{{ $key }}]" value="1" data-side="front" data-field="{{ $key }}" {{ $showFront[$key] ? 'checked' : '' }}> {{ $label }}</label>
                                @endforeach
                            </div>
                            <div class="row g-2">
                                <div class="col-4"><label class="form-label small">Font Info</label><input type="number" step="0.1" name="layout[front][font_size]" class="form-control form-control-sm preview-number" data-side="front" data-var="info-size" value="{{ $showFront['font_size'] }}"></div>
                                <div class="col-4"><label class="form-label small">Jarak Baris</label><input type="number" step="0.05" name="layout[front][line_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="line-height" value="{{ $showFront['line_height'] }}"></div>
                                <div class="col-4"><label class="form-label small">Ukuran QR (mm)</label><input type="number" step="0.5" name="layout[front][qr_size]" class="form-control form-control-sm preview-number" data-side="front" data-var="qr-size" value="{{ $showFront['qr_size'] }}"></div>
                                <div class="col-3"><label class="form-label small">Lebar Foto</label><input type="number" step="0.5" name="layout[front][photo_width]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-w" value="{{ $showFront['photo_width'] }}"></div>
                                <div class="col-3"><label class="form-label small">Tinggi Foto</label><input type="number" step="0.5" name="layout[front][photo_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-h" value="{{ $showFront['photo_height'] }}"></div>
                                <div class="col-3"><label class="form-label small">Foto Dari Kiri</label><input type="number" step="0.5" name="layout[front][photo_left]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-left" value="{{ $showFront['photo_left'] }}"></div>
                                <div class="col-3"><label class="form-label small">Foto Dari Atas</label><input type="number" step="0.5" name="layout[front][photo_top]" class="form-control form-control-sm preview-number" data-side="front" data-var="photo-top" value="{{ $showFront['photo_top'] }}"></div>
                                <div class="col-6"><label class="form-label small">Lebar TTD</label><input type="number" step="0.5" name="layout[front][signature_width]" class="form-control form-control-sm preview-number" data-side="front" data-var="sig-w" value="{{ $showFront['signature_width'] }}"></div>
                                <div class="col-6"><label class="form-label small">Tinggi TTD</label><input type="number" step="0.5" name="layout[front][signature_height]" class="form-control form-control-sm preview-number" data-side="front" data-var="sig-h" value="{{ $showFront['signature_height'] }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Informasi Belakang</h6></div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[back][show_note]" value="1" data-side="back" data-field="show_note" {{ $showBack['show_note'] ? 'checked' : '' }}> Catatan</label>
                                <label class="form-check field-check"><input type="checkbox" class="form-check-input preview-toggle" name="layout[back][show_back_qr]" value="1" data-side="back" data-field="show_back_qr" {{ $showBack['show_back_qr'] ? 'checked' : '' }}> QR Belakang</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small">Isi QR</label><select name="layout[back][back_qr_source]" class="form-select form-select-sm"><option value="nis" {{ $showBack['back_qr_source'] === 'nis' ? 'selected' : '' }}>NIS</option><option value="nisn" {{ $showBack['back_qr_source'] === 'nisn' ? 'selected' : '' }}>NISN</option><option value="both" {{ $showBack['back_qr_source'] === 'both' ? 'selected' : '' }}>NIS + NISN</option></select></div>
                                <div class="col-6"><label class="form-label small">Ukuran QR (mm)</label><input type="number" step="0.5" name="layout[back][back_qr_size]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-size" value="{{ $showBack['back_qr_size'] }}"></div>
                                <div class="col-6"><label class="form-label small">QR Dari Kiri</label><input type="number" step="0.5" name="layout[back][back_qr_left]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-left" value="{{ $showBack['back_qr_left'] }}"></div>
                                <div class="col-6"><label class="form-label small">QR Dari Atas</label><input type="number" step="0.5" name="layout[back][back_qr_top]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-qr-top" value="{{ $showBack['back_qr_top'] }}"></div>
                                <div class="col-6"><label class="form-label small">Font</label><input type="number" step="0.1" name="layout[back][font_size]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-size" value="{{ $showBack['font_size'] }}"></div>
                                <div class="col-6"><label class="form-label small">Jarak Baris</label><input type="number" step="0.05" name="layout[back][line_height]" class="form-control form-control-sm preview-number" data-side="back" data-var="back-line-height" value="{{ $showBack['line_height'] }}"></div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="ri-save-line me-1"></i>Simpan Pengaturan Kartu</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const cardFront = document.getElementById('cardFront');
    const cardBack = document.getElementById('cardBack');
    const mmVars = ['photo-w', 'photo-h', 'photo-left', 'photo-top', 'qr-size', 'sig-w', 'sig-h', 'back-qr-left', 'back-qr-top', 'back-qr-size'];

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
        if (wrap) {
            const img = wrap.querySelector('img, .sig-empty');
            if (img) img.outerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="TTD">';
        }
    });
</script>
@endsection
