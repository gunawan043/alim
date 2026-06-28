@extends('layouts.app')

@section('title', 'Jadwal KBM — ' . $studyGroup->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calendar-alt"></i>
            Jadwal KBM — {{ $studyGroup->full_name }}
        </h1>
        <div>
            <a href="{{ route('jadwal-kbm.edit', [$userId ?? auth()->id(), $studyGroup->id]) }}"
               class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('jadwal-kbm.index', [$userId ?? auth()->id()]) }}"
               class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Wali Kelas: {{ $studyGroup->homeroomTeacher?->name ?? '-' }}
                @if($activeAy)
                    | Tahun Ajaran: {{ $activeAy->name }}
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if($jadwals->isEmpty())
                <div class="alert alert-info">
                    Belum ada jadwal. <a href="{{ route('jadwal-kbm.generateIndex', [$userId ?? auth()->id()]) }}">Generate jadwal</a> untuk rombel ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Slot</th>
                                <th>Jam</th>
                                @foreach($days as $num => $name)
                                    <th>{{ $name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $maxSlot = $jadwals->flatten(1)->max('slot_index') ?? 0;
                            @endphp
                            @for($slot = 1; $slot <= $maxSlot; $slot++)
                                <tr>
                                    <td class="text-center"><strong>{{ $slot }}</strong></td>
                                    <td class="text-center text-muted small">
                                        @php
                                            $firstEntry = $jadwals->flatten(1)->firstWhere('slot_index', $slot);
                                        @endphp
                                        @if($firstEntry)
                                            {{ substr($firstEntry->start_time, 0, 5) }}–{{ substr($firstEntry->end_time, 0, 5) }}
                                        @endif
                                    </td>
                                    @foreach($days as $num => $name)
                                        @php
                                            $entry = $jadwals->get($num, collect())->firstWhere('slot_index', $slot);
                                        @endphp
                                        <td>
                                            @if($entry)
                                                <div class="font-weight-bold">{{ $entry->subject?->name ?? '-' }}</div>
                                                <div class="small text-muted">{{ $entry->teacher?->name ?? '-' }}</div>
                                                @if($entry->room)
                                                    <div class="small"><i class="fas fa-door-open"></i> {{ $entry->room }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
