@extends('layouts.master')
@section('title', 'Kelompok Guru Lintas Satuan Pendidikan')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.rumpun.index', ['userId' => $userId]) }}">Master Rumpun</a> @endslot
        @slot('title') Kelompok Guru Lintas Satuan @endslot
    @endcomponent

    <div class="alert alert-info small">
        <i class="ri-information-line me-1"></i>
        Kolaborasi mapel yang sama <strong>lintas satuan pendidikan</strong> difasilitasi sebagai keanggotaan turunan dari
        tugas mengajar aktif (bukan jenis tugas tambahan baru). Koordinator Guru Umum <strong>tidak otomatis</strong>
        menjadi koordinator mata pelajaran lintas satuan — koordinasi rumpun tetap per satuan pendidikan.
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-share-forward-line text-primary me-1"></i>Kelompok Guru per Mata Pelajaran</h5>
            <p class="text-muted mb-0 small">Tahun ajaran aktif · dihitung dari penugasan mengajar (status aktif).</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-freeze mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mata Pelajaran</th>
                            <th class="text-center">Guru</th>
                            <th class="text-center">Satuan Pendidikan</th>
                            <th>Anggota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($crossGroups as $group)
                            <tr>
                                <td class="fw-medium">{{ $group['subject'] }}</td>
                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $group['teacher_count'] }}</span></td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $group['school_count'] }}</span></td>
                                <td class="small text-muted">
                                    @foreach($group['teachers'] as $teacher)
                                        {{ $teacher['name'] }}@if($teacher['school']) ({{ $teacher['school'] }})@endif{{ !$loop->last ? ' · ' : '' }}
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    Belum ada penugasan mengajar aktif pada tahun ajaran ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
