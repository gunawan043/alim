@extends('layouts.master')

@section('title', 'Buat Laporan Aplikasi')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.feedback.index', ['userId' => $userId]) }}">Kritik & Saran</a> @endslot
        @slot('title') Buat Laporan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i><strong>Periksa kembali laporan Anda:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.feedback.store', ['userId' => $userId]) }}"
          enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-edit-box-line text-primary me-1"></i>Detail Laporan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    @foreach(\App\Models\AppFeedbackReport::CATEGORIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category', 'bug') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    @foreach(\App\Models\AppFeedbackReport::PRIORITIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', 'sedang') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Judul Laporan <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" maxlength="180" required
                                       value="{{ old('title') }}" placeholder="Contoh: Tombol simpan nilai tidak merespons">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Uraian Masalah / Kritik / Saran <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="5" maxlength="10000" required
                                          placeholder="Jelaskan masalah atau masukan Anda secara ringkas dan jelas.">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Kronologi / Proses Terjadinya Masalah
                                    <span class="text-muted small">(wajib untuk laporan bug)</span>
                                </label>
                                <textarea name="process" class="form-control" rows="5" maxlength="10000"
                                          placeholder="Contoh: Buka menu Nilai → pilih kelas → tab Mapel → klik Simpan. Muncul pesan error...">{{ old('process') }}</textarea>
                                <div class="form-text">Tuliskan langkah-langkah yang dilakukan sebelum masalah muncul agar dapat direproduksi.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hasil yang Diharapkan</label>
                                <textarea name="expected" class="form-control" rows="3" maxlength="5000"
                                          placeholder="Contoh: Nilai tersimpan dan muncul notifikasi berhasil.">{{ old('expected') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL / Halaman Terkait</label>
                                <input type="text" name="page_url" class="form-control" maxlength="2048"
                                       value="{{ old('page_url') }}" placeholder="/kurikulum/perangkat atau URL lengkap">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-attachment-line text-primary me-1"></i>Bukti Pendukung</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Upload Bukti</label>
                        <input type="file" name="evidence[]" class="form-control" multiple
                               accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.zip">
                        <div class="form-text">
                            Maksimal 5 file, masing-masing 10 MB. Format: gambar, PDF, dokumen, teks, atau ZIP.
                        </div>
                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Bukti bersifat privat. Hanya Anda dan pengelola laporan yang dapat membukanya.
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-send-plane-line me-1"></i>Kirim Laporan
                        </button>
                        <a href="{{ route('user.feedback.index', ['userId' => $userId]) }}" class="btn btn-light w-100 mt-2">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
