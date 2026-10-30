@php
    $days = $matrixData['days'] ?? [];
    $masterDays = $matrixData['masterDays'] ?? [];
    $matrix = $matrixData['matrix'] ?? [];
    $teacherLoads = $matrixData['teacherLoads'] ?? collect();
    $teacherCodes = $matrixData['teacherCodes'] ?? collect();
    $subjectCodes = $matrixData['subjectCodes'] ?? collect();
    $conflictIds = $matrixData['conflictIds'] ?? collect();
    $unavailableIds = $matrixData['unavailableIds'] ?? collect();
    $editable = $editable ?? false;
@endphp

<div class="matrix-layout">
    <div class="matrix-schedule">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle schedule-matrix-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:78px">HARI</th>
                        <th style="width:58px" class="text-center">JAM KE</th>
                        <th style="width:112px" class="text-center">WAKTU</th>
                        @foreach($studyGroups as $studyGroup)
                            <th class="text-center" title="{{ $studyGroup->full_name ?? $studyGroup->name }}">
                                {{ $studyGroup->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day => $dayName)
                        @php $slots = $masterDays[$day] ?? []; $periodNumber = 0; @endphp
                        @forelse($slots as $slot)
                            @php
                                $isBreak = (bool) ($slot['is_break'] ?? false);
                                $start = str_replace(':', '.', substr((string) ($slot['start'] ?? ''), 0, 5));
                                $end = str_replace(':', '.', substr((string) ($slot['end'] ?? ''), 0, 5));
                            @endphp
                            <tr class="{{ $isBreak ? 'table-secondary' : '' }} {{ $loop->first ? 'matrix-day-start' : '' }}">
                                @if($loop->first)
                                    <td rowspan="{{ count($slots) }}" class="fw-semibold text-nowrap matrix-day-name">{{ $dayName }}</td>
                                @endif
                                <td class="text-center">
                                    @if(! $isBreak)
                                        @php $periodNumber++; @endphp
                                        {{ $periodNumber }}
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">{{ $start }}–{{ $end }}</td>
                                @if($isBreak)
                                    <td colspan="{{ max(1, $studyGroups->count()) }}" class="text-center text-muted small">
                                        {{ $slot['label'] ?? 'ISTIRAHAT' }}
                                    </td>
                                @else
                                    @foreach($studyGroups as $studyGroup)
                                        @php
                                            $entry = $matrix[$day][$slot['slot']][$studyGroup->id] ?? null;
                                            $entryId = $entry?->id;
                                            $isConflict = $entryId && isset($conflictIds[$entryId]);
                                            $isUnavailable = $entryId && isset($unavailableIds[$entryId]);
                                        @endphp
                                        <td class="small text-center matrix-cell {{ $isConflict ? 'matrix-conflict' : '' }} {{ $isUnavailable ? 'matrix-unavailable' : '' }}">
                                            @if($editable)
                                                <button type="button"
                                                    class="matrix-cell-button"
                                                    data-matrix-cell="1"
                                                    data-entry-id="{{ $entryId }}"
                                                    data-group-id="{{ $studyGroup->id }}"
                                                    data-group-name="{{ $studyGroup->name }}"
                                                    data-day="{{ $day }}"
                                                    data-slot="{{ $slot['slot'] }}"
                                                    data-subject-id="{{ $entry?->subject_id }}"
                                                    data-teacher-id="{{ $entry?->teacher_id }}"
                                                    data-room="{{ $entry?->room }}"
                                                    title="{{ $entry ? 'Klik untuk mengubah jadwal' : 'Klik untuk menambah jadwal' }}">
                                                    @if($entry)
                                                        <span class="subject-code">{{ $entry->subject?->code ?: ($entry->subject?->name ?? '-') }}</span>
                                                        @if($entry->teacher_id)
                                                            <span class="teacher-code">{{ $teacherCodes[(string) $entry->teacher_id] ?? '' }}</span>
                                                        @endif
                                                    @else
                                                        <span class="matrix-cell-empty">+</span>
                                                    @endif
                                                </button>
                                            @elseif($entry)
                                                <span class="subject-code" title="{{ $entry->subject?->name }}">
                                                    {{ $entry->subject?->code ?: ($entry->subject?->name ?? '-') }}
                                                </span>
                                                @if($entry->teacher_id)
                                                    <span class="teacher-code" title="{{ $entry->teacher?->name }}">
                                                        {{ $teacherCodes[(string) $entry->teacher_id] ?? '' }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="fw-semibold">{{ $dayName }}</td>
                                <td colspan="{{ 2 + max(1, $studyGroups->count()) }}" class="text-muted small">
                                    Belum ada master jam untuk hari ini.
                                </td>
                            </tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="matrix-teachers">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:42px" class="text-center">NO</th>
                        <th style="width:48px" class="text-center">KODE</th>
                        <th>NAMA GURU</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherLoads as $load)
                        <tr>
                            <td class="text-center">{{ $load['no'] }}</td>
                            <td class="text-center fw-semibold">{{ $load['code'] }}</td>
                            <td class="small fw-semibold">{{ $load['teacher']?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted small py-4">
                                Belum ada guru yang terjadwal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-responsive mt-2">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:78px">KODE MAPEL</th>
                        <th>NAMA MAPEL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjectCodes as $subject)
                        <tr>
                            <td class="text-center fw-semibold">{{ $subject['code'] }}</td>
                            <td class="small">{{ $subject['name'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted small py-3">Belum ada mapel terjadwal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
