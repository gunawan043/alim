@extends('layouts.master')
@section('title') Tambah Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('title') Tambah Asrama @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('user.asrama.store', ['userId' => $userId]) }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-hotel-line me-1"></i> Form Asrama</h5></div>
                    <div class="card-body">
                        @if(!app('request')->attributes->get('schoolContextId'))
                        <div class="mb-3">
                            <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                            <select name="school_id" class="form-control @error('school_id') is-invalid @enderror">
                                <option value="">— Pilih Sekolah —</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('school_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Contoh: ASR-01">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Asrama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Asrama Al-Mubarak">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control @error('gender') is-invalid @enderror">
                                    <option value="">— Pilih —</option>
                                    <option value="putra" {{ old('gender') == 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ old('gender') == 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kapasitas Total</label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 50) }}" min="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kepala Asrama</label>
                                <input type="text" name="head_id" class="form-control" placeholder="Cari GTK...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Kamar</label>
                                <input type="number" name="total_rooms" class="form-control" value="{{ old('total_rooms', 0) }}" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Gedung</label>
                                <input type="number" name="total_wings" class="form-control" value="{{ old('total_wings', 0) }}" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" checked>
                                    <label class="form-check-label" for="isActive">Asrama Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection