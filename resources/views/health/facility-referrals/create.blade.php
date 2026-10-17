@extends('layouts.master')
@section('title') Tambah Faskes Rujukan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}">Faskes Rujukan</a> @endslot
        @slot('title') Tambah Faskes @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.facility-referrals.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Form Faskes Rujukan</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Faskes <span class="text-danger">*</span></label>
                                    <input type="text" name="facility_name" class="form-control @error('facility_name') is-invalid @enderror" value="{{ old('facility_name') }}" required placeholder="Contoh: Puskesmas Sejahtera">
                                    @error('facility_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Faskes <span class="text-danger">*</span></label>
                                    <select name="facility_type" class="form-control @error('facility_type') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['puskesmas','rumah_sakit','klinik','dokter_praktik','rs_psychologist','posyandu'] as $t)
                                            <option value="{{ $t }}" {{ old('facility_type')==$t?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('facility_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="021-xxxx">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jarak (km)</label>
                                    <input type="number" step="0.1" name="distance_km" class="form-control" value="{{ old('distance_km') }}" placeholder="Contoh: 2.5">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jam Operasional</label>
                                    <input type="text" name="operating_hours" class="form-control" value="{{ old('operating_hours') }}" placeholder="08.00 - 16.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Layanan (JSON/Catatan)</label>
                                    <input type="text" name="services" class="form-control" value="{{ old('services') }}" placeholder="CT-Scan, IGD, dll">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available_24h" class="form-check-input" id="is24h" value="1" {{ old('is_available_24h') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is24h">Buka 24 Jam</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Aktif</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection