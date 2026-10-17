@extends('layouts.master')

@section('title', 'Kebijakan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Kebijakan Asrama</h4>
            </div>
        </div>
    </div>

    {{-- BAB IV: PERIZINAN --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-primary">
                <i class="ri-book-2-line me-2"></i>BAB IV : PERIZINAN
            </h5>
        </div>
        <div class="card-body">
            <div class="policy-document">
                <h6 class="fw-bold text-dark mb-2">Pasal 10 : Pengertian Perizinan</h6>
                <p class="ms-3 mb-3">
                    Perizinan adalah pemberian hak kepada santri oleh pihak yang berwenang (kepala satuan pendidikan dan kepala pengasuhan) untuk meninggalkan pesantren sesuai dengan kebutuhan dalam batas waktu yang telah ditentukan.
                </p>

                <h6 class="fw-bold text-dark mb-2">Pasal 11 : Tujuan Perizinan</h6>
                <ol class="ms-3 mb-3">
                    <li>Memberikan pelayanan kepada santri pada keadaan yang dibutuhkan.</li>
                    <li>Mendidik dan membiasakan santri tertib dan disiplin.</li>
                    <li>Mendukung program Pendidikan di PAH Mataram.</li>
                </ol>

                <h6 class="fw-bold text-dark mb-2">Pasal 12 : Penanggung Jawab Perizinan</h6>
                <p class="ms-3 mb-3">
                    Perizinan sepenuhnya merupakan tanggung jawab kepala satuan pendidikan dan kepala pengasuhan.
                </p>

                <h6 class="fw-bold text-dark mb-2">Pasal 13 : Jenis Perizinan</h6>
                <ol class="ms-3 mb-2">
                    <li>Sakit</li>
                    <li>Musibah</li>
                    <li>Walimah orang tua dan saudara kandung.</li>
                    <li>Haji/umrah</li>
                    <li>Wisuda orang tua dan saudara kandung</li>
                    <li>Keperluan penting lainnya.</li>
                </ol>
                <p class="ms-3 mb-3"><em>Semua jenis perizinan harus dengan izin orang tua/wali santri.</em></p>

                <h6 class="fw-bold text-dark mb-2">Pasal 14 : Tempat dan Masa Perizinan</h6>
                <ol class="ms-3 mb-3">
                    <li>Izin diajukan kepada kepala satuan pendidikan dan kepala pengasuhan</li>
                    <li>Tempat mengambil kartu izin adalah di bagian perizinan.</li>
                    <li>Izin meninggalkan pembelajaran maksimal 3 (tiga) hari.</li>
                </ol>

                <h6 class="fw-bold text-dark mb-2">Pasal 15 : Prosedur Perizinan</h6>
                <p class="ms-3 mb-2">Prosedur perizinan sebagai berikut:</p>
                <ol class="ms-3 mb-3">
                    <li>Orang tua/wali santri mengajukan izin keluar</li>
                    <li>Orang tua/wali santri yang telah mendapatkan izin diperbolehkan membawa putra/putri nya setelah konfirmasi ke bagian keamanan.</li>
                    <li>Santri yang telah mendapatkan izin sesuai prosedur wajib berpakaian rapi, dilarang memakai kaos, jeans, soft jeans (model pensil atau yang sejenis), celana pendek ¾ atau yang sejenis.</li>
                    <li>Santri wajib kembali ke pondok sesuai dengan waktu yang telah ditentukan.</li>
                    <li>Santri diwajibkan melapor kepada petugas perizinan dengan menyerahkan kartu.</li>
                    <li>Santri harus siap diperiksa petugas perizinan saat kembali.</li>
                    <li>Izin keterlambatan balik ke pondok pesantren dari waktu yang ditentukan harus konfirmasi ke bagian perizinan.</li>
                </ol>

                <h6 class="fw-bold text-dark mb-2">Pasal 16 : Pelanggaran Perizinan</h6>
                <p class="ms-3 mb-2">Pelanggaran perizinan terdiri dari:</p>
                <ol class="ms-3 mb-3">
                    <li>Keluar pesantren tanpa izin.</li>
                    <li>Keluar pesantren tanpa surat izin.</li>
                    <li>Keluar pesantren tidak melewati pintu gerbang.</li>
                    <li>Tidak mengembalikan kartu perizinan.</li>
                    <li>Terlambat/melebihi waktu izin tanpa informasi.</li>
                    <li>Tidak melapor ke satpam saat keluar dan kembali ke pesantren.</li>
                    <li>Membawa/membeli barang yang dilarang oleh aturan pesantren.</li>
                    <li>Tidak siap diperiksa barang bawaannya oleh satpam/petugas perizinan.</li>
                    <li>Membawa tamu tanpa melapor.</li>
                </ol>

                <h6 class="fw-bold text-dark mb-2">Pasal 17 : Sanksi Pelanggaran Perizinan</h6>
                <p class="ms-3 mb-0">
                    Santri yang melanggar akan diberi sanksi berupa poin pelanggaran dan atau dengan kebijakan bagian asrama dan nasehat/bimbingan.
                </p>
            </div>
        </div>
    </div>

    {{-- BAB V: KUNJUNGAN ORANG TUA DAN TAMU --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-primary">
                <i class="ri-team-line me-2"></i>BAB V : KUNJUNGAN ORANG TUA DAN TAMU
            </h5>
        </div>
        <div class="card-body">
            <div class="policy-document">
                <p class="ms-3 mb-3">
                    Bagi orang tua atau wali santri, dan tamu yang berkunjung harus memperhatikan hal-hal sebagai berikut:
                </p>
                <ol class="ms-3 mb-3">
                    <li>Menaati tata tertib dan peraturan pesantren.</li>
                    <li>Melaporkan kepentingannya pada satpam dan bagian pengasuhan.</li>
                    <li>Kunjungan orang tua mengikuti ketentuan sebagai berikut:
                        <ol type="a" class="mt-2">
                            <li>Santri Putra : Ahad pekan ke- 1 dan 3</li>
                            <li>Santri Putri : Ahad pekan ke- 2 dan 4</li>
                            <li>Pekan ke- 5 tidak ada kunjungan (libur)</li>
                            <li>Jam kunjungan:
                                <ul class="mt-1">
                                    <li>Pagi : 08.00 – 11.30 WITA</li>
                                    <li>Sore : Ba'da ashar – 17.30 WITA</li>
                                </ul>
                            </li>
                            <li>Santri bersaudara boleh memilih salah satu jadwal putra atau putri dengan mengajukan izin kepada kepala pengasuhan</li>
                            <li>Kunjungan dilakukan oleh orang tua/wali santri dan bukan oleh teman atau kerabat yang sebaya dengan santri.</li>
                        </ol>
                    </li>
                    <li>Orang tua atau tamu diwajibkan menunjukkan kartu kunjungan atau kartu identitas (KTP).</li>
                    <li>Tamu atau pengunjung dimohon untuk menjaga ketertiban dan kebersihan pesantren.</li>
                    <li>Santri putra tidak boleh dibawa ke pondok putri dan sebaliknya.</li>
                    <li>Bagi orang tua yang ingin membawa putranya keluar lingkungan pesantren wajib meminta izin kepada bagian perizinan.</li>
                    <li>Orang tua santri tidak boleh membawa santri lain keluar pesantren tanpa seizin orang tua yang bersangkutan dan pihak yang bertanggung jawab atas perizinan santri.</li>
                    <li>Orang tua atau tamu yang hendak bertemu dengan santri pada jam kegiatan pembelajaran harus mendapatkan izin dari kepala satuan pendidikan.</li>
                    <li>Pengunjung atau wali santri dilarang meminjamkan HP kepada santri tanpa pengawasan.</li>
                    <li>Pengunjung atau wali santri diharapkan mengenakan pakaian syar'i (tidak ketat, transparan dan pelanggaran syar'i lainnya).</li>
                    <li>Pengunjung atau wali santri dilarang merokok dan mendengar/ memperdengarkan musik</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Daftar Kebijakan (Read-only) --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-primary">
                <i class="ri-file-list-3-line me-2"></i>Daftar Kebijakan Asrama
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="policyTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;" class="text-center">No</th>
                            <th>Kode</th>
                            <th>Nama Kebijakan</th>
                            <th>Strategi Izin</th>
                            <th>Strategi Kunjungan</th>
                            <th>Diterapkan di</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><code class="policy-code">{{ $policy->code }}</code></td>
                            <td>
                                <strong>{{ $policy->name }}</strong>
                                @if($policy->description)
                                    <br><small class="text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($policy->leave_strategy === 'quota')
                                    <span class="badge bg-warning-subtle text-warning">
                                        Kuota: {{ $policy->leave_quota }}/{{ $policy->leave_quota_period }}
                                    </span>
                                @elseif($policy->leave_strategy === 'unrestricted')
                                    <span class="badge bg-success-subtle text-success">Tanpa Batasan</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Dilarang</span>
                                @endif
                            </td>
                            <td>
                                @if($policy->visit_strategy === 'quota')
                                    <span class="badge bg-warning-subtle text-warning">
                                        Kuota: {{ $policy->visit_quota }}/{{ $policy->visit_quota_period }}
                                    </span>
                                @elseif($policy->visit_strategy === 'unrestricted')
                                    <span class="badge bg-success-subtle text-success">Bebas</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Dilarang</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $dorms = $policy->assignments->where('policy_assignment_type', 'dormitory')->pluck('dormitory.name')->filter();
                                @endphp
                                @if($dorms->count() > 0)
                                    @foreach($dorms->take(2) as $dn)
                                        <span class="badge bg-info-subtle text-info">{{ $dn }}</span>
                                    @endforeach
                                    @if($dorms->count() > 2)
                                        <span class="badge bg-secondary">+{{ $dorms->count() - 2 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">Belum diterapkan</span>
                                @endif
                            </td>
                            <td>
                                @if($policy->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Non-aktif</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h6 class="text-muted mb-1 mt-3">Belum Ada Kebijakan Asrama</h6>
                                <p class="text-muted mb-0">Saat ini belum ada kebijakan asrama yang terdaftar.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$policies" />
        </div>
    </div>
</div>

<style>
    .policy-document h6 {
        margin-top: 1rem;
    }
    .policy-document h6:first-child {
        margin-top: 0;
    }
    #policyTable .policy-code {
        font-family: monospace;
        background: #f1f3f5;
        padding: 2px 6px;
        border-radius: 3px;
        color: #495057;
        font-size: 0.85rem;
    }
</style>
@endsection