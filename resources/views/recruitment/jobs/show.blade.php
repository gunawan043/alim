@extends('layouts.master')
@section('title') Job Details @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Jobs @endslot
@slot('li_2') Job Lists @endslot
@slot('title') Job Details @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex">
                    <div class="avatar-lg me-3">
                        <div class="avatar-title bg-light rounded">
                            <img src="{{ $job->company_logo ?? URL::asset('build/images/companies/img-1.png') }}" alt="" class="avatar-sm">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-2">{{ $job->judul }}</h4>
                        <div class="hstack gap-3 flex-wrap">
                            <div><i class="ri-building-line me-1 align-bottom"></i> {{ $job->workUnit->name ?? 'friday' }}</div>
                            <div><i class="ri-map-pin-line me-1 align-bottom"></i> {{ $job->location ?? 'Mataram' }}</div>
                            <div><i class="ri-time-line me-1 align-bottom"></i> {{ $job->created_at->diffForHumans() }}</div>
                            <div><span class="badge bg-success">{{ $job->jenis_pegawai }}</span></div>
                            <div><span class="badge bg-info">{{ $job->status_pegawai }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-3 col-sm-6">
                        <div class="p-2 border border-dashed rounded">
                            <p class="text-muted mb-1">Kuota</p>
                            <h5>{{ $job->kuota }} Orang</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="p-2 border border-dashed rounded">
                            <p class="text-muted mb-1">Terisi</p>
                            <h5>{{ $job->kuota_terisi }} Orang</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="p-2 border border-dashed rounded">
                            <p class="text-muted mb-1">Pelamar</p>
                            <h5>{{ $job->applications_count }} Orang</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="p-2 border border-dashed rounded">
                            <p class="text-muted mb-1">Sisa Kuota</p>
                            <h5 class="text-warning">{{ $job->kuota - $job->kuota_terisi }} Orang</h5>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3">Deskripsi Pekerjaan</h5>
                    <p class="text-muted">{{ $job->deskripsi_pekerjaan }}</p>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3">Persyaratan</h5>
                    <ul class="text-muted vstack gap-2">
                        @if($job->persyaratan_umum)
                            @foreach(json_decode($job->persyaratan_umum) as $syarat)
                                <li><i class="ri-check-line text-success me-2"></i>{{ $syarat }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3">Fasilitas</h5>
                    <ul class="text-muted vstack gap-2">
                        @if($job->fasilitas)
                            @foreach(json_decode($job->fasilitas) as $fasilitas)
                                <li><i class="ri-gift-line text-success me-2"></i>{{ $fasilitas }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <div class="mt-4">
                    <div class="hstack gap-2">
                        <button class="btn btn-primary"><i class="ri-edit-line"></i> Edit</button>
                        <button class="btn btn-danger"><i class="ri-delete-bin-line"></i> Tutup</button>
                        <button class="btn btn-success"><i class="ri-share-line"></i> Bagikan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi Lowongan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="ps-0">Kode Lowongan</th>
                            <td class="text-muted">: {{ $job->kode_lowongan }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Tanggal Mulai</th>
                            <td class="text-muted">: {{ $job->tanggal_mulai->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Tanggal Selesai</th>
                            <td class="text-muted">: {{ $job->tanggal_selesai->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Jenis Pegawai</th>
                            <td class="text-muted">: {{ $job->jenis_pegawai }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Status</th>
                            <td>
                                @if($job->status == 'aktif')
                                    <span class="badge bg-success">Aktif</span>
                                @elseif($job->status == 'ditutup')
                                    <span class="badge bg-danger">Ditutup</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="ps-0">Dibuat Oleh</th>
                            <td class="text-muted">: {{ $job->creator->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ringkasan Pelamar</h5>
            </div>
            <div class="card-body">
                <div id="application_chart" class="apex-charts" dir="ltr"></div>
                
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Seleksi Administrasi</span>
                        <span class="badge bg-info">{{ $job->applications()->where('status', 'seleksi_administrasi')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tes Tertulis</span>
                        <span class="badge bg-warning">{{ $job->applications()->where('status', 'tes_tertulis')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Wawancara</span>
                        <span class="badge bg-primary">{{ $job->applications()->where('status', 'wawancara')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Diterima</span>
                        <span class="badge bg-success">{{ $job->kuota_terisi }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="vstack gap-2">
                    <a href="{{ route('user.ats.applications.index', ['userId' => $userId, 'job' => $job->id]) }}" class="btn btn-outline-primary">
                        <i class="ri-list-check"></i> Lihat Semua Pelamar
                    </a>
                    <a href="" class="btn btn-outline-success">
                        <i class="ri-calendar-line"></i> Atur Jadwal Interview
                    </a>
                    <button class="btn btn-outline-info" onclick="exportReport()">
                        <i class="ri-file-excel-line"></i> Export Data Pelamar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{URL::asset('build/libs/apexcharts/apexcharts.min.js')}}"></script>
<script>
    var options = {
        series: [{{ $job->applications()->where('status', 'seleksi_administrasi')->count() }}, 
                 {{ $job->applications()->where('status', 'tes_tertulis')->count() }}, 
                 {{ $job->applications()->where('status', 'wawancara')->count() }},
                 {{ $job->kuota_terisi }}],
        chart: {
            type: 'donut',
            height: 250
        },
        labels: ['Seleksi Adm', 'Tes Tertulis', 'Wawancara', 'Diterima'],
        colors: ['#299cdb', '#f1b44c', '#3452e1', '#34c38f'],
        legend: {
            show: false
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#application_chart"), options);
    chart.render();
</script>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection