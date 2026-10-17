@extends('layouts.master')
@section('title') Buku Admin Guru @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Buku Admin Guru @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Buku Admin Guru</h5>
                            <p class="text-muted mb-0">Pilih mapel &amp; kelas untuk membuka buku administrasi.</p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" id="filterForm" class="row g-2">
                                <div class="col-auto">
                                    <select name="subject_id" class="form-control form-control-sm" onchange="document.getElementById('filterForm').submit()">
                                        <option value="">Semua Mapel</option>
                                        @foreach($subjects as $sub)
                                            <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select name="study_group_id" class="form-control form-control-sm" onchange="document.getElementById('filterForm').submit()">
                                        <option value="">Semua Kelas</option>
                                        @foreach($adminBooks->flatten()->pluck('studyGroup')->unique('id') as $sg)
                                            <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap mb-0" style="table-layout: fixed;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Mata Pelajaran</th>
                                    <th style="width:100px">Kelas</th>
                                    <th style="width:90px">Semester</th>
                                    <th style="width:120px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adminBooks->flatten()->filter(fn($b) => !request('subject_id') || $b->subject_id == request('subject_id'))->filter(fn($b) => !request('study_group_id') || $b->study_group_id == request('study_group_id')) as $i => $book)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="fw-medium">{{ $book->subject->name ?? '-' }}</span>
                                            @if($book->subject->code)
                                                <span class="text-muted ms-1">{{ $book->subject->code }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ $book->studyGroup->name }}</span></td>
                                        <td class="text-center"><span class="badge bg-{{ $book->semester == 'ganjil' ? 'info' : 'warning' }}-subtle text-{{ $book->semester == 'ganjil' ? 'info' : 'warning' }}">{{ ucfirst($book->semester) }}</span></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('user.schools.guru-mapel.wizard', ['userId' => $userId, 'adminBookId' => $book->id]) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="ri-folder-open-line"></i> Buka
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada mapel yang diampu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
