@extends('layouts.master')

@section('title', 'Kartu Pelajar')

@section('css')
<style>
    .card-preview-stage { display:flex; flex-wrap:wrap; gap:1.25rem; align-items:flex-start; }
    .student-card-preview {
        position:relative; overflow:hidden; border:1px solid #cbd5e1; border-radius:3mm;
        background:#eef2f7 center/100% 100% no-repeat; color:#0f172a;
        box-shadow:0 6px 18px rgba(15,23,42,.12); flex:0 0 auto;
    }
    .student-card-preview.front { width:var(--card-w,85.6mm); height:var(--card-h,53.98mm); }
    .student-card-preview.back { width:var(--card-w,85.6mm); height:var(--card-h,53.98mm); }
    .student-card-preview .card-el { position:absolute; overflow:hidden; line-height:1.15; font-family:'Cambria','Caladea',serif; }
    .student-card-preview .card-photo { width:100%; height:100%; object-fit:cover; border-radius:1.5mm; background:#dbeafe; display:block; }
    .student-card-preview .photo-empty { width:100%; height:100%; background:#dbeafe; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:1.4rem; }
    .student-card-preview .signature-img { width:100%; height:100%; object-fit:contain; }
    .student-card-preview .signature-empty { width:100%; height:100%; border-bottom:.3mm solid #94a3b8; display:flex; align-items:flex-end; justify-content:center; font-size:6pt; color:#64748b; }
    .card-preview-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:.35rem; }
    .layout-table th, .layout-table td { font-size:.75rem; vertical-align:middle; }
    .layout-table input, .layout-table select { font-size:.75rem; padding:.2rem .35rem; }
    .preview-bg-label { font-size:.65rem; color:#64748b; }
    @media (max-width: 767.98px) { .student-card-preview { max-width:100%; } }
</style>
@endsection

@section('content')
@php
    $fieldLabels = [
        'school' => 'Nama Sekolah', 'photo' => 'Foto', 'name' => 'Nama Santri',
        'nis' => 'NIS', 'nisn' => 'NISN', 'class' => 'Kelas', 'validity' => 'Masa Berlaku',
        'note' => 'Catatan', 'signature' => 'TTD', 'signature_name' => 'Nama Penanda Tangan',
        'signature_title' => 'Jabatan Penanda Tangan',
    ];
    $selectedName = $selected?->name ?? 'Nama Santri';
    $selectedNis = $selected?->nis ?: '-';
    $selectedNisn = $selected?->nisn ?: '-';
    $selectedClass = $selected?->currentClassHistory?->studyGroup;
    $selectedClassText = $selectedClass ? trim(($selectedClass->gradeLevel?->name ?? '').' '.$selectedClass->name) : '-';
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
            <p class="text-muted mb-0">Atur background, tata letak, TTD, cari santri, lalu cetak PDF.</p>
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
            <div class="col-md-7">
                <input type="text" name="q" class="form-control" placeholder="Cari nama, NIS, atau NISN..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Cari</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('user.students.cards.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
            </div>
        </form>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Nama</th><th>NIS / NISN</th><th>Kelas</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr class="{{ $selected?->id === $student->id ? 'table-primary' : '' }}">
                            <td class="fw-semibold">{{ $student->name }}</td>
                            <td class="small text-muted">{{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}</td>
                            <td class="small">{{ $student->currentClassHistory?->studyGroup ? trim(($student->currentClassHistory->studyGroup->gradeLevel?->name ?? '').' '.$student->currentClassHistory->studyGroup->name) : '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.students.cards.index', ['userId' => $userId, 'q' => request('q'), 'student_id' => $student->id]) }}" class="btn btn-sm btn-soft-primary">Pilih</a>
                            </td>
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
                                <div class="student-card-preview front" id="cardFront"
                                     style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;{{ $frontBackground ? "background-image:url('".$frontBackground."');" : '' }}">
                                    @foreach($layout['front'] as $field => $cfg)
                                        @php $style = 'left:'.$cfg['x'].'mm;top:'.$cfg['y'].'mm;width:'.$cfg['w'].'mm;height:'.$cfg['h'].'mm;'; @endphp
                                        @if($field === 'photo')
                                            <div class="card-el" data-card-side="front" data-card-field="photo" style="{{ $style }}">
                                                @if($selected?->photo_path)
                                                    <img class="card-photo" src="{{ asset('storage/'.$selected->photo_path) }}" alt="Foto {{ $selectedName }}">
                                                @else
                                                    <div class="photo-empty"><i class="ri-user-line"></i></div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="card-el" data-card-side="front" data-card-field="{{ $field }}"
                                                 style="{{ $style }}font-size:{{ $cfg['size'] }}pt;text-align:{{ $cfg['align'] }};font-weight:{{ ! empty($cfg['bold']) ? '700' : '400' }};">
                                                @if($field === 'school'){{ $school->name }}
                                                @elseif($field === 'name'){{ $selectedName }}
                                                @elseif($field === 'nis')NIS: {{ $selectedNis }}
                                                @elseif($field === 'nisn')NISN: {{ $selectedNisn }}
                                                @elseif($field === 'class')Kelas: {{ $selectedClassText }}
                                                @elseif($field === 'validity'){{ $validityText }}
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <div class="card-preview-label">Belakang</div>
                                <div class="student-card-preview back" id="cardBack"
                                     style="--card-w:{{ $setting->card_width_mm }}mm;--card-h:{{ $setting->card_height_mm }}mm;{{ $backBackground ? "background-image:url('".$backBackground."');" : '' }}">
                                    @foreach($layout['back'] as $field => $cfg)
                                        @php $style = 'left:'.$cfg['x'].'mm;top:'.$cfg['y'].'mm;width:'.$cfg['w'].'mm;height:'.$cfg['h'].'mm;'; @endphp
                                        @if($field === 'signature')
                                            <div class="card-el" data-card-side="back" data-card-field="signature" style="{{ $style }}">
                                                @if($signature)
                                                    <img class="signature-img" src="{{ $signature }}" alt="TTD">
                                                @else
                                                    <div class="signature-empty">TTD</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="card-el" data-card-side="back" data-card-field="{{ $field }}"
                                                 style="{{ $style }}font-size:{{ $cfg['size'] }}pt;text-align:{{ $cfg['align'] }};font-weight:{{ ! empty($cfg['bold']) ? '700' : '400' }};">
                                                @if($field === 'note')Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.
                                                @elseif($field === 'signature_name'){{ $signatureName }}
                                                @elseif($field === 'signature_title'){{ $signatureTitle }}
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted mt-3">
                            Preview menggunakan HTML/CSS agar responsif. File PDF dibuat saat tombol cetak diklik.
                        </div>
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
                                <div class="col-6">
                                    <label class="form-label small">Background Depan</label>
                                    <input type="file" name="front_background" id="frontBackgroundInput" class="form-control form-control-sm" accept="image/*">
                                    @error('front_background')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Background Belakang</label>
                                    <input type="file" name="back_background" id="backBackgroundInput" class="form-control form-control-sm" accept="image/*">
                                    @error('back_background')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Gambar TTD</label>
                                    <input type="file" name="signature" id="signatureInput" class="form-control form-control-sm" accept="image/*">
                                    @error('signature')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-3">
                                    <label class="form-label small">Lebar (mm)</label>
                                    <input type="number" step="0.01" name="card_width_mm" id="cardWidth" class="form-control form-control-sm" value="{{ $setting->card_width_mm }}">
                                </div>
                                <div class="col-3">
                                    <label class="form-label small">Tinggi (mm)</label>
                                    <input type="number" step="0.01" name="card_height_mm" id="cardHeight" class="form-control form-control-sm" value="{{ $setting->card_height_mm }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Nama Penanda Tangan</label>
                                    <input type="text" name="signature_name" class="form-control form-control-sm" value="{{ $setting->signature_name ?: $signatureName }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Jabatan</label>
                                    <input type="text" name="signature_title" class="form-control form-control-sm" value="{{ $setting->signature_title ?: $signatureTitle }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Masa Berlaku</label>
                                    <input type="text" name="validity_text" class="form-control form-control-sm" value="{{ $setting->validity_text ?: $validityText }}">
                                </div>
                            </div>
                            <div class="form-text">Gunakan background dengan rasio yang sama dengan ukuran kartu agar tidak terdistorsi.</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Tata Letak (mm / pt)</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm layout-table mb-0">
                                    <thead class="table-light"><tr><th>Elemen</th><th>X</th><th>Y</th><th>W</th><th>H</th><th>Font</th><th>Align</th><th>Bold</th></tr></thead>
                                    <tbody>
                                        @foreach(['front' => 'Depan', 'back' => 'Belakang'] as $side => $sideLabel)
                                            <tr class="table-light"><td colspan="8" class="fw-semibold">{{ $sideLabel }}</td></tr>
                                            @foreach($layout[$side] as $field => $cfg)
                                                <tr>
                                                    <td class="fw-medium">{{ $fieldLabels[$field] ?? $field }}</td>
                                                    @foreach(['x', 'y', 'w', 'h'] as $prop)
                                                        <td><input type="number" step="0.1" name="layout[{{ $side }}][{{ $field }}][{{ $prop }}]" value="{{ $cfg[$prop] }}" class="form-control form-control-sm layout-input" data-side="{{ $side }}" data-field="{{ $field }}" data-prop="{{ $prop }}"></td>
                                                    @endforeach
                                                    @if(isset($cfg['size']))
                                                        <td><input type="number" step="0.1" name="layout[{{ $side }}][{{ $field }}][size]" value="{{ $cfg['size'] }}" class="form-control form-control-sm layout-input" data-side="{{ $side }}" data-field="{{ $field }}" data-prop="size"></td>
                                                        <td><select name="layout[{{ $side }}][{{ $field }}][align]" class="form-select form-select-sm layout-input" data-side="{{ $side }}" data-field="{{ $field }}" data-prop="align"><option value="left" {{ $cfg['align'] === 'left' ? 'selected' : '' }}>Kiri</option><option value="center" {{ $cfg['align'] === 'center' ? 'selected' : '' }}>Tengah</option><option value="right" {{ $cfg['align'] === 'right' ? 'selected' : '' }}>Kanan</option></select></td>
                                                        <td class="text-center"><input type="checkbox" name="layout[{{ $side }}][{{ $field }}][bold]" value="1" class="form-check-input layout-input" data-side="{{ $side }}" data-field="{{ $field }}" data-prop="bold" {{ ! empty($cfg['bold']) ? 'checked' : '' }}></td>
                                                    @else
                                                        <td colspan="3" class="text-muted small">-</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-save-line me-1"></i>Simpan Pengaturan Kartu
                    </button>
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

    function mm(value) { return Number(value || 0) + 'mm'; }
    function pt(value) { return Number(value || 0) + 'pt'; }

    document.querySelectorAll('.layout-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const side = input.dataset.side;
            const field = input.dataset.field;
            const prop = input.dataset.prop;
            const card = side === 'front' ? cardFront : cardBack;
            const el = card?.querySelector('[data-card-field="' + field + '"]');
            if (! el) return;

            if (['x', 'y', 'w', 'h'].includes(prop)) {
                const map = { x: 'left', y: 'top', w: 'width', h: 'height' };
                el.style[map[prop]] = mm(input.value);
            } else if (prop === 'size') {
                el.style.fontSize = pt(input.value);
            } else if (prop === 'align') {
                el.style.textAlign = input.value;
            } else if (prop === 'bold') {
                el.style.fontWeight = input.checked ? '700' : '400';
            }
        });
    });

    function updateSize() {
        const w = document.getElementById('cardWidth').value + 'mm';
        const h = document.getElementById('cardHeight').value + 'mm';
        [cardFront, cardBack].forEach(function (card) {
            if (card) {
                card.style.width = w;
                card.style.height = h;
            }
        });
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
        const wrap = document.querySelector('[data-card-side="back"][data-card-field="signature"]');
        if (wrap) {
            wrap.innerHTML = '<img class="signature-img" src="' + URL.createObjectURL(file) + '" alt="TTD">';
        }
    });
</script>
@endsection
