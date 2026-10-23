@extends('layouts.master')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
@section('title') Buat Paket Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('li_2') Paket Soal @endslot
        @slot('title') Buat Paket @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="mb-2">{{ $kisi->judul }}</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary-subtle text-primary"><i class="ri-book-2-line me-1"></i>{{ $kisi->subject->name ?? '-' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-layout-grid-line me-1"></i>{{ $kisi->gradeLevel->name ?? 'Semua tingkat' }}</span>
                        <span class="badge bg-info-subtle text-info"><i class="ri-calendar-line me-1"></i>{{ ucfirst($kisi->semester) }}</span>
                        <span class="badge bg-dark-subtle text-dark">{{ str_replace('_', ' ', ucfirst($kisi->jenis_ujian)) }}</span>
                    </div>
                </div>
                <a href="{{ route('user.asesmen.show', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Asesmen
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <form action="{{ route('user.paket-soal.store', ['userId' => $userId, 'kisiKisiId' => $kisi->id]) }}" method="POST">
                @csrf
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="ri-settings-3-line text-primary"></i>
                        <span>Pengaturan Paket</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Judul Paket <span class="text-danger">*</span></label>
                                <input type="text" name="judul" class="form-control"
                                       value="{{ old('judul', 'Paket '.str_replace('_', ' ', $kisi->jenis_ujian).' '.$kisi->subject->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Waktu <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="waktu_pengerjaan_menit" class="form-control" value="{{ old('waktu_pengerjaan_menit', 90) }}" min="15" max="480" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">KKTP</label>
                                <input type="number" name="kkm" class="form-control" value="{{ old('kkm') }}" step="0.01" min="0" max="100">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Bank Soal Sumber <span class="text-danger">*</span></label>
                                <select name="bank_soal_id" class="form-select" required>
                                    <option value="">-- Pilih Bank Soal --</option>
                                    @foreach(\App\Models\BankSoal::accessibleBy($userId, $kisi->school_id)->where('subject_id', $kisi->subject_id)->orderBy('nama')->get() as $bank)
                                        <option value="{{ $bank->id }}" {{ (string) old('bank_soal_id') === (string) $bank->id ? 'selected' : '' }}>{{ $bank->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Bank harus berisi soal approved yang sesuai kisi-kisi.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scope Bagikan</label>
                                <select name="shared_scope" class="form-select">
                                    <option value="private" {{ old('shared_scope', 'private') === 'private' ? 'selected' : '' }}>Pribadi</option>
                                    <option value="internal_school" {{ old('shared_scope') === 'internal_school' ? 'selected' : '' }}>Internal Sekolah</option>
                                    <option value="public_pool" {{ old('shared_scope') === 'public_pool' ? 'selected' : '' }}>Pool Publik</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Instruksi Umum</label>
                                <textarea name="instruksi_umum" class="form-control" rows="3" placeholder="Petunjuk umum pengerjaan...">{{ old('instruksi_umum') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_acak_urutan_soal" value="0">
                                    <input type="checkbox" name="is_acak_urutan_soal" value="1" class="form-check-input" id="acakUrutan" {{ old('is_acak_urutan_soal', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="acakUrutan">Acak urutan soal</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_acak_opsi" value="0">
                                    <input type="checkbox" name="is_acak_opsi" value="1" class="form-check-input" id="acakOpsi" {{ old('is_acak_opsi', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="acakOpsi">Acak pilihan jawaban</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-3">
                    <a href="{{ route('user.asesmen.show', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-success"><i class="ri-rocket-line me-1"></i> Bangun Paket Soal</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-file-list-3-line text-primary me-1"></i>Kebutuhan Soal</span>
                    <span class="badge bg-primary-subtle text-primary">{{ $kisi->items->sum('jumlah_soal') }} butir</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>TP</th><th>Level</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse($kisi->items as $item)
                                    <tr>
                                        <td><span class="small">{{ Str::limit($item->tujuanPembelajaran->deskripsi ?? $item->tujuanPembelajaran->nama ?? $item->tp_id, 60) }}</span></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->level_kognitif }}</span></td>
                                        <td class="text-end fw-semibold">{{ $item->jumlah_soal }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Kisi-kisi belum memiliki item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <div class="fw-semibold mb-1"><i class="ri-information-line me-1"></i>Yang terjadi setelah ini</div>
                <div class="small">Paket akan mengambil soal approved dari bank yang dipilih sesuai TP dan jumlah pada kisi-kisi. Periksa kembali komposisi sebelum publish.</div>
            </div>
        </div>
    </div>
@endsection
