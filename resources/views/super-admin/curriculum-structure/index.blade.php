@extends('layouts.master')
@section('title', 'Struktur Kurikulum')

@section('content')
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0"><i class="ri-layout-grid-line me-1"></i>Struktur Kurikulum Pondok</h5>
                <p class="text-muted mb-0 small">
                    Seeder wajib: tingkat, mapel pondok, dan alokasi jam per pekan. Satuan pendidikan hanya dapat
                    <strong>mengubah nama</strong> mapel pondok; <strong>Mapel Pemadatan</strong> diatur sendiri oleh satuan pendidikan.
                </p>
            </div>
            <form method="POST" action="{{ route('user.sa.curriculum-structure.apply', ['userId' => $userId]) }}"
                  onsubmit="return confirm('Terapkan ulang struktur kurikulum pondok ke seluruh satuan pendidikan?');">
                @csrf
                <button class="btn btn-success"><i class="ri-refresh-line me-1"></i>Terapkan Struktur (semua satuan)</button>
            </form>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Satuan Pendidikan</th>
                            <th class="text-center">Jenjang</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Mapel Pondok</th>
                            <th class="text-center">Mapel Pemadatan</th>
                            <th class="text-center">Baris Alokasi Jam</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schools as $row)
                            <tr>
                                <td class="fw-medium">{{ $row['school']?->name ?? '—' }}</td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ strtoupper($row['school']?->school_level ?? '-') }}</span></td>
                                <td class="text-center">{{ $row['levels'] }}</td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $row['pondok'] }}</span></td>
                                <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $row['pemadatan'] }}</span></td>
                                <td class="text-center">{{ $row['allocations'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Kelola Mapel</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
