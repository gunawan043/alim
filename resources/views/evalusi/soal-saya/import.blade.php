@extends('layouts.master')
@section('title', 'Import Soal Word')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}">Soal Saya</a> @endslot
        @slot('title') Import Soal Word @endslot
    @endcomponent

    <x-soal-flow current="bank" :scope="$scope ?? null" :userId="$userId" />

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('import_warnings') && count(session('import_warnings')))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>Catatan import:</strong>
            <ul class="mb-0 mt-1">@foreach(session('import_warnings') as $w)<li>{{ $w }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-file-word-line me-1 text-primary"></i>Upload Dokumen Word (.docx)</h5>
                    <p class="text-muted mb-0 small">Sistem membaca soal bernomor, opsi A–E, kunci, dan pembahasan secara otomatis.</p>
                </div>
                <form method="POST" action="{{ route('user.soal-saya.import-store', ['userId' => $userId]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    @forelse($subjects as $s)
                                        <option value="{{ $s->id }}" {{ old('subject_id') === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @empty
                                        <option value="">Belum ada mapel pada SK aktif</option>
                                    @endforelse
                                </select>
                                <small class="text-muted">Hanya mapel yang tercantum di SK Pembagian Tugas aktif Anda.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kelas</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($grades as $g)<option value="{{ $g->id }}" {{ old('grade_level_id') === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Asesmen <span class="text-danger">*</span></label>
                                <select name="jenis_asesmen" class="form-select" required>
                                    @foreach(\App\Models\Soal::JENIS_ASESMEN_OPTIONS as $value => $label)<option value="{{ $value }}" {{ old('jenis_asesmen', 'harian') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)<option value="{{ $ay->id }}" {{ (string) old('academic_year_id', $selectedAyId) === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ old('semester', $semester) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester', $semester) === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">File Word <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".docx" required>
                                <div class="form-text">Hanya .docx (Word modern). Maksimal 10 MB. File .doc lama belum didukung.</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="ri-upload-2-line me-1"></i>Baca & Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-information-line me-1 text-info"></i>Format Word yang Dibaca</h5></div>
                <div class="card-body small">
                    <p class="mb-2">Gunakan format sederhana seperti ini:</p>
                    <pre class="bg-light p-2 rounded small mb-3">1. Pertanyaan pertama ...
A. Opsi A
B. Opsi B
C. Opsi C
D. Opsi D
Kunci: B
Pembahasan: ...

2. Pertanyaan kedua ...
A. ...
B. ...
Kunci: A</pre>
                    <ul class="ps-3 mb-0">
                        <li>Nomor soal: <code>1.</code> atau <code>1)</code>.</li>
                        <li>Opsi: <code>A.</code> sampai <code>E.</code> (boleh A–D).</li>
                        <li>Kunci: <code>Kunci: B</code> atau blok <code>KUNCI JAWABAN</code> di akhir.</li>
                        <li>Pembahasan: <code>Pembahasan: ...</code>.</li>
                        <li>Tanpa opsi otomatis dianggap soal uraian.</li>
                        <li>Gambar belum diimpor otomatis; tambahkan manual bila ada.</li>
                        <li>STS/SAS otomatis masuk validasi guru serumpun.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
