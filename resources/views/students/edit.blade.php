@extends('layouts.master')
@section('title') Edit {{ $student->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Edit {{ $student->name }} @endslot
    @endcomponent

    @isset($errors)
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
    @endisset

    <form method="POST" action="{{ route('user.students.update', ['userId' => $userId, 'santriUuid' => $student->id]) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-identitas" type="button"><i class="ri-user-line me-1"></i>Identitas</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alamat" type="button"><i class="ri-home-line me-1"></i>Alamat</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kesehatan" type="button"><i class="ri-heart-line me-1"></i>Kesehatan</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-keluarga" type="button"><i class="ri-parent-line me-1"></i>Keluarga</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pendaftaran" type="button"><i class="ri-file-list-line me-1"></i>Pendaftaran</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button"><i class="ri-bank-line me-1"></i>Bank</button></li>
        </ul>

        <div class="tab-content">

            {{-- TAB 1: Identitas --}}
            <div class="tab-pane fade show active" id="tab-identitas" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Identitas Santri</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-control" required>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" {{ old('school_id', $student->school_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NISN <span class="text-danger">*</span></label>
                                <input type="text" name="nisn" class="form-control" value="{{ old('nisn', $student->nisn) }}" maxlength="20" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NIS</label>
                                <input type="text" name="nis" class="form-control" value="{{ old('nis', $student->nis) }}" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NIK</label>
                                <input type="text" name="nik" class="form-control" value="{{ old('nik', $student->nik) }}" maxlength="30">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. KK</label>
                                <input type="text" name="no_kk" class="form-control" value="{{ old('no_kk', $student->no_kk) }}" maxlength="30">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">JK <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="L" {{ old('gender', $student->gender) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $student->gender) === 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $student->birth_place) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Agama</label>
                                <select name="religion" class="form-control">
                                    <option value="">—</option>
                                    @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Khonghucu'] as $r)
                                        <option value="{{ strtolower($r) }}" {{ old('religion', $student->religion) === strtolower($r) ? 'selected' : '' }}>{{ $r }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Kebutuhan Khusus</label>
                                <select name="special_needs" class="form-control">
                                    <option value="tidak" {{ old('special_needs', $student->special_needs) === 'tidak' ? 'selected' : '' }}>Tidak</option>
                                    <option value="fisik" {{ old('special_needs', $student->special_needs) === 'fisik' ? 'selected' : '' }}>Fisik</option>
                                    <option value="intelektual" {{ old('special_needs', $student->special_needs) === 'intelektual' ? 'selected' : '' }}>Intelektual</option>
                                    <option value="mental" {{ old('special_needs', $student->special_needs) === 'mental' ? 'selected' : '' }}>Mental</option>
                                    <option value="sosial" {{ old('special_needs', $student->special_needs) === 'sosial' ? 'selected' : '' }}>Sosial</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Upload Foto</label>
                                <input type="file" name="photo_path" class="form-control" accept="image/*">
                                <small class="text-muted">JPG/PNG, maks 2MB</small>
                                @if($student->photo_path)
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/' . $student->photo_path) }}" class="rounded" width="52" height="52" style="object-fit:cover">
                                        <label class="small text-danger">
                                            <input type="checkbox" name="remove_photo" value="1"> Hapus foto
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: Alamat --}}
            <div class="tab-pane fade" id="tab-alamat" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Alamat Lengkap</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $student->address) }}</textarea>
                            </div>
                            <div class="col-md-2"><label class="form-label">RT</label><input type="text" name="rt" class="form-control" value="{{ old('rt', $student->rt) }}" maxlength="5"></div>
                            <div class="col-md-2"><label class="form-label">RW</label><input type="text" name="rw" class="form-control" value="{{ old('rw', $student->rw) }}" maxlength="5"></div>
                            <div class="col-md-4"><label class="form-label">Dusun</label><input type="text" name="hamlet" class="form-control" value="{{ old('hamlet', $student->hamlet) }}" maxlength="100"></div>
                            <div class="col-md-4"><label class="form-label">Kode Pos</label><input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $student->postal_code) }}" maxlength="10"></div>
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <select id="province_code" name="province_code" class="form-control">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach(App\Models\Province::orderBy('name')->get() as $p)
                                        <option value="{{ $p->code }}" {{ old('province_code', $student->province_code) == $p->code ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kabupaten/Kota</label>
                                <select id="city_code" name="city_code" class="form-control"><option value="">Pilih Kabupaten/Kota</option></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <select id="district_code" name="district_code" class="form-control"><option value="">Pilih Kecamatan</option></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Desa/Kelurahan</label>
                                <select id="village_code" name="village_code" class="form-control"><option value="">Pilih Desa/Kelurahan</option></select>
                            </div>
                            <div class="col-md-4"><label class="form-label">No. Telepon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}" maxlength="20"></div>
                            <div class="col-md-4"><label class="form-label">No. HP</label><input type="text" name="mobile_phone" class="form-control" value="{{ old('mobile_phone', $student->mobile_phone) }}" maxlength="20"></div>
                            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}"></div>
                            <div class="col-md-4">
                                <label class="form-label">Tempat Tinggal</label>
                                <select name="residence_type" class="form-control">
                                    @foreach(['milik_orangtua','sewa','asrama','panti','lainnya'] as $rt)
                                        <option value="{{ $rt }}" {{ old('residence_type', $student->residence_type) === $rt ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$rt)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transportasi</label>
                                <select name="transportation" class="form-control">
                                    @foreach(['jalan_kaki','sepeda','motor','mobil','angkutan_umum','antar_jemput'] as $tr)
                                        <option value="{{ $tr }}" {{ old('transportation', $student->transportation) === $tr ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$tr)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Jarak ke Sekolah (km)</label><input type="number" name="distance_to_school" class="form-control" value="{{ old('distance_to_school', $student->distance_to_school) }}" step="0.01" min="0"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Kesehatan --}}
            <div class="tab-pane fade" id="tab-kesehatan" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Data Kesehatan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Tinggi Badan (cm)</label><input type="number" name="height" class="form-control" value="{{ old('height', $student->height) }}" min="0"></div>
                            <div class="col-md-3"><label class="form-label">Berat Badan (kg)</label><input type="number" name="weight" class="form-control" value="{{ old('weight', $student->weight) }}" min="0"></div>
                            <div class="col-md-3"><label class="form-label">Lingkar Kepala (cm)</label><input type="number" name="head_circumference" class="form-control" value="{{ old('head_circumference', $student->head_circumference) }}" min="0"></div>
                            <div class="col-md-3"><label class="form-label">Jumlah Saudara</label><input type="number" name="sibling_count" class="form-control" value="{{ old('sibling_count', $student->sibling_count) }}" min="0"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 4: Keluarga --}}
            <div class="tab-pane fade" id="tab-keluarga" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0">Data Ayah</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Nama Ayah</label><input type="text" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name) }}"></div>
                            <div class="col-md-2"><label class="form-label">Tahun Lahir</label><input type="number" name="father_birth_year" class="form-control" value="{{ old('father_birth_year', $student->father_birth_year) }}" min="1900" max="2030"></div>
                            <div class="col-md-3"><label class="form-label">Pendidikan</label><select name="father_education" class="form-control"><option value="">—</option>@foreach(['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'] as $ed)<option value="{{ $ed }}" {{ old('father_education', $student->father_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Pekerjaan</label><input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', $student->father_occupation) }}"></div>
                            <div class="col-md-3"><label class="form-label">NIK Ayah</label><input type="text" name="father_nik" class="form-control" value="{{ old('father_nik', $student->father_nik) }}" maxlength="30"></div>
                            <div class="col-md-3"><label class="form-label">Penghasilan/Bulan</label><input type="number" name="father_income" class="form-control" value="{{ old('father_income', $student->father_income) }}" step="1000" min="0"></div>
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0">Data Ibu</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Nama Ibu</label><input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $student->mother_name) }}"></div>
                            <div class="col-md-2"><label class="form-label">Tahun Lahir</label><input type="number" name="mother_birth_year" class="form-control" value="{{ old('mother_birth_year', $student->mother_birth_year) }}" min="1900" max="2030"></div>
                            <div class="col-md-3"><label class="form-label">Pendidikan</label><select name="mother_education" class="form-control"><option value="">—</option>@foreach(['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'] as $ed)<option value="{{ $ed }}" {{ old('mother_education', $student->mother_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Pekerjaan</label><input type="text" name="mother_occupation" class="form-control" value="{{ old('mother_occupation', $student->mother_occupation) }}"></div>
                            <div class="col-md-3"><label class="form-label">NIK Ibu</label><input type="text" name="mother_nik" class="form-control" value="{{ old('mother_nik', $student->mother_nik) }}" maxlength="30"></div>
                            <div class="col-md-3"><label class="form-label">Penghasilan/Bulan</label><input type="number" name="mother_income" class="form-control" value="{{ old('mother_income', $student->mother_income) }}" step="1000" min="0"></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Data Wali</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Nama Wali</label><input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $student->guardian_name) }}"></div>
                            <div class="col-md-2"><label class="form-label">Tahun Lahir</label><input type="number" name="guardian_birth_year" class="form-control" value="{{ old('guardian_birth_year', $student->guardian_birth_year) }}" min="1900" max="2030"></div>
                            <div class="col-md-3"><label class="form-label">Pendidikan</label><select name="guardian_education" class="form-control"><option value="">—</option>@foreach(['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'] as $ed)<option value="{{ $ed }}" {{ old('guardian_education', $student->guardian_education) === $ed ? 'selected' : '' }}>{{ $ed }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Pekerjaan</label><input type="text" name="guardian_occupation" class="form-control" value="{{ old('guardian_occupation', $student->guardian_occupation) }}"></div>
                            <div class="col-md-3"><label class="form-label">NIK Wali</label><input type="text" name="guardian_nik" class="form-control" value="{{ old('guardian_nik', $student->guardian_nik) }}" maxlength="30"></div>
                            <div class="col-md-3"><label class="form-label">Penghasilan/Bulan</label><input type="number" name="guardian_income" class="form-control" value="{{ old('guardian_income', $student->guardian_income) }}" step="1000" min="0"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 5: Pendaftaran --}}
            <div class="tab-pane fade" id="tab-pendaftaran" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Data Pendaftaran & Sekolah Asal</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Anak ke-</label><input type="number" name="child_number" class="form-control" value="{{ old('child_number', $student->child_number) }}" min="0"></div>
                            <div class="col-md-4"><label class="form-label">Asal Sekolah</label><input type="text" name="previous_school" class="form-control" value="{{ old('previous_school', $student->previous_school) }}"></div>
                            <div class="col-md-3"><label class="form-label">Tingkat Masuk</label>
                                <select name="entry_grade_level" class="form-control"><option value="">—</option>@for($i=1;$i<=12;$i++)<option value="{{ $i }}" {{ old('entry_grade_level', $student->entry_grade_level) == $i ? 'selected' : '' }}>Kelas {{ $i }}</option>@endfor</select>
                            </div>
                            <div class="col-md-2"><label class="form-label">Tanggal Masuk</label><input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', $student->entry_date?->format('Y-m-d')) }}"></div>
                            <div class="col-md-3"><label class="form-label">SKHUN</label><input type="text" name="skhun" class="form-control" value="{{ old('skhun', $student->skhun) }}" maxlength="50"></div>
                            <div class="col-md-3"><label class="form-label">No. UN/SKP</label><input type="text" name="ujian_national_number" class="form-control" value="{{ old('ujian_national_number', $student->ujian_national_number) }}" maxlength="50"></div>
                            <div class="col-md-3"><label class="form-label">No. Ijazah</label><input type="text" name="certificate_number" class="form-control" value="{{ old('certificate_number', $student->certificate_number) }}" maxlength="50"></div>
                            <div class="col-md-3"><label class="form-label">No. Akta Lahir</label><input type="text" name="birth_certificate_number" class="form-control" value="{{ old('birth_certificate_number', $student->birth_certificate_number) }}" maxlength="50"></div>
                            <div class="col-12"><hr><h6 class="mb-3">Program Bantuan</h6></div>
                            <div class="col-md-4">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_kps_receiver" value="1" {{ old('is_kps_receiver', $student->is_kps_receiver) ? 'checked' : '' }}><label class="form-check-label">Penerima KPS</label></div>
                                <input type="text" name="kps_number" class="form-control mt-1" value="{{ old('kps_number', $student->kps_number) }}" placeholder="No. KPS">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_kip_receiver" value="1" {{ old('is_kip_receiver', $student->is_kip_receiver) ? 'checked' : '' }}><label class="form-check-label">Penerima KIP</label></div>
                                <input type="text" name="kip_number" class="form-control mt-1" value="{{ old('kip_number', $student->kip_number) }}" placeholder="No. KIP">
                                <input type="text" name="kip_name" class="form-control mt-1" value="{{ old('kip_name', $student->kip_name) }}" placeholder="Nama di KIP">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_pip_eligible" value="1" {{ old('is_pip_eligible', $student->is_pip_eligible) ? 'checked' : '' }}><label class="form-check-label">Layak PIP</label></div>
                                <input type="text" name="kks_number" class="form-control mt-1" value="{{ old('kks_number', $student->kks_number) }}" placeholder="No. KKS">
                                <textarea name="pip_reason" class="form-control mt-1" rows="1" placeholder="Alasan PIP">{{ old('pip_reason', $student->pip_reason) }}</textarea>
                            </div>
                            <div class="col-md-3"><label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    @foreach(['active','inactive','graduate','dropped','transfer'] as $st)
                                        <option value="{{ $st }}" {{ old('status', $student->status) === $st ? 'selected' : '' }}>
                                            {{ ['active'=>'Aktif','inactive'=>'Nonaktif','graduate'=>'Lulus','dropped'=>'Dropout','transfer'=>'Pindah'][$st] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Tahun Lulus</label><input type="number" name="graduation_year" class="form-control" value="{{ old('graduation_year', $student->graduation_year) }}" min="1900" max="2100"></div>
                            <div class="col-md-3"><label class="form-label">Tanggal Lulus</label><input type="date" name="graduation_date" class="form-control" value="{{ old('graduation_date', $student->graduation_date?->format('Y-m-d')) }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 6: Bank --}}
            <div class="tab-pane fade" id="tab-bank" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Informasi Bank</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Nama Bank</label><input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $student->bank_name) }}"></div>
                            <div class="col-md-4"><label class="form-label">Cabang</label><input type="text" name="bank_cabang" class="form-control" value="{{ old('bank_cabang', $student->bank_cabang) }}"></div>
                            <div class="col-md-4"><label class="form-label">No. Rekening</label><input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $student->bank_account_number) }}"></div>
                            <div class="col-md-4"><label class="form-label">Atas Nama</label><input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $student->bank_account_name) }}"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('user.students.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Perubahan</button>
        </div>
    </form>
