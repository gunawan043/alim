@extends('layouts.master')
@section('title') Terbitkan LJK @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php $userId = $userId ?? request()->route('userId') ?? Auth::id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('title') Terbitkan LJK @endslot
    @endcomponent

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.ljk.store', ['userId' => $userId]) }}" id="ljk-form">
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-file-list-3-line me-1 text-primary"></i>Sumber Soal &amp; Ujian</h5>
                        <p class="text-muted mb-0 stat-label">
                            Soal diambil langsung dari Paket Soal ALIM — tidak ada bank soal baru.
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Buku Nilai (mapel &amp; rombel) <span class="text-danger">*</span></label>
                                <select name="admin_book_id" id="book-select" class="form-select" required>
                                    <option value="">— pilih buku nilai —</option>
                                    @foreach ($books as $book)
                                        <option value="{{ $book->id }}"
                                                data-subject="{{ $book->subject_id }}"
                                                data-group="{{ $book->study_group_id }}"
                                                data-year="{{ $book->academic_year_id }}"
                                                data-semester="{{ $book->semester }}"
                                                data-school="{{ $book->school_id }}"
                                                {{ old('admin_book_id') === $book->id ? 'selected' : '' }}>
                                            {{ $book->subject?->name }} · {{ $book->studyGroup?->name }} ·
                                            {{ $book->academicYear?->name }} · {{ ucfirst($book->semester) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Menentukan rombel peserta dan tujuan sinkronisasi nilai.</div>
                                @error('admin_book_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Paket Soal (final/published) <span class="text-danger">*</span></label>
                                <select name="paket_soal_id" id="paket-select" class="form-select" required>
                                    <option value="">— pilih paket soal —</option>
                                    @foreach ($pakets as $paket)
                                        <option value="{{ $paket->id }}"
                                                data-subject="{{ $paket->kisiKisi?->subject_id }}"
                                                data-year="{{ $paket->kisiKisi?->academic_year_id }}"
                                                data-semester="{{ $paket->kisiKisi?->semester }}"
                                                {{ old('paket_soal_id') === $paket->id ? 'selected' : '' }}>
                                            {{ $paket->judul }} · {{ $paket->kisiKisi?->subject?->name }} · v{{ $paket->versi }}
                                            ({{ $paket->kode_paket }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Naskah beku (snapshot) — kunci jawaban tidak dicetak di lembar.</div>
                                @error('paket_soal_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-edit-2-line me-1 text-primary"></i>Identitas Ujian</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Judul Ujian <span class="text-danger">*</span></label>
                                <input type="text" name="judul" class="form-control"
                                       value="{{ old('judul', 'STS ') }}" maxlength="150" required>
                                @error('judul')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
                                <select name="jenis_ujian" id="jenis-ujian" class="form-select" required>
                                    @foreach (['sts' => 'STS', 'sas' => 'SAS', 'ulangan_harian' => 'Ulangan Harian', 'try_out' => 'Try Out', 'latihan' => 'Latihan'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('jenis_ujian') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Menentukan kolom nilai tujuan.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bahasa Naskah</label>
                                <select name="bahasa" id="bahasa-naskah" class="form-select">
                                    @foreach (\App\Models\Soal::BAHASA_OPTIONS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('bahasa', 'gabungan') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <strong>Arab</strong> → seluruh lembar RTL, nomor ١، ٢، ٣, pilihan ا، ب، ج، د.
                                    <strong>Latin/Gabungan</strong> → LTR, nomor 1, 2, 3, pilihan A, B, C, D.
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Waktu Mulai</label>
                                <input type="time" name="waktu_mulai" class="form-control" value="{{ old('waktu_mulai') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Waktu Selesai</label>
                                <input type="time" name="waktu_selesai" class="form-control" value="{{ old('waktu_selesai') }}">
                            </div>
                            <div class="col-md-4" id="sumatif-column-wrap">
                                <label class="form-label">Kolom Sumatif Harian tujuan</label>
                                <select name="nilai_target_column" class="form-select">
                                    @foreach ($sumatifColumns as $column)
                                        <option value="{{ $column['id'] }}" @selected(old('nilai_target_column', 's1') === $column['id'])>
                                            {{ $column['label'] ?? $column['id'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Untuk ulangan harian/try out/latihan (STS &amp; SAS otomatis ke nilai murni).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Instruksi tambahan (opsional)</label>
                                <textarea name="instruksi" rows="2" class="form-control" maxlength="2000"
                                          placeholder="mis. kerjakan dengan pulpen hitam, tidak ada pengurangan nilai">{{ old('instruksi') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="canonical_order" value="1"
                                           id="canonical_order" @checked(old('canonical_order'))>
                                    <label class="form-check-label" for="canonical_order">
                                        <strong>Mode Fotokopi</strong> — urutan soal &amp; opsi seragam untuk semua peserta,
                                        sehingga satu <em>lembar master tanpa nama</em> bisa disalin untuk seluruh santri.
                                    </label>
                                </div>
                                <div class="form-text">
                                    Tanpa mode ini, paket yang mengacak soal/opsi akan mencetak urutan berbeda per peserta.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-printer-line me-1 text-primary"></i>Peserta &amp; Lembar</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="ri-information-line me-1"></i>
                            Peserta diambil otomatis dari <strong>rombel aktif</strong> pada buku nilai terpilih.
                            Sistem menerbitkan satu lembar A4 per peserta dengan kotak tanda silang, QR identitas,
                            dan penanda sudut untuk pemindaian kamera HP. Soal uraian/isian tetap dinilai manual.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-scan-2-line me-1"></i> Terbitkan LJK &amp; Buat Lembar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-animate h-100">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-flow-chart me-1 text-primary"></i>Alur Singkat</h5>
                    </div>
                    <div class="card-body">
                        <div class="ljk-guide-step">
                            <span class="step-no">1</span>
                            <span>Pilih <strong>Paket Soal final</strong> yang sudah disetujui &amp; dipublikasikan.</span>
                        </div>
                        <div class="ljk-guide-step">
                            <span class="step-no">2</span>
                            <span>Terbitkan LJK → sistem membuat template, peserta, dan lembar ber-QR.</span>
                        </div>
                        <div class="ljk-guide-step">
                            <span class="step-no">3</span>
                            <span>Cetak PDF (A4) lalu bagikan ke santri.</span>
                        </div>
                        <div class="ljk-guide-step">
                            <span class="step-no">4</span>
                            <span>Santri mengisi dengan <strong>pulpen hitam/biru</strong>, tanda silang (X) di dalam kotak.</span>
                        </div>
                        <div class="ljk-guide-step">
                            <span class="step-no">5</span>
                            <span>Pindai dengan kamera HP → periksa nomor ⚠ → finalkan → sinkron ke nilai.</span>
                        </div>

                        <hr>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="avatar-title bg-success-subtle rounded fs-5" style="width:36px;height:36px;">
                                <i class="ri-shield-check-line text-success"></i>
                            </span>
                            <div>
                                <p class="mb-0 fw-semibold">Aman &amp; dapat diaudit</p>
                                <p class="text-muted mb-0 stat-label">Kunci jawaban tidak pernah dicetak/dikirim ke klien.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-title bg-primary-subtle rounded fs-5" style="width:36px;height:36px;">
                                <i class="ri-links-line text-primary"></i>
                            </span>
                            <div>
                                <p class="mb-0 fw-semibold">Terikat versi paket</p>
                                <p class="text-muted mb-0 stat-label">Lembar tercetak tertelusur ke naskah beku (digest).</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script>
        // Saring paket soal agar sesuai mapel + tahun ajaran + semester buku nilai.
        document.addEventListener('DOMContentLoaded', function () {
            const bookSelect = document.getElementById('book-select');
            const paketSelect = document.getElementById('paket-select');

            function filterPakets() {
                const book = bookSelect.options[bookSelect.selectedIndex];
                const subject = book?.dataset?.subject || '';
                const year = book?.dataset?.year || '';
                const semester = book?.dataset?.semester || '';

                Array.from(paketSelect.options).forEach(function (option, index) {
                    if (index === 0) return;
                    const match = (!subject || option.dataset.subject === subject)
                        && (!year || option.dataset.year === year)
                        && (!semester || option.dataset.semester === semester);
                    option.hidden = !match;
                    option.disabled = !match;
                });

                if (paketSelect.selectedOptions[0]?.disabled) {
                    paketSelect.value = '';
                }
            }

            bookSelect.addEventListener('change', filterPakets);
            filterPakets();

            // Kolom sumatif harian hanya relevan untuk ulangan/try out/latihan.
            const jenisSelect = document.getElementById('jenis-ujian');
            const sumatifWrap = document.getElementById('sumatif-column-wrap');

            function toggleSumatifColumn() {
                const jenis = jenisSelect.value;
                sumatifWrap.style.display = (jenis === 'sts' || jenis === 'sas') ? 'none' : '';
            }

            jenisSelect.addEventListener('change', toggleSumatifColumn);
            toggleSumatifColumn();
        });
    </script>
@endsection
