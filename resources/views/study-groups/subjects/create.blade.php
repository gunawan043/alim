@extends('layouts.master')
@section('title') Tambah Mata Pelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a> @endslot
        @slot('li_3') <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}">{{ $studyGroup->full_name }}</a> @endslot
        @slot('title') Tambah Mata Pelajaran @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.study-groups.subjects.store', ['userId' => $userId, 'id' => $studyGroup->id]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Pilih Mata Pelajaran untuk {{ $studyGroup->full_name }}</h5>

                        @if($availableSubjects->isEmpty())
                            <div class="alert alert-warning">
                                Tidak ada mata pelajaran yang tersedia untuk ditambahkan.
                                Semua mata pelajaran aktif di sekolah ini sudah ter-assign ke rombel ini.
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($availableSubjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->code ? $subject->code . ' - ' : '' }}{{ $subject->name }}
                                            @if($subject->hours_per_week)
                                                ({{ $subject->hours_per_week }} jam/minggu)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jam Pelajaran per Minggu</label>
                                <input type="number" name="hours_per_week" min="1" max="40"
                                       class="form-control @error('hours_per_week') is-invalid @enderror"
                                       value="{{ old('hours_per_week', 2) }}">
                                @error('hours_per_week')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info">
                                <i class="mdi mdi-information-outline me-1"></i>
                                Setelah mata pelajaran ditambahkan ke rombel, sistem akan otomatis membuat
                                <strong>data nilai awal</strong> untuk seluruh siswa aktif di rombel ini.
                            </div>
                        @endif

                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
                               class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                            @if(!$availableSubjects->isEmpty())
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save"></i> Simpan
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
