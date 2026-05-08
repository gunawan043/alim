@extends('layouts.master')
@section('title') Jurnal Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Jurnal Pembelajaran @endslot
        @slot('title') Jurnal Pembelajaran @endslot
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
                            <option value="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $book['adminBook']->id ? 'selected' : '' }}>
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
                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-primary">Jurnal Pembelajaran</a>
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}" class="btn btn-outline-secondary">Catatan Guru</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('user.schools.guru-mapel.w2.store', ['userId' => $userId, 'adminBookId' => $book['adminBook']->id]) }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Minggu ke-</label>
                                <input type="number" name="meeting_number" class="form-control"
                                       value="{{ old('meeting_number', ($journals->max('meeting_number') ?? 0) + 1) }}" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tanggal Pertemuan</label>
                                <input type="date" name="meeting_date" class="form-control"
                                       value="{{ old('meeting_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jam Masuk</label>
                                <input type="time" name="time_in" class="form-control" value="{{ old('time_in') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jam Pulang</label>
                                <input type="time" name="time_out" class="form-control" value="{{ old('time_out') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Materi Pembelajaran</label>
                            <textarea name="material" class="form-control" rows="4"
                                      placeholder="Tuliskan materi yang dibahas, metode mengajar, dan media yang digunakan...">{{ old('material') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="teacher_signature" name="teacher_signature"
                                       value="Terverifikasi" {{ old('teacher_signature') ? 'checked' : '' }}>
                                <label class="form-check-label" for="teacher_signature">
                                    Tanda tangan guru — saya sudah mengisi jurnal ini
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Jurnal
                            </button>
                        </div>
                    </form>

                    @if($journals->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="text-muted fw-semibold mb-3"><i class="ri-history-line me-1"></i> Riwayat Jurnal</h6>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th style="width:70px">Minggu</th>
                                        <th style="width:90px">Tanggal</th>
                                        <th style="width:120px">Jam</th>
                                        <th>Materi (preview)</th>
                                        <th style="width:110px">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journals as $i => $j)
                                        <tr>
                                            <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
                                            <td><strong>{{ $j->meeting_number }}</strong></td>
                                            <td>{{ $j->meeting_date->format('d/m/Y') }}</td>
                                            <td>{{ substr($j->time_in ?? '', 0, 5) ?: '-' }} - {{ substr($j->time_out ?? '', 0, 5) ?: '-' }}</td>
                                            <td class="text-muted">{{ Str::limit($j->material, 80) ?: '-' }}</td>
                                            <td>
                                                @if($j->teacher_signature)
                                                    <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Terverifikasi</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ri-edit-line me-1"></i>Draft</span>
                                                @endif
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

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-help-line me-1 text-secondary"></i> Petunjuk Pengisian</h6></div>
                <div class="card-body" style="font-size:.75rem;">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Isi <strong>Minggu ke-</strong> (nomor pertemuan), <strong>Tanggal</strong>, <strong>Jam Masuk</strong>, dan <strong>Jam Pulang</strong>.</li>
                        <li class="mb-2">Tulis <strong>materi pembelajaran</strong> yang dibahas lengkap dengan metode dan media yang digunakan.</li>
                        <li class="mb-2">Centang <strong>Tanda tangan guru</strong> setelah jurnal diisi untuk menandai terverifikasi.</li>
                        <li>Klik <strong>Simpan Jurnal</strong> untuk menyimpan data.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
