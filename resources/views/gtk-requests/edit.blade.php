@extends('layouts.master')
@section('title') Edit Request GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') Daftar Request GTK @endslot
        @slot('title') Edit Request @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Edit Request GTK</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.gtk-requests.update', $gtkRequest->id) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-control" required>
                                    <option value="">Pilih Satuan Kerja</option>
                                    @foreach($workUnits ?? [] as $wu)
                                        <option value="{{ $wu->id }}" {{ $gtkRequest->work_unit_id == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                <input type="text" name="jabatan" class="form-control" value="{{ $gtkRequest->jabatan }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah" class="form-control" value="{{ $gtkRequest->jumlah }}" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                <textarea name="alasan" class="form-control" rows="4" required>{{ $gtkRequest->alasan }}</textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.gtk-requests.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
