@extends('waka.master')
@section('title') Tim Supervisi & SK @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi.index') }}">Supervisi</a> @endslot
        @slot('title') Tim Supervisi & SK @endslot
    @endcomponent

    <x-supervisi-workflow current="team" :year-id="$selectedYearId" :semester="'1'" :admin="true" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="ri-route-line me-1 text-primary"></i>Prosedur: KSP menetapkan tim melalui SK → WKSP menyiapkan jadwal &amp; instrumen → supervisi dilaksanakan → hasil dilaporkan ke Wadir Akademik dan Pengasuhan.</span>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSkModal"><i class="ri-add-line me-1"></i>Buat SK Tim</button>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0">Daftar SK Tim Supervisi</h5><p class="text-muted small mb-0">Tim terdiri dari KSP, WKSP, dan perwakilan guru.</p></div>
            <form method="GET" action="{{ route('waka.supervisi-sk.index') }}" class="d-flex gap-2">
                <select name="academic_year_id" class="form-select form-select-sm" style="min-width:200px">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (string) $selectedYearId === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Tampilkan</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tahun Ajaran</th><th>Semester</th><th>Nomor SK</th><th>Tanggal</th><th class="text-center">Anggota</th><th class="text-end" style="width:220px">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($sks as $sk)
                        <tr>
                            <td>{{ $sk->academicYear?->name ?? '-' }}</td>
                            <td>{{ $sk->semester === 1 ? 'Ganjil' : 'Genap' }}</td>
                            <td><span class="fw-semibold">{{ $sk->nomor }}</span></td>
                            <td>{{ $sk->tanggal?->translatedFormat('d F Y') ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $sk->anggota_count }} orang</span></td>
                            <td class="text-end">
                                <a href="{{ route('waka.supervisi-sk.edit', $sk->id) }}" class="btn btn-sm btn-soft-primary"><i class="ri-settings-3-line me-1"></i>Atur</a>
                                <a href="{{ route('waka.supervisi-sk.cetak', $sk->id) }}" target="_blank" class="btn btn-sm btn-soft-secondary"><i class="ri-printer-line me-1"></i>SK</a>
                                @if($sk->surat_keluar_id)
                                    <a href="{{ route('waka.surat-keluar.show', $sk->surat_keluar_id) }}" class="btn btn-sm btn-soft-success" title="Surat keluar terkait"><i class="ri-mail-line"></i></a>
                                @endif
                                <form action="{{ route('waka.supervisi-sk.destroy', $sk->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus SK ini?')">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" title="Belum ada SK tim supervisi" message="Buat SK lalu tambahkan KSP, WKSP, dan perwakilan guru." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('waka.supervisi-sk.store') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-add-line me-1"></i>Buat SK Tim Supervisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($academicYears as $year)<option value="{{ $year->id }}" {{ (string) $selectedYearId === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" class="form-select" required><option value="1">Ganjil</option><option value="2">Genap</option></select></div>
                            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                            <div class="col-12"><label class="form-label">Nomor SK <span class="text-danger">*</span></label><input type="text" name="nomor" class="form-control" placeholder="Contoh: 001/KSP/ALIM/X/2026" required></div>
                            <div class="col-12"><label class="form-label">Menimbang</label><textarea name="menimbang" class="form-control" rows="3" placeholder="Bahwa dalam upaya memperlancar penilaian kinerja guru…"></textarea></div>
                            <div class="col-12"><label class="form-label">Mengingat</label><textarea name="mengingat" class="form-control" rows="3" placeholder="UU No. 20 Tahun 2003; PP No. 19 Tahun 2015; Program Kerja; Kalender Pendidikan…"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan & Lanjut Atur Tim</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection
