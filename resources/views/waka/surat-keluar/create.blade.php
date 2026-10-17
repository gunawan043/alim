@extends('waka.master')

@section('title') Tambah Surat Keluar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
        @slot('title') Tambah Surat Keluar @endslot
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

    <form action="{{ route('waka.surat-keluar.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-send-plane-line me-2 text-primary"></i>Data Surat Keluar</h5>
                        <a href="{{ route('waka.surat-template.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-file-text-line me-1"></i>Kelola Template
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Gunakan Template</label>
                            <div class="col-sm-9">
                                <select id="suratTemplate" class="form-select">
                                    <option value="">— Tanpa template —</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}"
                                                data-perihal="{{ $template->perihal_template }}"
                                                data-isi="{{ $template->isi_template }}">
                                            {{ $template->nama }}{{ $template->school_id ? '' : ' (global)' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih template untuk mengisi perihal dan isi surat otomatis.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nomor Surat</label>
                            <div class="col-sm-9">
                                <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat') }}" placeholder="Kosongkan untuk nomor otomatis">
                                <div class="form-text">Jika dikosongkan, nomor dibuat otomatis (contoh: 001/SK/VII/2026).</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', now()->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Kirim</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_kirim" class="form-control" value="{{ old('tanggal_kirim') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="tujuan" class="form-control" value="{{ old('tujuan') }}" placeholder="Instansi / perorangan" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Perihal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="perihal" id="perihal" class="form-control" value="{{ old('perihal') }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Isi Surat</label>
                            <div class="col-sm-9">
                                <textarea name="isi_surat" id="isi_surat" class="form-control" rows="9" placeholder="Isi badan surat…">{{ old('isi_surat') }}</textarea>
                                <div class="form-text">Tampil pada cetak surat dan dapat diisi otomatis dari template.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Sifat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat" class="form-select" required>
                                    @foreach(\App\Models\SuratKeluar::SIFAT_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('sifat', 'biasa') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    @foreach(\App\Models\SuratKeluar::STATUS_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('status', 'draft') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-sm-3 col-form-label">Lampiran</label>
                            <div class="col-sm-9">
                                <input type="file" name="file_lampiran" class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <div class="form-text">PDF, JPG, PNG, DOC/DOCX — maksimal 5 MB.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-pen-nib-line me-2 text-primary"></i>Penandatangan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Penandatangan</label>
                            <input type="text" name="penandatangan" class="form-control" value="{{ old('penandatangan') }}" placeholder="Nama lengkap">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan_penandatangan" class="form-control" value="{{ old('jabatan_penandatangan') }}" placeholder="Contoh: Kepala Sekolah">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('waka.surat-keluar.index') }}" class="btn btn-light"><i class="ri-arrow-go-back-line me-1"></i>Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Surat</button>
        </div>
    </form>
@endsection


@section('script')
<script>
    document.getElementById('suratTemplate')?.addEventListener('change', function () {
        var opt = this.selectedOptions[0];
        if (!this.value || !opt) return;
        var perihal = document.getElementById('perihal');
        var isi = document.getElementById('isi_surat');
        if (perihal) perihal.value = opt.dataset.perihal || '';
        if (isi) isi.value = opt.dataset.isi || '';
    });
</script>
@endsection
