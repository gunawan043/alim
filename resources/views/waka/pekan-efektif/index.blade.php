@extends('waka.master')
@section('title', 'Pekan Efektif')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pekan Efektif @endslot
        @slot('title') Daftar Pekan Efektif @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Pekan Efektif</h4>
            <p class="text-muted mb-0 small">Turunan Kalender Pendidikan — minggu efektif, hari efektif, dan dasar alokasi JP.</p>
        </div>
        <a href="{{ route('waka.pekan-efektif.create') }}" class="btn btn-primary btn-sm">
            <i class="ri-add-line align-bottom me-1"></i> Tambah Pekan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Generate dari Kalender Pendidikan --}}
    <div class="card border border-primary-subtle">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <h5 class="card-title mb-1"><i class="ri-magic-line text-primary me-1"></i> Generate dari Kalender Pendidikan</h5>
                    <p class="text-muted small mb-0">
                        Pekan efektif, hari efektif, minggu libur, dan minggu ujian dihitung otomatis dari Kalender Pendidikan
                        (tipe <strong>Libur</strong>, <strong>Ujian</strong>, <strong>Kegiatan Sekolah</strong>).
                        Data lama pada tahun ajaran &amp; semester yang sama akan diganti.
                    </p>
                </div>
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('waka.pekan-efektif.generate') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select form-select-sm" required>
                                <option value="1" {{ (int) $selectedSemester === 1 ? 'selected' : '' }}>Ganjil</option>
                                <option value="2" {{ (int) $selectedSemester === 2 ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-refresh-line align-bottom me-1"></i> Generate Pekan Efektif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($ringkasan)
        @if(! ($ringkasan['is_persisted'] ?? false))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="ri-information-line me-1"></i>
                Pekan efektif untuk tahun ajaran &amp; semester ini belum digenerate.
                Ringkasan di bawah dihitung langsung dari Kalender Pendidikan ({{ $ringkasan['sumber'] ?? '-' }}).
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-xxl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Minggu Efektif</p>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-success">{{ $ringkasan['minggu_efektif'] }}</h4>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-calendar-check-line text-success"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Hari Efektif</p>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-primary">{{ $ringkasan['total_hari_efektif'] }}</h4>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-calendar-2-line text-primary"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Minggu Libur</p>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-danger">{{ $ringkasan['minggu_libur'] }}</h4>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-danger-subtle rounded fs-3"><i class="ri-calendar-close-line text-danger"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Minggu Ujian</p>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-warning">{{ $ringkasan['minggu_ujian'] }}</h4>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-draft-line text-warning"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('waka.pekan-efektif.index') }}" class="row g-3 align-items-end">
                <div class="col-xxl-4 col-sm-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        <option value="">— Semua —</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">— Semua —</option>
                        <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Jenis</label>
                    <select name="jenis" class="form-select">
                        <option value="">— Semua —</option>
                        @foreach(\App\Models\PekanEfektif::JENIS_OPTIONS as $value => $label)
                            <option value="{{ $value }}" {{ request('jenis') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                    <a href="{{ route('waka.pekan-efektif.index') }}" class="btn btn-light"><i class="ri-refresh-line align-bottom"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Daftar Pekan</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $pekanList->total() }} pekan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Thn. Ajaran</th>
                        <th class="text-center">Smt</th>
                        <th class="text-center">Minggu</th>
                        <th>Periode</th>
                        <th class="text-center">Hari Efektif</th>
                        <th class="text-center">Jenis</th>
                        <th>Keterangan</th>
                        <th class="text-end" style="width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pekanList as $p)
                        @php
                            $map = ['efektif' => 'success', 'libur' => 'danger', 'ujian' => 'warning', 'kegiatan_sekolah' => 'info', 'lainnya' => 'secondary'];
                            $cls = $map[$p->jenis] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>{{ $p->academicYear?->name ?? '-' }}</td>
                            <td class="text-center">{{ $p->semester == 1 ? 'Ganjil' : 'Genap' }}</td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $p->minggu_ke }}</span>
                                @if($p->is_generated)
                                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:0.6rem">Kaldik</span>
                                @endif
                            </td>
                            <td class="small">{{ $p->tanggal_mulai?->format('d/m/Y') }} – {{ $p->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $p->hari_efektif > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $p->hari_efektif }} hari
                                </span>
                            </td>
                            <td class="text-center"><span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $p->jenis_label }}</span></td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($p->keterangan, 40) ?: '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('waka.pekan-efektif.show', $p->id) }}" class="btn btn-sm btn-soft-primary" title="Lihat"><i class="ri-eye-line"></i></a>
                                <a href="{{ route('waka.pekan-efektif.edit', $p->id) }}" class="btn btn-sm btn-soft-warning" title="Edit"><i class="ri-pencil-line"></i></a>
                                <form action="{{ route('waka.pekan-efektif.destroy', $p->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-soft-danger btn-delete" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-calendar-2-line fs-1 d-block mb-2"></i>
                                    Belum ada data. Gunakan tombol <strong>Generate Pekan Efektif</strong> di atas untuk membuat dari Kalender Pendidikan.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pt-0">{{ $pekanList->links() }}</div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                Swal.fire({ title: 'Hapus pekan ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus' })
                    .then((r) => { if (r.isConfirmed) form.submit(); });
            });
        });
    </script>
@endsection
