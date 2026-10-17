{{-- Payroll: Generate massal --}}
@extends('layouts.master')
@section('title') Generate Payroll @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Gaji & Kompensasi @endslot
        @slot('li_2') <a href="{{ route('user.payroll.index', $userId) }}">Daftar Gaji</a> @endslot
        @slot('title') Generate Payroll @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-flashlight-line me-2 text-success"></i>Generate Payroll Massal</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.payroll.generate.store', $userId) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Bulan <span class="text-danger">*</span></label>
                                <select name="bulan" class="form-select" required>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) old('bulan', now()->month) === $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                <select name="tahun" class="form-select" required>
                                    @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" {{ (int) old('tahun', now()->year) === $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GTK (opsional)</label>
                                <select name="gtk_id" class="form-select">
                                    <option value="">Semua GTK aktif</option>
                                    @foreach($gtkList as $gtk)
                                        <option value="{{ $gtk->id }}" {{ old('gtk_id') === $gtk->id ? 'selected' : '' }}>{{ $gtk->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info small mt-3 mb-3">
                            <i class="ri-information-line me-1"></i>
                            Gaji pokok dihitung dari <strong>skala gaji guru/tendik</strong> berdasarkan masa kerja, level, dan golongan pendidikan.
                            Tunjangan tetap/kehadiran serta potongan keterlambatan/alpa mengikuti <strong>Master Gaji</strong>.
                            Slip yang sudah <em>published/paid</em> tidak akan ditimpa.
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('user.payroll.master', $userId) }}" class="btn btn-light">
                                <i class="ri-settings-3-line me-1"></i>Atur Master Gaji
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-play-line me-1"></i>Proses Generate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>GTK yang Diproses</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:420px;overflow:auto">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Nama</th><th>Jenis</th></tr>
                            </thead>
                            <tbody>
                                @forelse($gtkList as $gtk)
                                    <tr>
                                        <td>{{ $gtk->nama }}</td>
                                        <td class="small text-muted">{{ $gtk->latestEmployment?->jenis_gtk ?? 'belum diisi' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-4">Belum ada GTK.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
