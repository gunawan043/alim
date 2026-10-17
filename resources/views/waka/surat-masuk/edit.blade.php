@extends('waka.master')

@section('title') Edit Surat Masuk @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-masuk.index') }}">Surat Masuk</a> @endslot
        @slot('title') Edit {{ $surat->nomor_surat }} @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('waka.surat-masuk.update', $surat->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-edit-line me-2 text-primary"></i>Data Surat Masuk</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nomor Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', $surat->tanggal_surat?->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_diterima" class="form-control" value="{{ old('tanggal_diterima', $surat->tanggal_diterima?->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Pengirim <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="pengirim" class="form-control" value="{{ old('pengirim', $surat->pengirim) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Perihal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="perihal" class="form-control" value="{{ old('perihal', $surat->perihal) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Sifat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat" class="form-select" required>
                                    @foreach(\App\Models\SuratMasuk::SIFAT_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('sifat', $surat->sifat) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Penyelesaian <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat_penyelesaian" class="form-select" required>
                                    @foreach(\App\Models\SuratMasuk::PENYELESAIAN_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('sifat_penyelesaian', $surat->sifat_penyelesaian) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    @foreach(\App\Models\SuratMasuk::STATUS_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('status', $surat->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-sm-3 col-form-label">Lampiran</label>
                            <div class="col-sm-9">
                                @if($surat->file_lampiran)
                                    <div class="mb-2">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($surat->file_lampiran) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-attachment-2 me-1"></i>Lihat Lampiran Saat Ini
                                        </a>
                                    </div>
                                @endif
                                <input type="file" name="file_lampiran" class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <div class="form-text">Kosongkan jika tidak ingin mengganti lampiran. Maksimal 5 MB.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-share-forward-line me-2 text-primary"></i>Disposisi</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Diteruskan Kepada</label>
                            <input type="text" name="disposisi_to" class="form-control" value="{{ old('disposisi_to', $surat->disposisi_to) }}" placeholder="Nama / jabatan penerima">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Catatan Disposisi</label>
                            <textarea name="disposisi_catatan" class="form-control" rows="5" placeholder="Instruksi atau catatan…">{{ old('disposisi_catatan', $surat->disposisi_catatan) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('waka.surat-masuk.show', $surat->id) }}" class="btn btn-light"><i class="ri-arrow-go-back-line me-1"></i>Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Perubahan</button>
        </div>
    </form>
@endsection
