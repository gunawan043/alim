@extends('layouts.master')
@section('title') Catatan Guru @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Catatan Guru @endslot
        @slot('title') Catatan Guru @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Buku — full width atas --}}
    <div class="card border-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-md-auto">
                    <p class="mb-n1 btn btn-primary btn-sm" style="font-size: 10px;"><i class="ri-book-2-line me-1"></i>{{ $book['adminBook']->subject->name ?? '-' }}</p>
                    <p class="mb-n1 btn btn-secondary btn-sm"><i class="ri-team-line me-1"></i>{{ $book['adminBook']->studyGroup->name }}</p>
                    <p class="mb-n1 btn btn-dark btn-sm"><i class="ri-calendar-line me-1"></i>{{ ucfirst($book['adminBook']->semester) }}</p>
                    <p class="mb-n1 btn btn-warning btn-sm"><i class="ri-government-line me-1"></i>{{ $book['adminBook']->academicYear->name ?? '-' }}</p>
                </div>
                <div class="col-md d-flex justify-content-md-end">
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed py-2">
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Presensi Siswa</a>
                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Jurnal Pembelajaran</a>
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary">Catatan Guru</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('user.schools.guru-mapel.w6.store', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hari / Tanggal</label>
                                <input type="date" name="note_date" class="form-control"
                                       value="{{ old('note_date', now()->toDateString()) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan tentang Peserta Didik</label>
                            <textarea name="student_note" class="form-control" rows="4"
                                      placeholder="Catatan terkait perkembangan siswa, perilaku, hambatan belajar, dll...">{{ old('student_note') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan tentang Proses Pembelajaran</label>
                            <textarea name="learning_note" class="form-control" rows="4"
                                      placeholder="Catatan terkait metode mengajar, materi yang sulit, suasana kelas, refleksi diri, dll...">{{ old('learning_note') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Catatan
                            </button>
                        </div>
                    </form>

                    @if($catatan->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="text-muted fw-semibold mb-3"><i class="ri-history-line me-1"></i> Riwayat Catatan</h6>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th style="width:90px">Tanggal</th>
                                        <th>Catatan Peserta Didik</th>
                                        <th>Catatan Pembelajaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catatan as $i => $c)
                                        <tr>
                                            <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
                                            <td><strong>{{ $c->note_date->format('d/m/Y') }}</strong></td>
                                            <td class="text-muted">{{ Str::limit($c->student_note, 100) ?: '-' }}</td>
                                            <td class="text-muted">{{ Str::limit($c->learning_note, 100) ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-help-line me-1 text-secondary"></i> Petunjuk Pengisian</h6></div>
                <div class="card-body" style="font-size:.75rem;">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Isi <strong>tanggal</strong> dan kedua kolom catatan sesuai kebutuhan.</li>
                        <li class="mb-2">Catatan Peserta Didik untuk mencatat perkembangan, perilaku, dan hambatan belajar siswa.</li>
                        <li class="mb-2">Catatan Pembelajaran untuk mencatat metode mengajar, materi sulit, dan refleksi diri.</li>
                        <li>Klik <strong>Simpan Catatan</strong> untuk menyimpan. Riwayat catatan akan muncul di bawah.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
