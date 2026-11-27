@extends('layouts.master')
@section('title') Format Penilaian Tahfidz @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Penilaian @endslot
        @slot('title') Format Penilaian Mapel @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="text-muted mb-0 small">
                Format penilaian menentukan cara sebuah mapel dinilai:
                <strong>Umum</strong> memakai RS / STS / SAS, sedangkan
                <strong>Tahfidz</strong> memakai Ziyadah / Rabt / STS / SAS.
                Penanda ini dipakai bersama oleh rapor, leger, dan penjadwalan, sehingga
                satu mapel tidak mungkin dinilai dengan dua format berbeda.
                Perubahan di sini <strong>tidak mengubah nilai yang sudah tersimpan</strong>.
            </p>
        </div>
    </div>

    {{-- Bobot NR Final tahfidz — milik departemen, guru tidak dapat mengubah --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-1">Bobot NR Final Tahfidz</h5>
            <p class="text-muted mb-0 small">
                Bobot ini dipakai seluruh halaqah di sekolah ini dan tidak dapat diubah guru.
                Semester <strong>ganjil</strong> tidak mengenal SAS, sehingga bobot SAS bernilai 0.
                {{ $weightSaved ? 'Setelan tersimpan untuk tahun ajaran ini.' : 'Belum pernah diatur — angka di bawah masih bawaan sistem.' }}
            </p>
        </div>
        <form method="POST" action="{{ route('user.tahfidz.format.weights', ['userId' => $userId]) }}" id="thfWeightForm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label mb-1">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select form-select-sm"
                                onchange="window.location = '{{ route('user.tahfidz.format.index', ['userId' => $userId]) }}?academic_year_id=' + this.value">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" @selected($ay->id === $academicYearId)>
                                    {{ $ay->name }} — {{ ucfirst($ay->semester) }}{{ $ay->is_active ? ' (aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:180px;">Kolom</th>
                                <th class="text-center">Semester Ganjil<br><small class="fw-normal text-muted">tanpa SAS</small></th>
                                <th class="text-center">Semester Genap<br><small class="fw-normal text-muted">dengan SAS</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($weightColumns as $key)
                                <tr>
                                    <td class="fw-semibold">{{ $weightLabels[$key] ?? strtoupper($key) }}</td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm mx-auto" style="max-width:150px;">
                                            <input type="number" name="ganjil[{{ $key }}]" class="form-control text-center thf-weight"
                                                   data-set="ganjil" min="0" max="100" step="0.5"
                                                   value="{{ $key === 'sas' ? 0 : rtrim(rtrim(number_format((float) ($weightGanjil[$key] ?? 0), 2, '.', ''), '0'), '.') }}"
                                                   @if($key === 'sas') readonly title="Semester ganjil tidak mengenal SAS" @endif>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm mx-auto" style="max-width:150px;">
                                            <input type="number" name="genap[{{ $key }}]" class="form-control text-center thf-weight"
                                                   data-set="genap" min="0" max="100" step="0.5"
                                                   value="{{ rtrim(rtrim(number_format((float) ($weightGenap[$key] ?? 0), 2, '.', ''), '0'), '.') }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="fw-semibold">Total</td>
                                <td class="text-center"><span class="badge bg-secondary" id="thf-total-ganjil">—</span></td>
                                <td class="text-center"><span class="badge bg-secondary" id="thf-total-genap">—</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-top-dashed d-flex justify-content-end gap-2">
                <span class="text-muted me-auto align-self-center small">
                    Total setiap semester harus 100%. Menyimpan akan menghitung ulang NR Final seluruh halaqah di tahun ajaran ini.
                </span>
                @if($canEditWeights)
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ri-save-line me-1"></i>Simpan Bobot
                    </button>
                @else
                    <span class="badge bg-warning-subtle text-warning align-self-center">
                        <i class="ri-lock-line me-1"></i>Hanya Departemen Tahfidz yang dapat mengubah bobot
                    </span>
                @endif
            </div>
        </form>
    </div>

    @if(count($suggestedSubjects) > 0)
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1">
                <i class="ri-alert-line me-1"></i>
                {{ count($suggestedSubjects) }} mapel berformat Umum memiliki nama yang mirip tahfidz
            </div>
            <div class="small">
                Nama mapel tidak digunakan otomatis untuk menebak format. Periksa daftar di bawah —
                jika memang tahfidz, ubah formatnya agar nilainya dinilai dengan kolom yang benar.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('user.tahfidz.format.update', ['userId' => $userId]) }}">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-1">Mapel Berformat Tahfidz</h5>
                <p class="text-muted mb-0 small">Dinilai dengan Ziyadah, Rabt, STS, dan SAS.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mapel</th>
                            <th style="width:120px;">Kode</th>
                            <th>Rumpun</th>
                            @if(!$schoolName)<th>Sekolah</th>@endif
                            <th style="width:180px;">Format</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahfidzSubjects as $subject)
                            <tr>
                                <td class="fw-semibold">{{ $subject->name }}</td>
                                <td><code>{{ $subject->code }}</code></td>
                                <td class="text-muted small">{{ $subject->subjectGroup?->name ?? '—' }}</td>
                                @if(!$schoolName)<td class="small text-muted">{{ $subject->school?->name ?? '—' }}</td>@endif
                                <td>
                                    <select name="formats[{{ $subject->id }}]" class="form-select form-select-sm">
                                        <option value="tahfidz" selected>Tahfidz</option>
                                        <option value="umum">Umum</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $schoolName ? 4 : 5 }}" class="text-center text-muted py-4">
                                    Belum ada mapel berformat tahfidz.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(count($suggestedSubjects) > 0)
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-1">Diduga Tahfidz — Perlu Dikonfirmasi</h5>
                    <p class="text-muted mb-0 small">
                        Nama mapel ini mirip ejaan tahfidz. Saat ini masih dinilai dengan format Umum.
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mapel</th>
                                <th style="width:120px;">Kode</th>
                                <th>Rumpun</th>
                                @if(!$schoolName)<th>Sekolah</th>@endif
                                <th style="width:180px;">Format</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suggestedSubjects as $subject)
                                <tr>
                                    <td class="fw-semibold">{{ $subject->name }}</td>
                                    <td><code>{{ $subject->code }}</code></td>
                                    <td class="text-muted small">{{ $subject->subjectGroup?->name ?? '—' }}</td>
                                    @if(!$schoolName)<td class="small text-muted">{{ $subject->school?->name ?? '—' }}</td>@endif
                                    <td>
                                        <select name="formats[{{ $subject->id }}]" class="form-select form-select-sm">
                                            <option value="umum" selected>Umum</option>
                                            <option value="tahfidz">Tahfidz</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i> Simpan Format Penilaian
            </button>
        </div>
    </form>

    @push('script')
    <script>
    (function () {
        function total(set) {
            var sum = 0;
            document.querySelectorAll('.thf-weight[data-set="' + set + '"]').forEach(function (el) {
                sum += parseFloat(el.value) || 0;
            });
            return Math.round(sum * 10) / 10;
        }

        function render() {
            ['ganjil', 'genap'].forEach(function (set) {
                var el = document.getElementById('thf-total-' + set);
                if (! el) return;
                var t = total(set);
                el.textContent = 'Total: ' + t + '%';
                el.className = 'badge ' + (Math.abs(t - 100) < 0.01 ? 'bg-success' : 'bg-danger');
            });
        }

        document.querySelectorAll('.thf-weight').forEach(function (el) {
            el.addEventListener('input', render);
        });
        render();
    })();
    </script>
    @endpush
@endsection
