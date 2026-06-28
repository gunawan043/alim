@extends('layouts.master')
@section('title') Bank Soal - Detail @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">Bank Soal</a> @endslot
        @slot('title') {{ $bank->nama }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Detail Bank Soal</h5>
                    <div>
                        <a href="{{ route('user.bank-soal.edit', ['userId' => $userId, 'id' => $bank->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="ri-pencil-line me-1"></i> Edit
                        </a>
                        <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}"
                           class="btn btn-light btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:200px">Nama Bank Soal</td>
                            <td><strong>{{ $bank->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mata Pelajaran</td>
                            <td>{{ $bank->subject?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Fase</td>
                            <td>{{ $bank->fase ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Deskripsi</td>
                            <td>{{ $bank->deskripsi ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jenis Soal</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $bank->jenis_soal }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tingkat Kesulitan</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ ucfirst($bank->tingkat_kesulitan_target) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jangkauan</td>
                            <td>
                                @if($bank->shared_scope === 'private')
                                    <span class="badge bg-danger-subtle text-danger">Privat</span>
                                @elseif($bank->shared_scope === 'internal_school')
                                    <span class="badge bg-warning-subtle text-warning">Internal Sekolah</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Publik</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pemilik</td>
                            <td>{{ $bank->owner?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dibuat Oleh</td>
                            <td>{{ $bank->creator?->name ?? '-' }} · {{ $bank->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                        @if($bank->tujuanPembelajaran->isNotEmpty())
                        <tr>
                            <td class="text-muted">Tujuan Pembelajaran</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($bank->tujuanPembelajaran as $tp)
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $tp->nama }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistik</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Soal</span>
                            <strong>{{ $bank->soal->count() }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Disetujui (approved)</span>
                            <strong class="text-success">{{ $bank->soal_approved_count }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Draft</span>
                            <strong class="text-warning">{{ $bank->soal->where('status', 'draft')->count() }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Soal List --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Daftar Soal</h5>
                    <a href="{{ route('user.soal.create', ['userId' => $userId, 'bankId' => $bank->id]) }}"
                       class="btn btn-success btn-sm">
                        <i class="ri-add-line me-1"></i> Tambah Soal
                    </a>
                </div>
                <div class="card-body">
                    @if($bank->soal->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">Belum ada soal dalam bank ini.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th style="width:60px">#</th>
                                        <th>Jenis Soal</th>
                                        <th>Pertanyaan (singkat)</th>
                                        <th>Bobot</th>
                                        <th>Status</th>
                                        <th style="width:120px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bank->soal as $i => $soal)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $soal->tipe_soal ?? '-' }}</span></td>
                                            <td style="max-width:300px">{{ Str::limit(strip_tags($soal->pertanyaan ?? ''), 60) }}</td>
                                            <td>{{ $soal->bobot_default ?? '-' }}</td>
                                            <td>
                                                @if($soal->status === 'approved')
                                                    <span class="badge bg-success">Disetujui</span>
                                                @elseif($soal->status === 'draft')
                                                    <span class="badge bg-warning">Draft</span>
                                                @else
                                                    <span class="badge bg-info">{{ $soal->status ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#soalDetailModal{{ $loop->iteration }}">
                                                    <i class="ri-eye-line"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
