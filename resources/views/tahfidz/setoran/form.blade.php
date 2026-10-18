@extends('layouts.master')
@section('title', isset($setoran) ? 'Edit Setoran' : 'Input Setoran')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isEdit = isset($setoran);
        $action = $isEdit ? route('user.tahfidz.setoran.update', ['userId'=>$userId,'id'=>$setoran->id]) : route('user.tahfidz.setoran.store', ['userId'=>$userId]);
        $val = fn ($field, $default = null) => old($field, $isEdit ? ($setoran->{$field} ?? $default) : $default);
        $createUrl = route('user.tahfidz.setoran.create', ['userId'=>$userId]);
    @endphp
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId]) }}">Setoran</a> @endslot @slot('li_2') {{ $isEdit ? 'Edit' : 'Input' }} @endslot @slot('title') Setoran Tahfizh @endslot @endcomponent

    <form method="POST" action="{{ $action }}">
        @csrf @if($isEdit) @method('PUT') @endif
        @if($isEdit && request('return_to'))<input type="hidden" name="return_to" value="{{ request('return_to') }}">@endif
        <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Data Setoran</h5></div><div class="card-body"><div class="row g-3">
            <div class="col-md-5"><label class="form-label">Halaqah <span class="text-danger">*</span></label>
                @if($isEdit)<input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}"><input class="form-control" value="{{ $group->name }}" disabled>
                @else<select name="tahfidz_group_id" class="form-select" required onchange="window.location.href='{{ $createUrl }}?group_id='+this.value">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($group->id===$g->id)>{{ $g->name }} — {{ $g->school?->name }}</option>@endforeach</select>@endif
            </div>
            <div class="col-md-4"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" class="form-select" required><option value="">—</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string)$val('student_id')===(string)$student->id)>{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="setoran_date" class="form-control" value="{{ $val('setoran_date', now()->toDateString()) }}" required></div>
            <div class="col-md-2"><label class="form-label">Pekan</label><input type="number" name="week_number" min="1" max="5" class="form-control" value="{{ $val('week_number') }}"></div>
            <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="setoran_type" class="form-select" required><option value="ziyadah" @selected($val('setoran_type','ziyadah')==='ziyadah')>Ziyadah</option><option value="murajaah" @selected($val('setoran_type')==='murajaah')>Murajaah</option><option value="tikror" @selected($val('setoran_type')==='tikror')>Tikror</option></select></div>
            <div class="col-md-3"><label class="form-label">Metode</label><input name="metode_pembelajaran" class="form-control" value="{{ $val('metode_pembelajaran') }}" placeholder="Talaqqi/Tasmi/Mandiri"></div>
            <div class="col-md-2"><label class="form-label">Juz</label><input type="number" name="juz" min="1" max="30" class="form-control" value="{{ $val('juz') }}"></div>
            <div class="col-md-2"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required><option value="lulus" @selected($val('status','lulus')==='lulus')>Lulus</option><option value="ulang" @selected($val('status')==='ulang')>Ulang</option><option value="ditunda" @selected($val('status')==='ditunda')>Ditunda</option></select></div>
            <div class="col-md-4"><label class="form-label">Surah Awal</label><select name="surah_start_name" id="surahStartForm" class="form-select surah-picker"><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" @selected($val('surah_start_name')===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach
@if($val('surah_start_name') && ! $surahs->contains('name_latin', $val('surah_start_name')))<option value="{{ $val('surah_start_name') }}" selected>{{ $val('surah_start_name') }}</option>@endif
</select></div>
            <div class="col-md-2"><label class="form-label">Ayat</label><input type="number" name="ayat_start" class="form-control" value="{{ $val('ayat_start') }}"></div>
            <div class="col-md-4"><label class="form-label">Surah Akhir</label><select name="surah_end_name" id="surahEndForm" class="form-select surah-picker"><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" @selected($val('surah_end_name')===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach
@if($val('surah_end_name') && ! $surahs->contains('name_latin', $val('surah_end_name')))<option value="{{ $val('surah_end_name') }}" selected>{{ $val('surah_end_name') }}</option>@endif
</select></div>
            <div class="col-md-2"><label class="form-label">Ayat</label><input type="number" name="ayat_end" class="form-control" value="{{ $val('ayat_end') }}"></div>
            <div class="col-md-2"><label class="form-label">Halaman Awal</label><input type="number" step="0.1" name="halaman_start" class="form-control" value="{{ $val('halaman_start') }}"></div>
            <div class="col-md-2"><label class="form-label">Halaman Akhir</label><input type="number" step="0.1" name="halaman_end" class="form-control" value="{{ $val('halaman_end') }}"></div>
            <div class="col-md-2"><label class="form-label">Jml Halaman</label><input type="number" step="0.1" name="jumlah_halaman" class="form-control" value="{{ $val('jumlah_halaman') }}"></div>
            <div class="col-md-2"><label class="form-label">Jml Baris</label><input type="number" name="jumlah_baris" class="form-control" value="{{ $val('jumlah_baris') }}"></div>
            <div class="col-md-2"><label class="form-label">Hasil Hafalan</label><input type="number" step="0.01" min="0" max="100" name="hasil_hafalan" class="form-control" value="{{ $val('hasil_hafalan') }}"></div>
            <div class="col-md-2"><label class="form-label">Khofi</label><input type="number" step="0.01" min="0" max="100" name="khofi" class="form-control" value="{{ $val('khofi') }}"></div>
            <div class="col-md-2"><label class="form-label">Jali</label><input type="number" step="0.01" min="0" max="100" name="jali" class="form-control" value="{{ $val('jali') }}"></div>
            <div class="col-md-2"><label class="form-label">Nilai Akhir</label><input type="number" step="0.01" min="0" max="100" name="nilai_setoran" class="form-control" value="{{ $val('nilai_setoran') }}" placeholder="Otomatis"></div>
            <div class="col-md-8"><label class="form-label">Catatan Guru</label><textarea name="catatan_guru" class="form-control" rows="2">{{ $val('catatan_guru') }}</textarea></div>
        </div></div><div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId]) }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Setoran</button></div></div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#surahStartForm, #surahEndForm').select2({ width: '100%', placeholder: 'Cari nama surah...', allowClear: true });
        }
    });
</script>
@endpush
