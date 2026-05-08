@extends('layouts.master')
@section('title') {{ $subject->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}">Mata Pelajaran</a> @endslot
        @slot('title') {{ $subject->name }} @endslot
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
                <div class="card-header"><h5 class="mb-0">{{ $subject->name }}</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:180px;">Sekolah</th>
                            <td>
                                @if($subject->school)
                                    <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $subject->school_id]) }}">{{ $subject->school->name }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kode</th>
                            <td><code>{{ $subject->code }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Nama</th>
                            <td>{{ $subject->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kategori</th>
                            <td>
                                @if($subject->category === 'nasional')
                                    <span class="badge bg-info-subtle text-info">Nasional</span>
                                @elseif($subject->category === 'lokal')
                                    <span class="badge bg-warning-subtle text-warning">Lokal</span>
                                @else
                                    <span class="badge bg-purple-subtle text-purple">Muatan Lokal</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Jam Pelajaran / Minggu</th>
                            <td>{{ $subject->credit_hours }} JP</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Deskripsi</th>
                            <td>{{ $subject->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @if($subject->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Dibuat</th>
                            <td>{{ $subject->created_at->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Diperbarui</th>
                            <td>{{ $subject->updated_at->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.subjects.edit', ['userId' => $userId, 'id' => $subject->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection