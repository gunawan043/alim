@extends('layouts.master')
@section('title') Tambah Penghargaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Tambah Penghargaan @endslot
    @endcomponent

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Gagal!</strong> Terdapat masukan yang salah.<br>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Form Penghargaan</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('user.asrama.rewards.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Katalog Penghargaan <span class="text-danger">*</span></label>
                        <select name="point_catalog_id" id="point_catalog_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih katalog penghargaan --</option>
                            @foreach($catalogs as $catalog)
                                <option value="{{ $catalog->id }}"
                                        data-title="{{ $catalog->name }}"
                                        data-category="{{ $catalog->rewardCategoryValue() }}"
                                        data-level="{{ $catalog->rewardLevelValue() }}"
                                        data-points="{{ $catalog->points_min }}"
                                        @selected(old('point_catalog_id') === $catalog->id)>
                                    {{ $catalog->code }} — {{ $catalog->name }} ({{ $catalog->points_min }} poin)
                                </option>
                            @endforeach
                        </select>
                        @error('point_catalog_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @if($catalogs->isEmpty())
                            <div class="alert alert-warning py-2 mt-2 mb-0 small">
                                <i class="ri-alert-line me-1"></i>Belum ada katalog penghargaan aktif. Hubungi Superadmin untuk menambahkan katalog.
                            </div>
                        @endif
                        <small class="text-muted">Judul, kategori, level, dan poin diisi otomatis dari katalog.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Santri <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Santri --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id') === $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} - {{ $student->nisn ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tahun Akademik</label>
                        <select name="academic_year_id" class="form-select form-select-sm">
                            <option value="">Default (tahun aktif)</option>
                            @foreach(\App\Models\AcademicYear::all() as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id') === $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Judul <span class="text-muted">(otomatis)</span></label>
                        <input type="text" name="title" id="reward_title" class="form-control form-control-sm" value="{{ old('title') }}" readonly placeholder="Pilih katalog penghargaan">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pemberian <span class="text-danger">*</span></label>
                        <input type="date" name="awarded_date" class="form-control form-control-sm" value="{{ old('awarded_date', today()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Kategori <span class="text-muted">(otomatis)</span></label>
                        <select name="category" id="reward_category" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach(\App\Models\DormitoryReward::categories() as $key => $label)
                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Level <span class="text-muted">(otomatis)</span></label>
                        <select name="level" id="reward_level" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Level --</option>
                            @foreach(\App\Models\DormitoryReward::levels() as $key => $label)
                                <option value="{{ $key }}" {{ old('level') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Poin <span class="text-muted">(otomatis)</span></label>
                        <input type="number" name="points" id="reward_points" class="form-control form-control-sm" value="{{ old('points') }}" readonly placeholder="Pilih katalog">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi pencapaian atau alasan penghargaan...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Dokumen Pendukung</label>
                        <input type="file" name="proof_path" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Foto/surat dukungan (maks 2MB)</small>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="ri-save-line"></i> Simpan</button>
                    <a href="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-secondary"><i class="ri-arrow-go-back-line"></i> Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
(function () {
    'use strict';
    const select = document.getElementById('point_catalog_id');
    if (!select) return;

    function syncRewardCatalog() {
        const option = select.selectedOptions[0];
        if (!option || !option.value) return;

        document.getElementById('reward_title').value = option.dataset.title || '';
        document.getElementById('reward_category').value = option.dataset.category || '';
        document.getElementById('reward_level').value = option.dataset.level || '';
        document.getElementById('reward_points').value = option.dataset.points || '0';
    }

    select.addEventListener('change', syncRewardCatalog);
    if (select.value) syncRewardCatalog();

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(select).select2({
            width: '100%',
            placeholder: 'Cari kode atau nama penghargaan...',
            allowClear: true,
        });
        const student = document.querySelector('select[name="student_id"]');
        if (student) {
            jQuery(student).select2({
                width: '100%',
                placeholder: 'Cari nama santri...',
                allowClear: true,
            });
        }
    }
})();
</script>
@endsection
