@extends('cetak.layout')
@section('title', 'Jadwal Penggunaan Laboratorium')
@section('doc_title', 'JADWAL PENGGUNAAN LABORATORIUM IPA')
@section('doc_subtitle') {{ $lab->name }} &bull; Semester {{ $semester }} &bull; Tahun Pelajaran {{ $year?->name ?? '-' }} @endsection
@section('content')
@php $days = [1=>'SENIN',2=>'SELASA',3=>'RABU',4=>'KAMIS',5=>'JUMAT',6=>'SABTU']; @endphp
<table class="data">
    <thead><tr><th style="width:30px">NO.</th><th style="width:90px">JAM PELAJARAN</th><th style="width:80px">KELAS</th>@foreach($days as $day)<th>{{ $day }}</th>@endforeach</tr></thead>
    <tbody>
    @for($slot=1; $slot<=8; $slot++)
        <tr>
            <td class="center">{{ $slot }}</td>
            <td class="center">Jam ke-{{ $slot }}</td>
            <td class="center">{{ $slots->firstWhere('slot_index', $slot)?->studyGroup?->name ?? '' }}</td>
            @foreach($days as $dayNo => $dayName)
                @php $cell = $slots->first(fn ($s) => $s->day_of_week === $dayNo && $s->slot_index === $slot); @endphp
                <td class="center">
                    {{ $cell?->studyGroup?->name ?? '' }}
                    @if($cell?->subject)<br><span class="small">{{ $cell->subject->name }}</span>@endif
                </td>
            @endforeach
        </tr>
    @endfor
    </tbody>
</table>
<table style="width:100%;margin-top:20px" class="small"><tr><td style="width:50%;text-align:center">Guru Pembimbing<br><br><br><strong>{{ $lab->coordinator?->name ?? '..............................' }}</strong></td><td style="width:50%;text-align:center">Kepala Satuan Pendidikan<br><br><br><strong>{{ $school?->kepala_sekolah ?? '..............................' }}</strong></td></tr></table>
@endsection
