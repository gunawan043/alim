@include('user.laporan.pdf._style')

<div class="kop">
    <h3>{{ $school->name ?? 'Satuan Pendidikan' }}</h3>
    <p>
        @if($school?->address){{ $school->address }}@endif
        @if($school?->phone) · Telp. {{ $school->phone }}@endif
        @if($school?->email) · {{ $school->email }}@endif
    </p>
</div>

<h2>{{ $title ?? 'Laporan' }}</h2>
@if(!empty($filterText))
    <div class="filter">{{ $filterText }}</div>
@endif
