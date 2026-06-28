@extends('layouts.master')
@section('title') Buat Paket Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Paket Soal @endslot
        @slot('li_2') Buat @endslot
        @slot('title') Buat dari Kisi-kisi @endslot
    @endcomponent

    <!-- Kisi-kisi info -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">{{ $kisi->judul }}</h5>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Mapel</dt><dd class="col-sm-9">{{ $kisi->subject->name ?? '-' }}</dd>
                <dt class="col-sm-3">Fase</dt><dd class="col-sm-9">{{ $kisi->gradeLevel->nama ?? '-' }}</dd>
                <dt class="col-sm-3">Jenis</dt><dd class="col-sm-9">{{ str_replace('_', ' ', $kisi->jenis_ujian) }}</dd>
                <dt class="col-sm-3">Semester</dt><dd class="col-sm-9">{{ ucfirst($kisi->semester) }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Item Kisi-kisi</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead><tr><th>TP</th><th>Level</th><th>Soal Dibutuhkan</th></tr></thead>
                <tbody>
                    @foreach($kisi->items as $item)
                    <tr>
                        <td>{{ Str::limit($item->tujuanPembelajaran->deskripsi ?? $item->tujuanPembelajaran->nama ?? $item->tp_id, 50) }}</td>
                        <td><span class="badge bg-secondary">{{ $item->level_kognitif }}</span></td>
                        <td><strong>{{ $item->jumlah_soal }} butir</strong></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Total</th><th>{{ $kisi->items->sum('jumlah_soal') }} butir</th></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <form action="{{ route('user.paket-soal.store', $kisi->id) }}" method="POST">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Pengaturan Paket</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Judul Paket <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control"
                               value="{{ old('judul', 'Paket '.$kisi->jenis_ujian.' '.$kisi->subject->name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu Pengerjaan (menit) <span class="text-danger">*</span></label>
                        <input type="number" name="waktu_pengerjaan_menit" class="form-control" value="{{ old('waktu_pengerjaan_menit', 90) }}" min="15" max="480" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">KKM</label>
                        <input type="number" name="kkm" class="form-control" value="{{ old('kkm') }}" step="0.01" min="0" max="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Scope Bagikan</label>
                        <select name="shared_scope" class="form-select">
                            <option value="private" selected>Pribadi</option>
                            <option value="internal_school">Internal Sekolah</option>
                            <option value="public_pool">Pool Publik</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bank Soal Sumber <span class="text-danger">*</span></label>
                        <select name="bank_soal_id" class="form-select" required>
                            <option value="">-- Pilih Bank Soal --</option>
                            @foreach(\App\Models\BankSoal::where('subject_id', $kisi->subject_id)->get() as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Instruksi Umum</label>
                        <textarea name="instruksi_umum" class="form-control" rows="3" placeholder="Petunjuk umum pengerjaan..."></textarea>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_acak_urutan_soal" class="form-check-input" id="acakUrutan" checked>
                            <label class="form-check-label" for="acakUrutan">Acak urutan soal</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_acak_opsi" class="form-check-input" id="acakOpsi" checked>
                            <label class="form-check-label" for="acakOpsi">Acak pilihan jawaban</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('user.paket-soal.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-success"><i class="ri-rocket-line"></i> Bangun Paket Soal</button>
        </div>
    </form>
@endsection