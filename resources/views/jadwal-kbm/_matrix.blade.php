@php
    $days = $matrixData['days'] ?? [];
    $masterDays = $matrixData['masterDays'] ?? [];
    $matrix = $matrixData['matrix'] ?? [];
    $teacherLoads = $matrixData['teacherLoads'] ?? collect();
    $teacherCodes = $matrixData['teacherCodes'] ?? collect();
    $compactTeacherLoads = $compactTeacherLoads ?? true;
    $lessonSequence = 0;
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
                        <th style="width:62px" class="text-center">JML JAM</th>
                        @foreach($studyGroups as $studyGroup)
                            <th class="text-center" title="{{ $studyGroup->full_name ?? $studyGroup->name }}">
                                {{ $studyGroup->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day => $dayName)
                        @php $slots = $masterDays[$day] ?? []; @endphp
                        @forelse($slots as $slot)
                            @php
                                $isBreak = (bool) ($slot['is_break'] ?? false);
                                $start = str_replace(':', '.', substr((string) ($slot['start'] ?? ''), 0, 5));
                                $end = str_replace(':', '.', substr((string) ($slot['end'] ?? ''), 0, 5));
                            @endphp
                            <tr class="{{ $isBreak ? 'table-secondary' : '' }}">
                                @if($loop->first)
                                    <td rowspan="{{ count($slots) }}" class="fw-semibold text-nowrap">{{ $dayName }}</td>
                                @endif
                                <td class="text-center">{{ $isBreak ? '' : $slot['slot'] }}</td>
                                <td class="text-center text-nowrap">{{ $start }}–{{ $end }}</td>
                                <td class="text-center">
                                    @if(! $isBreak)
                                        @php $lessonSequence++; @endphp
                                        {{ $lessonSequence }}
                                    @endif
                                </td>
                                @foreach($studyGroups as $studyGroup)
                                    @if($isBreak)
                                        <td class="text-center text-muted small">{{ $slot['label'] ?? 'ISTIRAHAT' }}</td>
                                    @else
                                        @php $entry = $matrix[$day][$slot['slot']][$studyGroup->id] ?? null; @endphp
                                        <td class="small">
                                            @if($entry)
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
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td class="fw-semibold">{{ $dayName }}</td>
                                <td colspan="{{ 3 + $studyGroups->count() }}" class="text-muted small">
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
                        <th>NAMA / BEBAN MENGAJAR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherLoads as $load)
                        <tr>
                            <td class="text-center">{{ $load['no'] }}</td>
                            <td class="text-center fw-semibold">{{ $load['code'] }}</td>
                            <td class="small">
                                @if($compactTeacherLoads)
                                    <details class="teacher-load">
                                        <summary>
                                            <span class="fw-semibold">{{ $load['teacher']?->name ?? '-' }}</span>
                                            <span class="teacher-load-summary">
                                                {{ count($load['subjects']) }} mapel · {{ $load['total_hours'] }} JP
                                            </span>
                                        </summary>
                                        <div class="teacher-load-detail">
                                            @foreach($load['subjects'] as $subject)
                                                <div class="teacher-subject">
                                                    <span>
                                                        {{ $subject['name'] }}
                                                        @if(! empty($subject['classes']))
                                                            <span class="text-muted">({{ implode(', ', $subject['classes']) }})</span>
                                                        @endif
                                                    </span>
                                                    <span class="teacher-hours">{{ $subject['hours'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <div class="fw-semibold">{{ $load['teacher']?->name ?? '-' }}</div>
                                    @foreach($load['subjects'] as $subject)
                                        <div class="teacher-subject">
                                            <span>
                                                {{ $subject['name'] }}
                                                @if(! empty($subject['classes']))
                                                    <span class="text-muted">({{ implode(', ', $subject['classes']) }})</span>
                                                @endif
                                            </span>
                                            <span class="teacher-hours">{{ $subject['hours'] }}</span>
                                        </div>
                                    @endforeach
                                    <div class="teacher-total">Total: {{ $load['total_hours'] }} JP</div>
                                @endif
                            </td>
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
    </div>
</div>
