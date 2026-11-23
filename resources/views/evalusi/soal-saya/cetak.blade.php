@extends('layouts.master')
@section('title', 'Cetak Naskah Soal')

@section('css')
    <style>
        .cetak-layout { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 1rem; align-items: start; }
        @media (max-width: 991.98px) { .cetak-layout { grid-template-columns: 1fr; } }

        .cetak-panel { position: sticky; top: 82px; }
        @media (max-width: 991.98px) { .cetak-panel { position: static; } }

        .cetak-preview { width: 100%; height: 78vh; border: 1px solid var(--bs-border-color); border-radius: .5rem; background: #fff; }
        .cetak-field label { font-size: .8rem; font-weight: 600; }
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
                        <div class="cetak-field mb-2">
                            <label class="form-label mb-1">Judul naskah</label>
                            <input type="text" name="judul" class="form-control form-control-sm"
                                   value="{{ $settings['judul'] }}" placeholder="NASKAH SOAL">
                        </div>

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

                        <label class="form-label mt-3 mb-1">Margin (mm)</label>
                        <div class="row g-2">
                            @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                <div class="col-6 cetak-field">
                                    <label class="form-label mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                                           min="5" max="50" step="1" value="{{ (int) $settings[$field] }}">
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-2 mt-1">
                            <div class="col-6 cetak-field">
                                <label class="form-label mb-1">Ukuran font</label>
                                <input type="number" name="font_size" class="form-control form-control-sm"
                                       min="8" max="16" value="{{ (int) $settings['font_size'] }}">
                            </div>
                            <div class="col-6 cetak-field">
                                <label class="form-label mb-1">Spasi baris</label>
                                <input type="number" name="line_height" class="form-control form-control-sm"
                                       min="1" max="2.5" step="0.05" value="{{ $settings['line_height'] }}">
                            </div>
                        </div>

                        <div class="cetak-field mt-2">
                            <label class="form-label mb-1">Nomor soal mulai</label>
                            <input type="number" name="start_number" class="form-control form-control-sm"
                                   min="1" max="999" value="{{ (int) $settings['start_number'] }}">
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input type="checkbox" class="form-check-input" name="show_kop" value="1" id="showKop" @checked($settings['show_kop'])>
                            <label class="form-check-label small" for="showKop">Kop sekolah</label>
                        </div>

                        <hr class="my-3">
                        <label class="form-label mb-1">Sumber soal</label>
                        <select name="bank_id" class="form-select form-select-sm">
                            <option value="">Semua bank soal yang saya akses</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" @selected((string) request('bank_id') === (string) $bank->id)>
                                    {{ $bank->nama }} ({{ $bank->soal_count }})
                                </option>
                            @endforeach
                        </select>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-sm btn-soft-primary">
                                <i class="ri-refresh-line me-1"></i>Terapkan &amp; pratinjau
                            </button>
                        </div>
                    </form>

                    <hr class="my-3">
                    <form method="POST" action="{{ route('user.soal-saya.print', ['userId' => $userId]) }}" target="_blank" id="pdfForm">
                        @csrf
                        @foreach($settings as $key => $value)
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
                                @php $isBlocked = $soal->requiresValidation() && ! $soal->isValidated(); @endphp
                                <label class="list-group-item d-flex gap-2 align-items-start py-2">
                                    <input type="checkbox" class="form-check-input mt-1 q-pick"
                                           value="{{ $soal->id }}"
                                           data-blocked="{{ $isBlocked ? '1' : '0' }}"
                                           @checked(in_array($soal->id, $selectedIds, true) || $selectedIds === [] ? ! $isBlocked : ! $isBlocked)>
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

            function sync() {
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
            }

            picks.forEach(function (p) { p.addEventListener('change', sync); });

            if (toggleAll) {
                toggleAll.addEventListener('click', function () {
                    var allChecked = picks.length > 0 && picks.every(function (p) { return p.checked; });
                    picks.forEach(function (p) {
                        p.checked = p.dataset.blocked === '1' ? false : !allChecked;
                    });
                    sync();
                });
            }

            sync();
        })();
    </script>
@endsection
