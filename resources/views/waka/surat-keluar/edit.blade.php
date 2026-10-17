@extends('waka.master')

@section('title') Edit Surat Keluar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
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

    <form action="{{ route('waka.surat-keluar.update', $surat->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ri-edit-line me-2 text-primary"></i>Data Surat Keluar</h5>
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
                                <div class="form-text">Pilih template untuk mengganti perihal dan isi surat.</div>
                            </div>
                        </div>
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
                            <label class="col-sm-3 col-form-label">Tanggal Kirim</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_kirim" class="form-control" value="{{ old('tanggal_kirim', $surat->tanggal_kirim?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="tujuan" class="form-control" value="{{ old('tujuan', $surat->tujuan) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Perihal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="perihal" id="perihal" class="form-control" value="{{ old('perihal', $surat->perihal) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Isi Surat</label>
                            <div class="col-sm-9">
                                <textarea name="isi_surat" id="isi_surat" class="form-control" rows="9" placeholder="Isi badan surat…">{{ old('isi_surat', $surat->isi_surat) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Sifat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat" class="form-select" required>
                                    @foreach(\App\Models\SuratKeluar::SIFAT_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('sifat', $surat->sifat) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    @foreach(\App\Models\SuratKeluar::STATUS_OPTIONS as $val => $label)
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
                        <h5 class="card-title mb-0"><i class="ri-pen-nib-line me-2 text-primary"></i>Penandatangan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Penandatangan</label>
                            <input type="text" name="penandatangan" class="form-control" value="{{ old('penandatangan', $surat->penandatangan) }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan_penandatangan" class="form-control" value="{{ old('jabatan_penandatangan', $surat->jabatan_penandatangan) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('waka.surat-keluar.show', $surat->id) }}" class="btn btn-light"><i class="ri-arrow-go-back-line me-1"></i>Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Perubahan</button>
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
        if (perihal) perihal.value = opt.dataset.perihal || perihal.value;
        if (isi) isi.value = opt.dataset.isi || isi.value;
    });
</script>
@endsection
