@php
    $rowProfile = $model->curriculumProfile ?? null;
@endphp
@if($rowProfile)
    <span class="badge {{ $rowProfile->isKemenag() ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }} {{ $class ?? '' }}" title="{{ $rowProfile->name }}">
        {{ $rowProfile->isKemenag() ? 'KBC' : 'Merdeka' }}
    </span>
@else
    <span class="badge bg-secondary-subtle text-secondary {{ $class ?? '' }}" title="Dokumen belum memiliki profil">Belum ditandai</span>
@endif
