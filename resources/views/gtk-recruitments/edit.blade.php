@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Edit GTK Recruitment</h4>
        <a href="{{ route('user.recruitment.show', [request('userId', ''), request('recruitmentUuid', '')]) }}" class="btn btn-secondary">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('user.recruitment.update', [request('userId'), $recruitment->id]) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Work Unit</label>
                        <select name="work_unit_id" class="form-select" required>
                            <option value="">— pilih work unit —</option>
                            @foreach(\App\Models\WorkUnit::where('is_active', true)->orderBy('name')->get() as $wu)
                                <option value="{{ $wu->id }}" {{ old('work_unit_id', $recruitment->work_unit_id) === $wu->id ? 'selected' : '' }}>
                                    {{ $wu->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jabatan</label>
                        <select name="jabatan" class="form-select" required>
                            <option value="">— pilih jabatan —</option>
                            @foreach($jabatan as $j)
                                <option value="{{ $j->nama }}" {{ old('jabatan', $recruitment->jabatan) === $j->nama ? 'selected' : '' }}>
                                    {{ $j->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kebutuhan (jumlah)</label>
                        <input type="number" name="kebutuhan" min="1" class="form-control" value="{{ old('kebutuhan', $recruitment->kebutuhan) }}" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Kualifikasi</label>
                        <textarea name="kualifikasi" class="form-control" rows="3" required>{{ old('kualifikasi', $recruitment->kualifikasi) }}</textarea>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Dibutuhkan</label>
                        <input type="date" name="tanggal_dibutuhkan" class="form-control" value="{{ old('tanggal_dibutuhkan', optional($recruitment->tanggal_dibutuhkan)->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>
@endsection