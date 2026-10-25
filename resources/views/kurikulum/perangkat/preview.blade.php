@extends('layouts.master')
@section('title', 'Preview Perangkat')
@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}">Perangkat</a> @endslot
        @slot('title') Preview &amp; Tata Letak @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">{{ $perangkat->judul }}</h5>
                <div class="text-muted small">
                    {{ $perangkat->subject?->name ?? '-' }}
                    · {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}
                    · {{ $perangkat->academicYear?->name ?? '-' }}
                    · Semester {{ ucfirst($perangkat->semester) }}
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="badge bg-primary-subtle text-primary">
                        {{ $formatType === 'agama' ? 'Format Agama / Bahasa Arab' : 'Format Umum' }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary">
                        {{ $perangkat->status === 'final' ? 'Final' : 'Draft' }}
                    </span>
                    @if($perangkat->workflow_status === 'approved')
                        <span class="badge bg-success-subtle text-success">Disetujui Rumpun</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $previewUrl }}" target="_blank" class="btn btn-soft-success btn-sm">
                    <i class="ri-external-link-line me-1"></i>Buka PDF
                </a>
                <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="card-title mb-1"><i class="ri-eye-line text-primary me-1"></i>Preview PDF</h6>
                        <p class="text-muted mb-0 small">Preview memakai pengaturan yang tersimpan.</p>
                    </div>
                    <span class="badge bg-info-subtle text-info">
                        {{ strtoupper($layout['paper_size']) }}
                        · {{ $layout['font_size'] }} pt
                        · Line {{ $layout['line_height'] }}
                    </span>
                </div>
                <div class="card-body p-2">
                    <div class="border rounded overflow-hidden bg-light" style="height:760px;">
                        <iframe src="{{ $previewUrl }}" title="Preview perangkat pembelajaran"
                                style="width:100%;height:100%;border:0;background:#fff;" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h6 class="card-title mb-1"><i class="ri-settings-3-line text-primary me-1"></i>Tata Letak Cetak</h6>
                    <p class="text-muted mb-0 small">Atur margin, font, dan kertas untuk perangkat ini.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kurikulum.perangkat.layout', ['userId' => $userId, 'id' => $perangkat->id]) }}">
                        @csrf
                        @method('PUT')

                        <h6 class="small fw-semibold mb-2">Margin (mm)</h6>
                        <div class="row g-2 mb-3">
                            @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                                           min="0" max="50" step="0.5" required value="{{ old($field, $layout[$field]) }}">
                                </div>
                            @endforeach
                        </div>

                        <h6 class="small fw-semibold mb-2">Font &amp; Baris</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small mb-1">Font isi (pt)</label>
                                <input type="number" name="font_size" class="form-control form-control-sm" min="6" max="24" step="0.5" required value="{{ old('font_size', $layout['font_size']) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Font judul (pt)</label>
                                <input type="number" name="title_font_size" class="form-control form-control-sm" min="8" max="30" step="0.5" required value="{{ old('title_font_size', $layout['title_font_size']) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Font tabel (pt)</label>
                                <input type="number" name="table_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('table_font_size', $layout['table_font_size']) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Tinggi baris</label>
                                <input type="number" name="line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ old('line_height', $layout['line_height']) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Ukuran kertas</label>
                            <select name="paper_size" class="form-select form-select-sm" required>
                                <option value="a4" @selected($layout['paper_size'] === 'a4')>A4</option>
                                <option value="folio" @selected($layout['paper_size'] === 'folio')>F4 / Folio</option>
                                <option value="letter" @selected($layout['paper_size'] === 'letter')>Letter</option>
                            </select>
                        </div>

                        <h6 class="small fw-semibold mb-2">Tampilan</h6>
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="show_kop" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="show_kop" name="show_kop" value="1" @checked($layout['show_kop'])>
                            <label class="form-check-label small" for="show_kop">Tampilkan kop sekolah</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="show_stamp" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="show_stamp" name="show_stamp" value="1" @checked($layout['show_stamp'])>
                            <label class="form-check-label small" for="show_stamp">Tampilkan stempel</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="use_digital_signature" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="use_digital_signature" name="use_digital_signature" value="1" @checked($layout['use_digital_signature']) @disabled(! $perangkat->isKspApproved())>
                            <label class="form-check-label small" for="use_digital_signature">Gunakan TTD digital KSP</label>
                        </div>
                        <p class="text-muted small mb-3">
                            @if($perangkat->isKspApproved())
                                Perangkat sudah disetujui KSP. TTD dan stempel digital dapat diaktifkan.
                            @else
                                TTD digital baru dapat diaktifkan setelah perangkat disetujui rumpun dan KSP.
                            @endif
                        </p>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-save-3-line me-1"></i>Simpan Tata Letak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
