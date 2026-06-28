@extends('layouts.master')
@section('title') Kisi-kisi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Kisi-kisi @endslot
        @slot('title') Daftar Kisi-kisi @endslot
    @endcomponent

    <!-- Filter -->
    <div class="row mb-3">
        <div class="col-md-12">
            <form class="d-flex gap-2">
                <select name="subject_id" class="form-control form-control-sm" style="max-width:220px">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
                <select name="jenis_ujian" class="form-control form-control-sm" style="max-width:200px">
                    <option value="">Semua Jenis</option>
                    @foreach(['sts','sas','ulangan_harian','try_out','latihan'] as $j)
                        <option value="{{ $j }}" {{ request('jenis_ujian') === $j ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $j)) }}</option>
                    @endforeach
                </select>
                <select name="semester" class="form-control form-control-sm" style="max-width:150px">
                    <option value="">Semua Semester</option>
                    <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                </select>
                <button class="btn btn-sm btn-primary"><i class="ri-search-line"></i></button>
                <a href="{{ route('user.kisi-kisi.create') }}" class="btn btn-sm btn-success ms-auto">
                    <i class="ri-add-line"></i> Buat Kisi-kisi
                </a>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Mapel</th>
                                    <th>Fase</th>
                                    <th>Sem</th>
                                    <th>Jenis</th>
                                    <th>Tgl</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kisis as $kisi)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.kisi-kisi.show', $kisi->id) }}">{{ $kisi->judul }}</a>
                                    </td>
                                    <td>{{ $kisi->subject->name ?? '-' }}</td>
                                    <td>{{ $kisi->gradeLevel->nama ?? '-' }}</td>
                                    <td>{{ ucfirst($kisi->semester) }}</td>
                                    <td><span class="badge bg-info">{{ str_replace('_', ' ', $kisi->jenis_ujian) }}</span></td>
                                    <td>{{ $kisi->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('user.kisi-kisi.edit', $kisi->id) }}" class="btn btn-sm btn-warning">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <form action="{{ route('user.kisi-kisi.destroy', $kisi->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus kisi-kisi ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">Belum ada kisi-kisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $kisis->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection