@extends('layouts.app')

@section('title', 'Edit Jadwal KBM — ' . $studyGroup->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i>
            Edit Jadwal — {{ $studyGroup->full_name }}
        </h1>
        <a href="{{ route('jadwal-kbm.show', [$userId ?? auth()->id(), $studyGroup->id]) }}"
           class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('jadwal-kbm.update', [$userId ?? auth()->id(), $studyGroup->id]) }}">
        @csrf
        @method('PUT')

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-triangle"></i>
                    Slot yang bentrok akan ditolak otomatis oleh sistem (guru/rombel tidak boleh mengajar 2 tempat di jam sama).
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Hari / Slot</th>
                                <th>Mapel</th>
                                <th>Guru</th>
                                <th>Ruang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jadwals as $jadwal)
                                <tr>
                                    <td>
                                        <strong>{{ $days[$jadwal->day_of_week] ?? '-' }}</strong>
                                        | Slot {{ $jadwal->slot_index }}
                                        <br><small class="text-muted">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</small>
                                        <input type="hidden" name="entries[{{ $loop->index }}][id]" value="{{ $jadwal->id }}">
                                        <input type="hidden" name="entries[{{ $loop->index }}][day_of_week]" value="{{ $jadwal->day_of_week }}">
                                        <input type="hidden" name="entries[{{ $loop->index }}][slot_index]" value="{{ $jadwal->slot_index }}">
                                    </td>
                                    <td>
                                        <select name="entries[{{ $loop->index }}][subject_id]" class="form-control form-control-sm" required>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}" {{ $jadwal->subject_id == $subject->id ? 'selected' : '' }}>
                                                    {{ $subject->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="entries[{{ $loop->index }}][teacher_id]" class="form-control form-control-sm">
                                            <option value="">— Kosong —</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" {{ $jadwal->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                    {{ $teacher->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="entries[{{ $loop->index }}][room]" class="form-control form-control-sm"
                                               value="{{ $jadwal->room }}" maxlength="50">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Belum ada jadwal. <a href="{{ route('jadwal-kbm.generateIndex', [$userId ?? auth()->id()]) }}">Generate dulu</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" {{ $jadwals->isEmpty() ? 'disabled' : '' }}>
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
