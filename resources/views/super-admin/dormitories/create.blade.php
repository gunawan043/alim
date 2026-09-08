@extends('layouts.master')
@section('title') Tambah Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}" class="text-muted">Manajemen Asrama</a> @endslot
        @slot('title') Tambah Asrama @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Tambah Asrama Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.dormitories.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
                        @csrf

                        <h6 class="mb-3">Informasi Dasar</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Nama Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Asrama</label>
                                <input type="text" name="code" class="form-control" placeholder="Otomatis">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Pilih...</option>
                                    <option value="putra">Putra</option>
                                    <option value="putri">Putri</option>
                                    <option value="campuran">Campuran</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-select" required>
                                    <option value="">Pilih sekolah...</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit Kerja</label>
                                <select name="work_unit_id" class="form-select">
                                    <option value="">Pilih unit...</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pengawas Asrama</label>
                                <select name="head_id" class="form-select">
                                    <option value="">Tidak ditentukan</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Kapasitas</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Total Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Kamar</label>
                                <input type="number" name="total_rooms" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Sayap</label>
                                <input type="number" name="total_wings" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <h6 class="mb-3">Informasi Kontak</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">Lainnya</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Logo Asrama</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <div class="float-end gap-2">
                            <a href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
