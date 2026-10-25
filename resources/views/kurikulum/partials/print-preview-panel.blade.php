@php
    $printVersion = $printVersion ?? 'pondok';
    $layout = $layout ?? [];
    $versionUrls = $versionUrls ?? [];
    $canManagePrint = $canManagePrint ?? false;
@endphp

<div class="card mb-3">
    <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-1"><i class="ri-eye-line text-primary me-1"></i>Preview &amp; Cetak</h5>
            <p class="text-muted mb-0 small">Pilih format pemerintah atau pondok, lalu atur margin dan font langsung di sini.</p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            @foreach(['pemerintah' => 'Pemerintah', 'pondok' => 'Pondok'] as $versionKey => $versionLabel)
                <a href="{{ $versionUrls[$versionKey] ?? '#' }}"
                   class="btn btn-sm {{ $printVersion === $versionKey ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $versionLabel }}
                </a>
            @endforeach
            <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-soft-success">
                <i class="ri-external-link-line me-1"></i>Buka PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="border rounded overflow-hidden bg-light" style="height:520px;">
                    <iframe src="{{ $previewUrl }}" title="Preview cetak"
                            style="width:100%;height:100%;border:0;background:#fff;" loading="lazy"></iframe>
                </div>
        <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
            <span><i class="ri-layout-line me-1"></i>Margin {{ $layout['margin_top'] ?? 0 }}/{{ $layout['margin_right'] ?? 0 }}/{{ $layout['margin_bottom'] ?? 0 }}/{{ $layout['margin_left'] ?? 0 }} mm</span>
            <span><i class="ri-font-size-2 me-1"></i>Font {{ $layout['font_size'] ?? 11 }} pt</span>
            <span><i class="ri-file-paper-2-line me-1"></i>{{ strtoupper($layout['paper_size'] ?? 'A4') }}</span>
        </div>
        <div class="mt-3">
                @if($canManagePrint)
                    <form method="POST" action="{{ $layoutRoute }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="version" value="{{ $printVersion }}">

                        <h6 class="small fw-semibold mb-2">Margin (mm)</h6>
                        <div class="row g-2 mb-3">
                            @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control form-control-sm" min="0" max="50" step="0.5" required value="{{ old($field, $layout[$field] ?? 10) }}">
                                </div>
                            @endforeach
                        </div>

                        <h6 class="small fw-semibold mb-2">Font &amp; Baris</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small mb-1">Font isi</label>
                                <input type="number" name="font_size" class="form-control form-control-sm" min="6" max="24" step="0.5" required value="{{ old('font_size', $layout['font_size'] ?? 11) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Font judul</label>
                                <input type="number" name="title_font_size" class="form-control form-control-sm" min="8" max="30" step="0.5" required value="{{ old('title_font_size', $layout['title_font_size'] ?? 13) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Font tabel</label>
                                <input type="number" name="table_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('table_font_size', $layout['table_font_size'] ?? 10) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Tinggi baris</label>
                                <input type="number" name="line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ old('line_height', $layout['line_height'] ?? 1.25) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Kertas</label>
                            <select name="paper_size" class="form-select form-select-sm" required>
                                <option value="a4" @selected(($layout['paper_size'] ?? 'a4') === 'a4')>A4</option>
                                <option value="folio" @selected(($layout['paper_size'] ?? 'a4') === 'folio')>F4 / Folio</option>
                                <option value="letter" @selected(($layout['paper_size'] ?? 'a4') === 'letter')>Letter</option>
                            </select>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="show_kop" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="show_kop" name="show_kop" value="1" @checked($layout['show_kop'] ?? true)>
                            <label class="form-check-label small" for="show_kop">Tampilkan kop sekolah</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="ri-save-3-line me-1"></i>Simpan Tata Letak</button>
                    </form>
                @else
                    <div class="alert alert-light border mb-0 small">
                        Pengaturan tata letak hanya dapat diubah oleh penyusun atau tim kurikulum.
                    </div>
                @endif
        </div>
    </div>
</div>
