@extends('layouts.master')
@section('title') Pengaturan JP Tugas Tambahan — {{ $workUnit->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Kerja @endslot
        @slot('li_2') {{ $workUnit->name }} @endslot
        @slot('title') Pengaturan JP Tugas Tambahan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">JP Tugas Tambahan</h5>
                <p class="text-muted mb-0">Satuan kerja: <strong>{{ $workUnit->name }}</strong></p>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2">
                Nilai JP di sini menjadi nilai awal saat tugas tambahan dipilih pada halaman SK. Nilai pada tugas yang sudah ada tetap dapat diubah dari halaman SK.
            </div>

            <form method="POST" action="{{ route('user.schools.satuan-kerja.task-hours.update', ['userId' => $userId, 'workUnitId' => $workUnitId]) }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">No</th>
                                <th>Tugas Tambahan</th>
                                <th>Kode</th>
                                <th style="width:180px">JP / Minggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taskTypes as $type)
                                @php
                                    $defaultHours = $hours[$type->id] ?? ($type->kode === 'wali-kelas' ? 12 : 3);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-medium">{{ $type->nama }}</td>
                                    <td><code>{{ $type->kode }}</code></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="hours[{{ $type->id }}]" class="form-control"
                                                   value="{{ old('hours.'.$type->id, $defaultHours) }}" min="0" max="40" step="1">
                                            <span class="input-group-text">JP</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada master tugas tambahan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ url()->previous() }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Simpan Pengaturan JP
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
