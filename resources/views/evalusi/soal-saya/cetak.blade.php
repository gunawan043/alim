@extends('layouts.master')
@section('title', 'Cetak Naskah Soal')

@section('css')
    <style>
        .cetak-layout { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 1rem; align-items: start; }
        @media (max-width: 1199.98px) { .cetak-layout { grid-template-columns: 1fr; } }

        .cetak-panel { position: sticky; top: 82px; max-height: calc(100vh - 100px); overflow-y: auto; }
        @media (max-width: 1199.98px) { .cetak-panel { position: static; max-height: none; } }

        .cetak-preview { width: 100%; height: 78vh; border: 1px solid var(--bs-border-color); border-radius: .5rem; background: #fff; }
        .cetak-field label { font-size: .78rem; font-weight: 600; }
        .cetak-group > summary {
            cursor: pointer;
            font-size: .8rem;
            font-weight: 600;
            padding: .45rem 0;
            list-style: none;
            display: flex;
            align-items: center;
            gap: .35rem;
            color: var(--bs-body-color);
        }
        .cetak-group > summary::-webkit-details-marker { display: none; }
        .cetak-group > summary::before { content: "▸"; font-size: .7rem; color: var(--bs-secondary-color); }
        .cetak-group[open] > summary::before { content: "▾"; }
        .cetak-group + .cetak-group { border-top: 1px solid var(--bs-border-color); }
    </style>
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $settings = $settings ?? [];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}">Soal Saya</a> @endslot
        @slot('title') Cetak Naskah Soal @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="ri-information-line fs-5 mt-1"></i>
        <div class="small mb-0">
            <strong>Soal harian bisa langsung dicetak</strong> — tanpa paket soal dan tanpa validasi.
            Naskah memakai format resmi yang sama dengan STS/SAS: kop sekolah, tabel identitas,
            penanaman karakter, dan pilihan jawaban sejajar.
            Paket &amp; validasi hanya diperlukan untuk <strong>STS/SAS</strong>.
        </div>
    </div>

    @if(count($blocked) > 0)
        <div class="alert alert-warning">
            <strong>{{ count($blocked) }} soal STS/SAS belum tervalidasi</strong> dan tidak ikut tercetak:
            <ul class="mb-0 mt-1 small">
                @foreach(array_slice($blocked, 0, 5) as $teks)<li>{{ $teks }}…</li>@endforeach
            </ul>
            <div class="small mt-1">Soal harian tidak memerlukan validasi. Bila soal ini sebenarnya harian, ubah jenis asesmennya.</div>
        </div>
    @endif

    <div class="cetak-layout">
        {{-- PANEL PENGATURAN --}}
        <div class="cetak-panel">
            <div class="card">
                <div class="card-header py-2"><h6 class="mb-0">Pengaturan naskah</h6></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('user.soal-saya.cetak', ['userId' => $userId]) }}">
                        @php
                            // Sumber soal & filter harus ikut tersimpan saat "Terapkan".
                            foreach (['bank_id', 'subject_id', 'jenis_asesmen'] as $keep) {
                                if (request()->filled($keep)) {
                                    echo '<input type="hidden" name="'.$keep.'" value="'.e(request($keep)).'">';
                                }
                            }
                        @endphp

                        <div class="cetak-field mb-2">
                            <label class="form-label mb-1">Sumber soal</label>
                            <select name="bank_id" class="form-select form-select-sm">
                                <option value="">Semua bank soal yang saya akses</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" @selected((string) request('bank_id') === (string) $bank->id)>
                                        {{ $bank->nama }} ({{ $bank->soal_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ── Judul & tahun ajaran ──────────────────────────────── --}}
                        <details class="cetak-group" open>
                            <summary>Judul &amp; tahun ajaran</summary>
                            <div class="pb-2">
                                <div class="cetak-field mb-2">
                                    <label class="form-label mb-1">Judul naskah</label>
                                    <input type="text" name="judul" class="form-control form-control-sm"
                                           value="{{ $settings['judul'] }}" placeholder="NASKAH SOAL">
                                </div>
                                <div class="row g-2">
                                    <div class="col-7 cetak-field">
                                        <label class="form-label mb-1">Ukuran font</label>
                                        <input type="number" name="judul_size" class="form-control form-control-sm"
                                               min="8" max="24" value="{{ (int) $settings['judul_size'] }}">
                                    </div>
                                    <div class="col-5 cetak-field">
                                        <label class="form-label mb-1">Jarak baris</label>
                                        <input type="number" name="judul_line_height" class="form-control form-control-sm"
                                               min="1" max="2.5" step="0.05" value="{{ $settings['judul_line_height'] }}">
                                    </div>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-7 cetak-field">
                                        <label class="form-label mb-1">Rata</label>
                                        <select name="judul_align" class="form-select form-select-sm">
                                            @foreach(['center' => 'Tengah', 'left' => 'Kiri', 'right' => 'Kanan'] as $value => $label)
                                                <option value="{{ $value }}" @selected($settings['judul_align'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5 d-flex align-items-end">
                                        <div class="form-check form-switch mb-1">
                                            <input type="checkbox" class="form-check-input" name="judul_bold" value="1" id="judulBold" @checked($settings['judul_bold'])>
                                            <label class="form-check-label small" for="judulBold">Tebal</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" class="form-check-input" name="show_tahun_ajaran" value="1" id="showTahun" @checked($settings['show_tahun_ajaran'])>
                                    <label class="form-check-label small" for="showTahun">Tampilkan baris tahun ajaran</label>
                                </div>
                                <div class="cetak-field mt-1">
                                    <label class="form-label mb-1">Isi tahun ajaran <span class="text-muted fw-normal">(kosong = otomatis)</span></label>
                                    <input type="text" name="tahun_ajaran" class="form-control form-control-sm"
                                           value="{{ $settings['tahun_ajaran'] }}" placeholder="2026/2027">
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Ukuran font</label>
                                        <input type="number" name="tahun_size" class="form-control form-control-sm"
                                               min="8" max="24" value="{{ (int) $settings['tahun_size'] }}">
                                    </div>
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Jarak baris</label>
                                        <input type="number" name="tahun_line_height" class="form-control form-control-sm"
                                               min="1" max="2.5" step="0.05" value="{{ $settings['tahun_line_height'] }}">
                                    </div>
                                </div>
                                <div class="form-check form-switch mt-1">
                                    <input type="checkbox" class="form-check-input" name="tahun_bold" value="1" id="tahunBold" @checked($settings['tahun_bold'])>
                                    <label class="form-check-label small" for="tahunBold">Tebal</label>
                                </div>
                            </div>
                        </details>

                        {{-- ── Tabel keterangan (identitas) ──────────────────────── --}}
                        <details class="cetak-group">
                            <summary>Tabel keterangan</summary>
                            <div class="pb-2">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="show_identitas" value="1" id="showIdentitas" @checked($settings['show_identitas'])>
                                    <label class="form-check-label small" for="showIdentitas">Tampilkan tabel keterangan</label>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Jumlah baris</label>
                                        <select name="identitas_rows" class="form-select form-select-sm">
                                            <option value="1" @selected((int) $settings['identitas_rows'] === 1)>1 baris (mapel &amp; kelas)</option>
                                            <option value="2" @selected((int) $settings['identitas_rows'] === 2)>2 baris (+ guru &amp; peserta)</option>
                                        </select>
                                    </div>
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Ukuran font</label>
                                        <input type="number" name="identitas_size" class="form-control form-control-sm"
                                               min="8" max="16" value="{{ (int) $settings['identitas_size'] }}">
                                    </div>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Padding (mm)</label>
                                        <input type="number" name="identitas_padding" class="form-control form-control-sm"
                                               min="0" max="4" step="0.1" value="{{ $settings['identitas_padding'] }}">
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <div class="form-check form-switch mb-1">
                                            <input type="checkbox" class="form-check-input" name="identitas_border" value="1" id="identitasBorder" @checked($settings['identitas_border'])>
                                            <label class="form-check-label small" for="identitasBorder">Garis tabel</label>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-2">
                                <div class="small text-muted mb-1">Label baris</div>
                                <div class="row g-2">
                                    @foreach(['mapel' => 'Mapel', 'kelas' => 'Kelas', 'guru' => 'Guru', 'peserta' => 'Peserta'] as $field => $label)
                                        <div class="col-6 cetak-field">
                                            <input type="text" name="identitas_label_{{ $field }}" class="form-control form-control-sm"
                                                   value="{{ $settings['identitas_label_'.$field] }}" placeholder="{{ $label }}">
                                        </div>
                                    @endforeach
                                </div>

                                <div class="small text-muted mt-2 mb-1">Isi tetap (opsional, kosong = otomatis dari soal)</div>
                                <div class="row g-2">
                                    @foreach(['mapel' => 'Mata pelajaran', 'kelas' => 'Kelas', 'guru' => 'Nama guru'] as $field => $label)
                                        <div class="col-12 cetak-field">
                                            <input type="text" name="identitas_isi_{{ $field }}" class="form-control form-control-sm"
                                                   value="{{ $settings['identitas_isi_'.$field] }}" placeholder="{{ $label }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </details>

                        {{-- ── Penanaman karakter ────────────────────────────────── --}}
                        <details class="cetak-group">
                            <summary>Penanaman karakter</summary>
                            <div class="pb-2">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="show_karakter" value="1" id="showKarakter" @checked($settings['show_karakter'])>
                                    <label class="form-check-label small" for="showKarakter">Tampilkan penanaman karakter</label>
                                </div>
                                <div class="cetak-field mt-1">
                                    <label class="form-label mb-1">Judul</label>
                                    <input type="text" name="karakter_title" class="form-control form-control-sm"
                                           value="{{ $settings['karakter_title'] }}">
                                </div>
                                <div class="cetak-field mt-1">
                                    <label class="form-label mb-1">Butir <span class="text-muted fw-normal">(satu butir per baris)</span></label>
                                    <textarea name="karakter_items" class="form-control form-control-sm" rows="4"
                                              placeholder="Kosongkan untuk memakai daftar bawaan sekolah">{{ $settings['karakter_items'] ?: implode("\n", (array) config('ljk.soal.penanaman_karakter', [])) }}</textarea>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Penanda</label>
                                        <select name="karakter_style" class="form-select form-select-sm">
                                            <option value="numbered" @selected($settings['karakter_style'] === 'numbered')>1. 2.</option>
                                            <option value="bullet" @selected($settings['karakter_style'] === 'bullet')>•</option>
                                            <option value="dash" @selected($settings['karakter_style'] === 'dash')>–</option>
                                        </select>
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Font</label>
                                        <input type="number" name="karakter_size" class="form-control form-control-sm"
                                               min="8" max="16" value="{{ (int) $settings['karakter_size'] }}">
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Jarak baris</label>
                                        <input type="number" name="karakter_line_height" class="form-control form-control-sm"
                                               min="1" max="2.5" step="0.05" value="{{ $settings['karakter_line_height'] }}">
                                    </div>
                                </div>
                                <div class="form-check form-switch mt-1">
                                    <input type="checkbox" class="form-check-input" name="karakter_bold" value="1" id="karakterBold" @checked($settings['karakter_bold'])>
                                    <label class="form-check-label small" for="karakterBold">Tebal</label>
                                </div>
                            </div>
                        </details>

                        {{-- ── Keterangan / instruksi soal ──────────────────────── --}}
                        <details class="cetak-group">
                            <summary>Keterangan soal</summary>
                            <div class="pb-2">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="show_instruksi" value="1" id="showInstruksi" @checked($settings['show_instruksi'])>
                                    <label class="form-check-label small" for="showInstruksi">Tampilkan keterangan</label>
                                </div>
                                <div class="cetak-field mt-1">
                                    <label class="form-label mb-1">Isi keterangan <span class="text-muted fw-normal">(kosong = bawaan)</span></label>
                                    <textarea name="instruksi" class="form-control form-control-sm" rows="3"
                                              placeholder="{{ config('ljk.soal.instruksi') }}">{{ $settings['instruksi'] }}</textarea>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Ukuran font</label>
                                        <input type="number" name="instruksi_size" class="form-control form-control-sm"
                                               min="8" max="16" value="{{ (int) $settings['instruksi_size'] }}">
                                    </div>
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Jarak baris</label>
                                        <input type="number" name="instruksi_spacing" class="form-control form-control-sm"
                                               min="1" max="2.5" step="0.05" value="{{ $settings['instruksi_spacing'] }}">
                                    </div>
                                </div>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="instruksi_bold" value="1" id="instruksiBold" @checked($settings['instruksi_bold'])>
                                        <label class="form-check-label small" for="instruksiBold">Tebal</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="instruksi_italic" value="1" id="instruksiItalic" @checked($settings['instruksi_italic'])>
                                        <label class="form-check-label small" for="instruksiItalic">Miring</label>
                                    </div>
                                </div>
                            </div>
                        </details>

                        {{-- ── Soal, spasi & opsi ───────────────────────────────── --}}
                        <details class="cetak-group">
                            <summary>Soal, spasi &amp; opsi</summary>
                            <div class="pb-2">
                                <div class="cetak-field">
                                    <label class="form-label mb-1">Jenis huruf</label>
                                    <select name="font" class="form-select form-select-sm">
                                        @foreach(\App\Services\Evaluasi\PrintSettingsService::FONTS as $value => $label)
                                            <option value="{{ $value }}" @selected($settings['font'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Ukuran font</label>
                                        <input type="number" name="font_size" class="form-control form-control-sm"
                                               min="8" max="16" value="{{ (int) $settings['font_size'] }}">
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Jarak baris</label>
                                        <input type="number" name="line_height" class="form-control form-control-sm"
                                               min="1" max="2.5" step="0.05" value="{{ $settings['line_height'] }}">
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Jarak soal</label>
                                        <input type="number" name="question_gap" class="form-control form-control-sm"
                                               min="0.5" max="4" step="0.05" value="{{ $settings['question_gap'] }}">
                                    </div>
                                </div>
                                <div class="row g-2 mt-0">
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Lekuk opsi (mm)</label>
                                        <input type="number" name="option_indent" class="form-control form-control-sm"
                                               min="0" max="15" value="{{ (int) $settings['option_indent'] }}">
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Nomor mulai</label>
                                        <input type="number" name="start_number" class="form-control form-control-sm"
                                               min="1" max="999" value="{{ (int) $settings['start_number'] }}">
                                    </div>
                                    <div class="col-4 cetak-field">
                                        <label class="form-label mb-1">Kolom opsi</label>
                                        <select name="option_columns" class="form-select form-select-sm">
                                            <option value="1" @selected((int) $settings['option_columns'] === 1)>1</option>
                                            <option value="2" @selected((int) $settings['option_columns'] === 2)>2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </details>

                        {{-- ── Halaman ──────────────────────────────────────────── --}}
                        <details class="cetak-group">
                            <summary>Halaman &amp; kertas</summary>
                            <div class="pb-2">
                                <div class="row g-2">
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Kertas</label>
                                        <select name="paper" class="form-select form-select-sm">
                                            @foreach(\App\Services\Evaluasi\PrintSettingsService::PAPERS as $value => $label)
                                                <option value="{{ $value }}" @selected($settings['paper'] === $value)>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 cetak-field">
                                        <label class="form-label mb-1">Orientasi</label>
                                        <select name="orientation" class="form-select form-select-sm">
                                            <option value="portrait" @selected($settings['orientation'] === 'portrait')>Portrait</option>
                                            <option value="landscape" @selected($settings['orientation'] === 'landscape')>Landscape</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="form-label mt-2 mb-1">Margin (mm)</label>
                                <div class="row g-2">
                                    @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                        <div class="col-6 cetak-field">
                                            <label class="form-label mb-1">{{ $label }}</label>
                                            <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                                                   min="5" max="50" step="1" value="{{ (int) $settings[$field] }}">
                                        </div>
                                    @endforeach
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" class="form-check-input" name="show_kop" value="1" id="showKop" @checked($settings['show_kop'])>
                                    <label class="form-check-label small" for="showKop">Kop sekolah</label>
                                </div>
                            </div>
                        </details>

                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" class="form-check-input" name="show_key" value="1" id="showKey" @checked($settings['show_key'])>
                            <label class="form-check-label small" for="showKey">Sertakan kunci jawaban</label>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-sm btn-soft-primary">
                                <i class="ri-refresh-line me-1"></i>Terapkan &amp; pratinjau
                            </button>
                        </div>
                    </form>

                    <hr class="my-3">
                    <form method="POST" action="{{ route('user.soal-saya.print', ['userId' => $userId]) }}" target="_blank" id="pdfForm">
                        @csrf
                        @php
                            // Judul yang terlihat di panel ikut dikirim apa adanya.
                            $hidden = $settings;
                            $hidden['judul'] = trim((string) request('judul')) !== '' ? (string) request('judul') : $settings['judul'];
                        @endphp
                        @foreach($hidden as $key => $value)
                            @if(is_bool($value))
                                @if($value)<input type="hidden" name="{{ $key }}" value="1">@endif
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <div id="pdfIds"></div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-printer-line me-1"></i>Cetak / simpan PDF
                        </button>
                        <div class="text-muted small mt-1" id="pdfCount">{{ count($selectedIds) }} soal akan dicetak</div>
                    </form>
                </div>
            </div>
        </div>

        {{-- PRATINJAU NASKAH ASLI --}}
        <div class="card">
            <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0">Pratinjau naskah</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary" id="selectedCount">{{ count($questions) }} soal</span>
                    <button type="button" class="btn btn-sm btn-light" id="toggleAll">Pilih/buang semua</button>
                </div>
            </div>
            <div class="card-body">
                @if($questions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ri-file-text-line fs-1 d-block mb-2"></i>
                        <p class="mb-2">Belum ada soal yang dapat dicetak.</p>
                        <a href="{{ route('user.soal-saya.create', ['userId' => $userId]) }}" class="btn btn-sm btn-primary">
                            <i class="ri-add-line me-1"></i>Buat soal
                        </a>
                    </div>
                @else
                    {{-- Pratinjau memakai dokumen PDF asli, bukan tiruan HTML,
                         agar yang terlihat persis sama dengan hasil cetak. --}}
                    <iframe class="cetak-preview" id="previewFrame"
                            src="{{ route('user.soal-saya.preview', ['userId' => $userId]) }}?{{ http_build_query(request()->query()) }}"
                            title="Pratinjau naskah soal"></iframe>

                    <div class="mt-3">
                        <div class="small text-muted mb-2">Soal yang ikut dicetak:</div>
                        <div class="list-group list-group-flush" style="max-height:200px; overflow:auto">
                            @foreach($questions as $index => $soal)
                                @php
                                    $isBlocked = $soal->requiresValidation() && ! $soal->isValidated();
                                    $isPicked = $selectedIds === [] ? ! $isBlocked : in_array((string) $soal->id, $selectedIds, true);
                                @endphp
                                <label class="list-group-item d-flex gap-2 align-items-start py-2">
                                    <input type="checkbox" class="form-check-input mt-1 q-pick"
                                           value="{{ $soal->id }}"
                                           data-blocked="{{ $isBlocked ? '1' : '0' }}"
                                           @checked($isPicked)>
                                    <span class="small">
                                        <strong>{{ $index + 1 }}.</strong>
                                        {{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 110) }}
                                        @if($isBlocked)
                                            <span class="badge bg-warning-subtle text-warning ms-1">STS/SAS belum tervalidasi</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            var picks = Array.prototype.slice.call(document.querySelectorAll('.q-pick'));
            var pdfIds = document.getElementById('pdfIds');
            var pdfCount = document.getElementById('pdfCount');
            var selectedCount = document.getElementById('selectedCount');
            var toggleAll = document.getElementById('toggleAll');
            var frame = document.getElementById('previewFrame');
            var previewUrl = frame ? frame.getAttribute('src').split('?')[0] : null;
            var baseQuery = new URLSearchParams(window.location.search);
            var timer = null;

            function refreshPreview() {
                if (!frame || !previewUrl) { return; }
                var params = new URLSearchParams(baseQuery.toString());
                params.delete('ids[]');
                picks.filter(function (p) { return p.checked; }).forEach(function (p) {
                    params.append('ids[]', p.value);
                });
                frame.src = previewUrl + '?' + params.toString();
            }

            function sync(refresh) {
                var chosen = picks.filter(function (p) { return p.checked; });
                pdfIds.innerHTML = '';
                chosen.forEach(function (p) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = p.value;
                    pdfIds.appendChild(input);
                });
                if (pdfCount) { pdfCount.textContent = chosen.length + ' soal akan dicetak'; }
                if (selectedCount) { selectedCount.textContent = chosen.length + ' soal'; }

                if (refresh) {
                    clearTimeout(timer);
                    timer = setTimeout(refreshPreview, 350);
                }
            }

            picks.forEach(function (p) { p.addEventListener('change', function () { sync(true); }); });

            if (toggleAll) {
                toggleAll.addEventListener('click', function () {
                    var allChecked = picks.length > 0 && picks.every(function (p) { return p.checked; });
                    picks.forEach(function (p) {
                        p.checked = p.dataset.blocked === '1' ? false : !allChecked;
                    });
                    sync(true);
                });
            }

            sync(false);
        })();
    </script>
@endsection
