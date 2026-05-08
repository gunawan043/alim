@php
    $stages = [
        'submitted' => ['name' => 'Submitted', 'icon' => 'ri-file-copy-line', 'color' => 'primary'],
        'administrasi' => ['name' => 'Administrasi', 'icon' => 'ri-file-list-line', 'color' => 'info'],
        'tes' => ['name' => 'Tes', 'icon' => 'ri-pencil-line', 'color' => 'warning'],
        'wawancara' => ['name' => 'Wawancara', 'icon' => 'ri-discuss-line', 'color' => 'purple'],
        'offer' => ['name' => 'Offer', 'icon' => 'ri-mail-send-line', 'color' => 'success'],
        'hired' => ['name' => 'Hired', 'icon' => 'ri-user-star-line', 'color' => 'success'],
    ];

    $currentStage = 'submitted';
    if (in_array($application->status, ['diterima'])) {
        $currentStage = 'hired';
    } elseif (in_array($application->status, ['penawaran_kerja'])) {
        $currentStage = 'offer';
    } elseif (in_array($application->status, ['wawancara', 'lolos_wawancara'])) {
        $currentStage = 'wawancara';
    } elseif (in_array($application->status, ['tes_tertulis', 'lolos_tes'])) {
        $currentStage = 'tes';
    } elseif (in_array($application->status, ['seleksi_administrasi', 'lolos_administrasi'])) {
        $currentStage = 'administrasi';
    }
@endphp

<div class="d-flex justify-content-between">
    @foreach ($stages as $key => $stage)
        <div class="text-center" style="flex:1">
            <div class="mb-2">
                <div class="avatar-sm mx-auto">
                    <div class="avatar-title rounded-circle bg-{{ $stage['color'] }}-subtle text-{{ $stage['color'] }} {{ $currentStage == $key ? 'border border-3 border-' . $stage['color'] : '' }}">
                        <i class="{{ $stage['icon'] }} fs-18"></i>
                    </div>
                </div>
            </div>
            <h6 class="fs-13 mb-1">{{ $stage['name'] }}</h6>
            @if ($key == 'submitted')
                <small class="text-muted">{{ $application->created_at->format('d/m/Y') }}</small>
            @elseif($key == 'hired' && $application->status == 'diterima')
                <small class="text-muted">{{ $application->updated_at->format('d/m/Y') }}</small>
            @endif
        </div>
        @if (!$loop->last)
            <div class="align-self-center flex-grow-1 mx-2">
                <div class="progress" style="height: 4px;">
                    @php
                        $progress = 0;
                        if ($currentStage == 'hired') $progress = 100;
                        elseif ($currentStage == 'offer') $progress = 80;
                        elseif ($currentStage == 'wawancara') $progress = 60;
                        elseif ($currentStage == 'tes') $progress = 40;
                        elseif ($currentStage == 'administrasi') $progress = 20;
                    @endphp
                    <div class="progress-bar bg-{{ $stage['color'] }}" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @endif
    @endforeach
</div>