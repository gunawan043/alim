@extends('layouts.master')
@section('title') Wizard — Buku Admin Guru @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('title') Wizard @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Buku Admin Guru</h5>
                            <p class="text-muted mb-0">
                                <strong>{{ $book['adminBook']->subject->name ?? '-' }}</strong>
                                &mdash; {{ $book['adminBook']->studyGroup->name }}
                                &mdash; Semester {{ ucfirst($book['adminBook']->semester) }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" id="switchBook">
                                <select name="switch" class="form-control form-control-sm" onchange="document.getElementById('switchBook').submit()">
                                    <option value="">— Ganti Mapel / Kelas —</option>
                                    @foreach($books as $b)
                                        <option value="{{ $b->id }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                            {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>Wizard</th>
                                    <th>Deskripsi</th>
                                    <th style="width:120px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center">1</td>
                                    <td><strong>Presensi Siswa</strong></td>
                                    <td class="text-muted">Input absensi harian per pertemuan</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">2</td>
                                    <td><strong>Jurnal Pembelajaran</strong></td>
                                    <td class="text-muted">Catatan kegiatan &amp; materi mingguan</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">3</td>
                                    <td><strong>Nilai Sumatif</strong></td>
                                    <td class="text-muted">SH, STS, SAS &amp; nilai akhir semester</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">4</td>
                                    <td><strong>Asesmen Formatif</strong></td>
                                    <td class="text-muted">LKPD, Diskusi, Kuis &amp; Antarteman</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">5</td>
                                    <td><strong>Penghargaan Akademik</strong></td>
                                    <td class="text-muted">Jujur, Disiplin, Peduli, Adab &amp; lain</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">6</td>
                                    <td><strong>Catatan Guru</strong></td>
                                    <td class="text-muted">Catatan proses &amp; perkembangan didik</td>
                                    <td>
                                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-play-line me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