@endsection

@section('script')
<script>
const savedCityCode = '{{ old('city_code', $student->city_code ?? '') }}';
const savedDistrictCode = '{{ old('district_code', $student->district_code ?? '') }}';
const savedVillageCode = '{{ old('village_code', $student->village_code ?? '') }}';
const savedProvinceCode = '{{ old('province_code', $student->province_code ?? '') }}';

async function loadCities(provinceCode, targetId, selected) {
    const t = document.getElementById(targetId);
    if (!provinceCode || !t) return;
    t.disabled = true;
    try {
        const res = await fetch(`/api/wilayah/cities/${provinceCode}`);
        const json = await res.json();
        t.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';
        json.data.forEach(c => { t.innerHTML += `<option value="${c.code}" ${c.code===selected?'selected':''}>${c.name}</option>`; });
    } catch(e) { t.innerHTML = '<option value="">Gagal</option>'; }
    finally { t.disabled = false; }
}
async function loadDistricts(cityCode, targetId, selected) {
    const t = document.getElementById(targetId);
    if (!cityCode || !t) return;
    t.disabled = true;
    try {
        const res = await fetch(`/api/wilayah/districts/${cityCode}`);
        const json = await res.json();
        t.innerHTML = '<option value="">Pilih Kecamatan</option>';
        json.data.forEach(d => { t.innerHTML += `<option value="${d.code}" ${d.code===selected?'selected':''}>${d.name}</option>`; });
    } catch(e) { t.innerHTML = '<option value="">Gagal</option>'; }
    finally { t.disabled = false; }
}
async function loadVillages(districtCode, targetId, selected) {
    const t = document.getElementById(targetId);
    if (!districtCode || !t) return;
    t.disabled = true;
    try {
        const res = await fetch(`/api/wilayah/villages/${districtCode}`);
        const json = await res.json();
        t.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
        json.data.forEach(v => { t.innerHTML += `<option value="${v.code}" ${v.code===selected?'selected':''}>${v.name}</option>`; });
    } catch(e) { t.innerHTML = '<option value="">Gagal</option>'; }
    finally { t.disabled = false; }
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('province_code')?.addEventListener('change', function() {
        loadCities(this.value, 'city_code', '');
        ['district_code','village_code'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<option value="">Pilih ' + (id==='district_code'?'Kecamatan':'Desa') + '</option>';
        });
    });
    document.getElementById('city_code')?.addEventListener('change', function() {
        loadDistricts(this.value, 'district_code', '');
        const vl = document.getElementById('village_code');
        if (vl) vl.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
    });
    document.getElementById('district_code')?.addEventListener('change', function() {
        loadVillages(this.value, 'village_code', '');
    });
    if (savedProvinceCode) {
        loadCities(savedProvinceCode, 'city_code', savedCityCode).then(() => {
            if (savedCityCode) loadDistricts(savedCityCode, 'district_code', savedDistrictCode).then(() => {
                if (savedDistrictCode) loadVillages(savedDistrictCode, 'village_code', savedVillageCode);
            });
        });
    }
    const hash = window.location.hash;
    if (hash) { const tab = new bootstrap.Tab(document.querySelector(`[href='${hash}']`)); if (tab) tab.show(); }
});
</script>
@endsection
