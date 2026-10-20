@extends('waka.master')
@section('title') Atur SK Tim Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi-sk.index') }}">Tim Supervisi & SK</a> @endslot
        @slot('title') {{ $sk->nomor }} @endslot
    @endcomponent

    <x-supervisi-workflow current="team" :year-id="$sk->academic_year_id" :semester="$sk->semester" :admin="true" />

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h4 class="mb-1">Tim Supervisi — {{ $sk->academicYear?->name }}</h4><p class="text-muted small mb-0">Semester {{ $sk->semester === 1 ? 'Ganjil' : 'Genap' }} · {{ $sk->nomor }}</p></div>
        <div class="d-flex gap-2">
            @if($sk->institution_decree_id)
                <a href="{{ route('user.institution-decrees.show', ['userId' => auth()->id(), 'id' => $sk->institution_decree_id]) }}" class="btn btn-outline-secondary">Database SK</a>
            @endif
            @if($sk->surat_keluar_id)
                <a href="{{ route('waka.surat-keluar.show', $sk->surat_keluar_id) }}" class="btn btn-outline-success">Surat Keluar</a>
            @endif
            <a href="{{ route('waka.supervisi-sk.index') }}" class="btn btn-light">Kembali</a>
            <a href="{{ route('waka.supervisi-sk.cetak', $sk->id) }}" target="_blank" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Cetak SK</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0">Identitas SK</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('waka.supervisi-sk.update', $sk->id) }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-5"><label class="form-label">Tahun Ajaran</label><select name="academic_year_id" class="form-select">@foreach($academicYears as $year)<option value="{{ $year->id }}" {{ (string) $sk->academic_year_id === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="1" {{ $sk->semester === 1 ? 'selected' : '' }}>Ganjil</option><option value="2" {{ $sk->semester === 2 ? 'selected' : '' }}>Genap</option></select></div>
                <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ $sk->tanggal?->format('Y-m-d') }}"></div>
                <div class="col-md-6"><label class="form-label">Nomor SK</label><input name="nomor" class="form-control" value="{{ $sk->nomor }}" required></div>
                <div class="col-12"><label class="form-label">Menimbang</label><textarea name="menimbang" class="form-control" rows="2">{{ $sk->menimbang }}</textarea></div>
                <div class="col-12"><label class="form-label">Mengingat</label><textarea name="mengingat" class="form-control" rows="2">{{ $sk->mengingat }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Identitas</button></div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0">Anggota Tim</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Jabatan</th><th>Peran Tim</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($sk->anggota as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $member->gtk?->name ?? '-' }}</td>
                            <td class="small">{{ $member->gtk?->latestEmployment?->jabatan ?? '-' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ ['ksp' => 'KSP', 'wksp' => 'WKSP', 'guru' => 'Perwakilan Guru'][$member->peran] ?? $member->peran }}</span></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('waka.supervisi-sk.member.destroy', ['id' => $sk->id, 'memberId' => $member->id]) }}" onsubmit="return confirm('Hapus anggota ini?')">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada anggota tim.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Tambah Anggota</h5></div>
        <div class="card-body">
            @php $existingMemberIds = $sk->anggota->pluck('gtk_profile_id')->all(); @endphp
            <form method="POST" action="{{ route('waka.supervisi-sk.member.bulk', $sk->id) }}">
                @csrf
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Peran Anggota <span class="text-danger">*</span></label>
                        <select name="peran" class="form-select" required>
                            <option value="guru" selected>Perwakilan Guru (bisa banyak)</option>
                            <option value="wksp">WKSP (satu orang)</option>
                            <option value="ksp">KSP (satu orang)</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Pilih GTK <span class="text-danger">*</span></label>
                        <div class="border rounded p-2" style="max-height:220px;overflow:auto">
                            <div class="row g-2">
                                @foreach($gtks as $gtk)
                                    @php $isExisting = in_array($gtk->id, $existingMemberIds, true); @endphp
                                    <div class="col-md-6">
                                        <label class="form-check mb-0 {{ $isExisting ? 'text-muted' : '' }}">
                                            <input class="form-check-input" type="checkbox" name="gtk_profile_ids[]" value="{{ $gtk->id }}" {{ $isExisting ? 'disabled' : '' }}>
                                            <span class="form-check-label">
                                                {{ $gtk->name }}
                                                @if($gtk->latestEmployment?->jabatan)<small class="text-muted d-block">{{ $gtk->latestEmployment->jabatan }}</small>@endif
                                                @if($isExisting)<small class="text-success">sudah menjadi anggota</small>@endif
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100"><i class="ri-add-line me-1"></i>Tambah Terpilih</button>
                    </div>
                </div>
                <div class="form-text">Centang beberapa guru sekaligus untuk menambahkan perwakilan guru secara massal. Untuk KSP/WKSP, pilih satu orang saja.</div>
            </form>

        </div>
    </div>
@endsection
