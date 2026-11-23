@props(['current' => 'kisi', 'userId' => null, 'scope' => null, 'formal' => null])

@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();

    // `formal = true` menampilkan langkah paket & validasi; hanya relevan untuk
    // STS/SAS. Soal harian dan latihan langsung ke cetak, sehingga guru tidak
    // dipaksa membuat paket lebih dulu.
    $isFormal = (bool) $formal;

    $steps = [
        'kisi' => [
            'label' => 'Kisi-kisi',
            'desc' => 'Tujuan & cakupan materi',
            'route' => 'user.kisi-kisi-soal.index',
        ],
        'bank' => [
            'label' => 'Bank & input soal',
            'desc' => 'Tulis soal sesuai mapel SK',
            'route' => 'user.bank-soal.index',
        ],
        'cetak' => [
            'label' => 'Cetak naskah',
            'desc' => 'Atur tata letak, cetak PDF',
            'route' => 'user.soal-saya.cetak',
        ],
    ];

    if ($isFormal) {
        // Paket & validasi disisipkan sebelum cetak untuk STS/SAS.
        $steps = [
            'kisi' => $steps['kisi'],
            'bank' => $steps['bank'],
            'paket' => [
                'label' => 'Paket & validasi',
                'desc' => 'Khusus STS/SAS: validasi guru serumpun',
                'route' => 'user.paket-soal.index',
            ],
            'cetak' => [
                'label' => 'Cetak naskah',
                'desc' => 'Cetak paket yang sudah disetujui',
                'route' => 'user.paket-soal.index',
            ],
        ];
    }

    $keys = array_keys($steps);
    $currentIndex = array_search($current, $keys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $scopeSubjects = $scope['subjects'] ?? [];
    $scopeDecree = $scope['decrees'][0] ?? null;
@endphp

<div class="card border mb-3">
    <div class="card-body py-3">
        <ol class="soal-flow-steps list-unstyled mb-0">
            @foreach($steps as $key => $step)
                @php
                    $index = array_search($key, $keys, true);
                    $isCurrent = $key === $current;
                    $isDone = $index < $currentIndex;
                @endphp
                <li class="soal-flow-step {{ $isCurrent ? 'is-current' : ($isDone ? 'is-done' : 'is-todo') }}">
                    <a href="{{ route($step['route'], ['userId' => $userId]) }}" class="soal-flow-link">
                        <span class="soal-flow-marker">
                            @if($isDone)
                                <i class="ri-check-line"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="soal-flow-text">
                            <span class="soal-flow-label">{{ $step['label'] }}</span>
                            <span class="soal-flow-desc">{{ $step['desc'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>

        <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center gap-2">
            @if($isFormal)
                <span class="badge bg-info-subtle text-info fw-medium">
                    <i class="ri-shield-check-line me-1"></i>Alur STS/SAS: paket &amp; validasi wajib
                </span>
            @else
                <span class="badge bg-light text-muted border fw-medium">
                    <i class="ri-flashlight-line me-1"></i>Soal harian: langsung cetak, tanpa paket
                </span>
            @endif
            @if($scopeDecree)
                <span class="badge bg-success-subtle text-success fw-medium">
                    <i class="ri-shield-check-line me-1"></i>SK aktif: {{ $scopeDecree['number'] }}
                </span>
                <span class="text-muted small">
                    {{ $scope['subject_count'] }} mapel ·
                    {{ collect($scopeSubjects)->flatMap(fn ($s) => $s['classes'])->unique()->count() }} kelas
                </span>
            @else
                <span class="badge bg-danger-subtle text-danger fw-medium">
                    <i class="ri-error-warning-line me-1"></i>Belum ada penugasan pada SK aktif
                </span>
                <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="small">
                    Lihat SK &amp; keputusan
                </a>
            @endif
        </div>
    </div>
</div>

@once
    <style>
        .soal-flow-steps { display: flex; flex-wrap: wrap; }
        .soal-flow-step { position: relative; flex: 1 1 180px; min-width: 160px; }
        .soal-flow-step + .soal-flow-step::before {
            content: ""; position: absolute; top: 15px; left: 0;
            width: 10px; height: 1px; background: var(--bs-border-color);
        }
        .soal-flow-link {
            display: flex; align-items: flex-start; gap: .6rem;
            padding: .25rem .75rem .25rem 0; text-decoration: none; color: inherit;
        }
        .soal-flow-marker {
            display: inline-flex; align-items: center; justify-content: center;
            flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%;
            font-weight: 600; font-size: .8rem;
            border: 1.5px solid var(--bs-border-color);
            background: var(--bs-body-bg); color: var(--bs-secondary-color);
        }
        .soal-flow-text { display: flex; flex-direction: column; line-height: 1.25; padding-top: .2rem; }
        .soal-flow-label { font-weight: 600; font-size: .875rem; color: var(--bs-body-color); }
        .soal-flow-desc { font-size: .75rem; color: var(--bs-secondary-color); }
        .soal-flow-step.is-current .soal-flow-marker {
            background: var(--bs-primary); border-color: var(--bs-primary); color: #fff;
        }
        .soal-flow-step.is-current .soal-flow-label { color: var(--bs-primary); }
        .soal-flow-step.is-done .soal-flow-marker {
            background: var(--bs-success); border-color: var(--bs-success); color: #fff;
        }
        .soal-flow-step.is-todo .soal-flow-link:hover .soal-flow-label { text-decoration: underline; }

        @media (max-width: 767.98px) {
            .soal-flow-step { flex: 1 1 100%; }
            .soal-flow-step + .soal-flow-step::before { display: none; }
        }
    </style>
@endonce
