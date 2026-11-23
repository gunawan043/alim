@extends('layouts.master')
@section('title', 'Buat Soal')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}">Soal Saya</a> @endslot
        @slot('title') Buat Soal @endslot
    @endcomponent

    <x-soal-flow current="bank" :scope="$scope ?? null" :userId="$userId" />

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if(empty($scope['has_assignment']))
        <div class="alert alert-warning d-flex gap-3" role="alert">
            <i class="ri-error-warning-line fs-4 lh-1"></i>
            <div>
                <h6 class="alert-heading mb-1">Belum ada mata pelajaran pada SK aktif Anda</h6>
                <p class="mb-2 small">
                    Soal hanya boleh dibuat untuk mata pelajaran yang diamanahkan SK Pembagian
                    Tugas yang sedang aktif. Minta Waka menambahkan nama Anda ke SK tersebut.
                </p>
                <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-sm btn-primary">
                    <i class="ri-file-list-3-line me-1"></i>Buka halaman SK &amp; Keputusan
                </a>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('user.soal-saya.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">1. Konteks soal</h5>
                        <p class="text-muted mb-0 small">Mapel dan kelas mengikuti SK Pembagian Tugas aktif.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    @forelse($subjects as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('subject_id', $selectedSubjectId) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @empty
                                        <option value="">Belum ada mapel pada SK aktif</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kelas</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">Semua kelas yang saya ajar</option>
                                    @foreach($grades as $g)<option value="{{ $g->id }}" {{ (string) old('grade_level_id') === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Asesmen <span class="text-danger">*</span></label>
                                <select name="jenis_asesmen" class="form-select" required id="jenisAsesmen">
                                    @foreach(\App\Models\Soal::JENIS_ASESMEN_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ old('jenis_asesmen', 'harian') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)<option value="{{ $ay->id }}" {{ (string) old('academic_year_id', $selectedAyId) === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester_text }})</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ old('semester', $semester) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester', $semester) === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipe Soal <span class="text-danger">*</span></label>
                                <select name="tipe_soal" class="form-select" required id="tipeSoal">
                                    @foreach(['pg' => 'Pilihan Ganda', 'bs' => 'Benar/Salah', 'jodoh' => 'Menjodohkan', 'isian' => 'Isian', 'uraian' => 'Uraian'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('tipe_soal', 'pg') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kesulitan</label>
                                <select name="tingkat_kesulitan_estimasi" class="form-select">
                                    @foreach(['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('tingkat_kesulitan_estimasi', 'sedang') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Waktu (menit)</label>
                                <input type="number" name="waktu_estimasi_menit" class="form-control" min="1" max="120" value="{{ old('waktu_estimasi_menit', 2) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TP (opsional)</label>
                                <select name="tp_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($tps as $tp)<option value="{{ $tp->id }}" {{ old('tp_id') === $tp->id ? 'selected' : '' }}>{{ $tp->kode_tp }} — {{ \Illuminate\Support\Str::limit($tp->deskripsi, 70) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bahasa</label>
                                <select name="bahasa" class="form-select">
                                    @foreach(\App\Models\Soal::BAHASA_OPTIONS as $value => $label)<option value="{{ $value }}" {{ old('bahasa', 'latin') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tag</label>
                                <input type="text" name="tags" class="form-control" value="{{ old('tags') }}" placeholder="dipisah koma">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                <textarea name="pertanyaan" class="form-control" rows="4" required>{{ old('pertanyaan') }}</textarea>
                            </div>

                            <div class="col-12" id="optionsWrap">
                                <label class="form-label">Opsi Jawaban</label>
                                <div class="alert alert-info py-2 small">Isi minimal 2 opsi. Pilih kunci jawaban dengan radio di sebelah kiri.</div>
                                @foreach(['A', 'B', 'C', 'D', 'E'] as $label)
                                    @php $oldOption = old('options.'.(ord($label) - 65).'.teks_opsi', ''); @endphp
                                    <div class="input-group mb-2">
                                        <span class="input-group-text" style="width:44px;">{{ $label }}</span>
                                        <input type="hidden" name="options[{{ ord($label) - 65 }}][label]" value="{{ $label }}">
                                        <input type="text" name="options[{{ ord($label) - 65 }}][teks_opsi]" class="form-control" value="{{ $oldOption }}" placeholder="Teks opsi {{ $label }}">
                                        <span class="input-group-text">
                                            <input type="radio" name="correct_label" value="{{ $label }}" {{ old('correct_label') === $label ? 'checked' : '' }} title="Kunci {{ $label }}">
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pembahasan (opsional)</label>
                                <textarea name="pembahasan" class="form-control" rows="2">{{ old('pembahasan') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Soal</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-information-line me-1 text-info"></i>Ketentuan Cepat</h6></div>
                    <div class="card-body small">
                        <ul class="ps-3 mb-3">
                            <li class="mb-2"><strong>Tugas Harian / Formatif</strong> — tidak wajib validasi.</li>
                            <li class="mb-2"><strong>STS / SAS</strong> — otomatis diajukan ke validasi guru serumpun. Cetak hanya aktif setelah mayoritas reviewer setuju.</li>
                            <li class="mb-2">Soal masuk ke <strong>Soal Saya</strong>. Bank soal dibuat otomatis.</li>
                            <li>Kisi-kisi tidak wajib.</li>
                        </ul>
                        <div class="alert alert-warning py-2 mb-0" id="formalWarning" style="display:none;">
                            <i class="ri-alert-line me-1"></i>Soal STS/SAS akan masuk antrean validasi guru serumpun.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tipe = document.getElementById('tipeSoal');
        const options = document.getElementById('optionsWrap');
        const jenis = document.getElementById('jenisAsesmen');
        const warning = document.getElementById('formalWarning');

        function sync() {
            const needsOptions = ['pg', 'bs', 'jodoh'].includes(tipe.value);
            options.style.display = needsOptions ? '' : 'none';
            warning.style.display = ['sts', 'sas'].includes(jenis.value) ? '' : 'none';
        }

        tipe?.addEventListener('change', sync);
        jenis?.addEventListener('change', sync);
        sync();
    });
    </script>
@endsection
