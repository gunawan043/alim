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
@endsection
