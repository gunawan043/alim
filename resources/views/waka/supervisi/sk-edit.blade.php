@extends('waka.master')
@section('title') Atur SK Tim Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi-sk.index') }}">Tim Supervisi & SK</a> @endslot
        @slot('title') {{ $sk->nomor }} @endslot
    @endcomponent

    <x-supervisi-workflow current="team" :year-id="$sk->academic_year_id" :semester="$sk->semester" :admin="true" />

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h4 class="mb-1">Tim Supervisi — {{ $sk->academicYear?->name }}</h4><p class="text-muted small mb-0">Semester {{ $sk->semester === 1 ? 'Ganjil' : 'Genap' }} · {{ $sk->nomor }}</p></div>
        <div class="d-flex gap-2">
            @if($sk->institution_decree_id)
                <a href="{{ route('user.institution-decrees.show', ['userId' => auth()->id(), 'id' => $sk->institution_decree_id]) }}" class="btn btn-outline-secondary">Database SK</a>
            @endif
            @if($sk->surat_keluar_id)
                <a href="{{ route('waka.surat-keluar.show', $sk->surat_keluar_id) }}" class="btn btn-outline-success">Surat Keluar</a>
            @endif
            <a href="{{ route('waka.supervisi-sk.index') }}" class="btn btn-light">Kembali</a>
            <a href="{{ route('waka.supervisi-sk.cetak', $sk->id) }}" target="_blank" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Cetak SK</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0">Identitas SK</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('waka.supervisi-sk.update', $sk->id) }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-5"><label class="form-label">Tahun Ajaran</label><select name="academic_year_id" class="form-select">@foreach($academicYears as $year)<option value="{{ $year->id }}" {{ (string) $sk->academic_year_id === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="1" {{ $sk->semester === 1 ? 'selected' : '' }}>Ganjil</option><option value="2" {{ $sk->semester === 2 ? 'selected' : '' }}>Genap</option></select></div>
                <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ $sk->tanggal?->format('Y-m-d') }}"></div>
                <div class="col-md-6"><label class="form-label">Nomor SK</label><input name="nomor" class="form-control" value="{{ $sk->nomor }}" required></div>
                <div class="col-12"><label class="form-label">Menimbang</label><textarea name="menimbang" class="form-control" rows="2">{{ $sk->menimbang }}</textarea></div>
                <div class="col-12"><label class="form-label">Mengingat</label><textarea name="mengingat" class="form-control" rows="2">{{ $sk->mengingat }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Identitas</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0">Anggota Tim</h5>
                <div class="small text-muted">Tambah baris langsung di tabel, pilih GTK dan peran, lalu simpan sekali di akhir.</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addMemberRow"><i class="ri-add-line me-1"></i>Tambah Baris</button>
        </div>
        <form method="POST" action="{{ route('waka.supervisi-sk.member.sync', $sk->id) }}">
            @csrf @method('PUT')
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th style="width:55px" class="text-center">No</th><th>Nama GTK</th><th style="width:180px">Jabatan</th><th style="width:190px">Peran Tim</th><th style="width:70px" class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody id="memberRows">
                        @foreach($sk->anggota as $index => $member)
                            @include('waka.supervisi.partials.member-row', ['index' => $index, 'member' => $member, 'gtks' => $gtks])
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <template id="memberRowTemplate">
                    @include('waka.supervisi.partials.member-row', ['index' => '__INDEX__', 'member' => null, 'gtks' => $gtks])
                </template>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">KSP/WKSP maksimal satu orang. Perwakilan guru bisa lebih dari satu.</div>
                <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Anggota Tim</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('memberRows');
    const template = document.getElementById('memberRowTemplate');
    const addButton = document.getElementById('addMemberRow');
    let nextIndex = {{ $sk->anggota->count() }};

    function renumber() {
        tbody.querySelectorAll('.member-row').forEach(function (row, index) {
            const number = row.querySelector('.row-number');
            if (number) number.textContent = index + 1;
        });
    }

    function updatePosition(row) {
        const select = row.querySelector('.member-gtk');
        const position = row.querySelector('.member-position');
        if (!select || !position) return;
        const option = select.options[select.selectedIndex];
        position.textContent = option?.dataset.jabatan || '—';
    }

    function bindRow(row) {
        row.querySelector('.member-gtk')?.addEventListener('change', function () { updatePosition(row); });
        row.querySelector('.remove-member-row')?.addEventListener('click', function () {
            row.remove();
            renumber();
        });
    }

    tbody.querySelectorAll('.member-row').forEach(function (row) {
        bindRow(row);
        updatePosition(row);
    });
    renumber();

    addButton?.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        tbody.insertAdjacentHTML('beforeend', html);
        const row = tbody.lastElementChild;
        bindRow(row);
        updatePosition(row);
        renumber();
        row.querySelector('.member-gtk')?.focus();
    });
});
</script>
@endsection
