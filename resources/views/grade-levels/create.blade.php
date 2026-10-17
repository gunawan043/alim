@extends('layouts.master')
@section('title') Tambah Tingkat Kelas @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Data Kelas</a> @endslot
        @slot('title') Tambah Tingkat @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.grade-levels.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-stack-line text-primary me-1"></i>Form Tingkat Kelas</h5>
                        <p class="text-muted mb-0 small">Tingkat kelas menghubungkan fase CP dengan rombel dan JP efektif.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                @if($schoolContext)
                                    <input type="text" class="form-control" value="{{ $schoolContext->name }}" readonly>
                                    <input type="hidden" name="school_id" value="{{ $schoolContext->id }}">
                                @else
                                    <select name="school_id" class="form-select" required>
                                        <option value="">— Pilih Sekolah —</option>
                                        @foreach($schools as $s)
                                            <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                <input type="number" name="level" class="form-control" value="{{ old('level') }}" min="1" max="15" required placeholder="Contoh: 7">
                                <small class="text-muted">Angka tingkat (1–15)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Tingkat <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Kelas 7">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Contoh: VII" maxlength="20">
                                <small class="text-muted">Kode romawi opsional (VII, VIII, IX)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fase</label>
                                <input type="text" name="fase" class="form-control" value="{{ old('fase') }}" maxlength="5" placeholder="Contoh: D">
                                <small class="text-muted">Fase capaian pembelajaran (A–F) — dipakai CP, TP, dan ATP.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Tingkat kelas aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-information-line text-info me-1"></i>Panduan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted small mb-0 ps-3">
                            <li class="mb-2"><strong>Tingkat</strong> = angka urut untuk pengurutan kelas (7, 8, 9).</li>
                            <li class="mb-2"><strong>Fase</strong> dipakai CP/TP/ATP — pastikan konsisten dengan jenjang (A–F).</li>
                            <li class="mb-2">Setelah tingkat dibuat, tambahkan <strong>Rombel</strong> dan alokasi mapelnya.</li>
                            <li>Tingkat nonaktif tidak muncul saat pembuatan rombel baru.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
