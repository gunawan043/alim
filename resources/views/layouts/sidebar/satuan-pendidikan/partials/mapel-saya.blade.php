@php
    $mapelSayaBooks = $mapelSayaBooks ?? collect();
    $mapelSayaId = $mapelSayaId ?? 'sp_guru_books';
    $mapelSayaUserId = $mapelSayaUserId ?? ($userId ?? auth()->id());
    $mapelSayaActive = isActiveAny($currentRoute ?? '', [
        'user.schools.guru-mapel.wizard',
        'user.schools.guru-mapel.w1',
        'user.schools.guru-mapel.w2',
        'user.schools.guru-mapel.w3',
        'user.schools.guru-mapel.w4',
        'user.schools.guru-mapel.w5',
        'user.schools.guru-mapel.w6',
    ]);
@endphp

@if($mapelSayaBooks->isNotEmpty())
    <li class="nav-item">
        <a class="nav-link menu-link{{ $mapelSayaActive ? ' active' : '' }}"
           href="#{{ $mapelSayaId }}" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $mapelSayaActive ? 'true' : 'false' }}"
           aria-controls="{{ $mapelSayaId }}">
            <i class="ri-booklet-line"></i>
            <span>Mapel Saya <span class="badge bg-primary-subtle text-primary ms-1">{{ $mapelSayaBooks->count() }}</span></span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $mapelSayaActive ? ' show' : '' }}" id="{{ $mapelSayaId }}">
            <ul class="nav nav-sm flex-column">
                @foreach($mapelSayaBooks as $book)
                    <li class="nav-item">
                        <div class="d-flex align-items-center">
                            <a class="nav-link flex-grow-1 text-truncate{{ (string) request()->route('adminBookId') === (string) $book->id ? ' active' : '' }}"
                               href="{{ route('user.schools.guru-mapel.w3', ['userId' => $mapelSayaUserId, 'adminBookId' => $book->id]) }}"
                               title="Input nilai {{ $book->subject?->name }} — {{ $book->studyGroup?->name }}"
                               style="max-width: 190px;">
                                {{ $book->subject?->name ?? 'Mapel' }} · {{ $book->studyGroup?->name ?? '-' }}
                            </a>
                            <a class="nav-link px-2 flex-shrink-0"
                               href="{{ route('user.kurikulum.perangkat.index', ['userId' => $mapelSayaUserId, 'subject_id' => $book->subject_id, 'study_group_id' => $book->study_group_id]) }}"
                               title="Perangkat ajar {{ $book->subject?->name }} — {{ $book->studyGroup?->name }}"
                               aria-label="Perangkat ajar {{ $book->subject?->name }}">
                                <i class="ri-file-text-line"></i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </li>
@endif
