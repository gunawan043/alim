@extends('layouts.master')
@section('title') Laporan Sarpras @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Laporan
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Aset','value'=>$stats['assets'],'tone'=>'primary','icon'=>'ri-archive-2-line'],
        ['label'=>'Total Ruang','value'=>$stats['rooms'],'tone'=>'info','icon'=>'ri-door-open-line'],
        ['label'=>'Catatan Perawatan','value'=>$stats['maintenance'],'tone'=>'success','icon'=>'ri-tools-line'],
        ['label'=>'Peminjaman','value'=>$stats['loans'],'tone'=>'warning','icon'=>'ri-hand-heart-line'],
    ]" />

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-2">
            <div class="flex-grow-1" style="max-width:520px"><label class="form-label small fw-semibold">Cari Laporan</label><input type="text" id="reportSearch" class="form-control" placeholder="Cari nama atau deskripsi laporan…"></div>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reportGuideModal"><i class="ri-question-line me-1"></i>Panduan Laporan</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-chart-2-line me-2 text-primary"></i>Daftar Laporan Sarana Prasarana</h5></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="reportTable"><thead class="table-light"><tr><th>No</th><th>Laporan</th><th>Deskripsi</th><th>Output</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @php
                $reports = [
                    ['Buku Induk Inventaris','Buku induk aset per satuan kerja lengkap dengan kuantitas, penyusutan, dan penanggung jawab','Tabel + PDF','ri-book-2-line','primary','sarpras.laporan.buku-induk','sarpras.laporan.buku-induk.pdf'],
                    ['Kartu Inventaris Ruangan','KIR per ruang: jenis barang, merek, jumlah, kondisi, dan keterangan','Tabel + PDF','ri-file-list-3-line','info','sarpras.laporan.kir','sarpras.laporan.kir.pdf'],
                    ['Inventaris Per Ruang','Daftar aset yang ditempatkan pada setiap ruangan','Tabel + PDF','ri-door-open-line','secondary','sarpras.laporan.inventaris-per-ruang','sarpras.laporan.inventaris-per-ruang.pdf'],
                    ['Kondisi Aset','Rekap kondisi seluruh aset: baik, rusak ringan, sedang, berat, dan hilang','Tabel + PDF','ri-bar-chart-box-line','warning','sarpras.laporan.kondisi-aset','sarpras.laporan.kondisi-aset.pdf'],
                    ['Peminjaman Aset','Riwayat dan statistik peminjaman aset','Tabel','ri-hand-heart-line','info','sarpras.laporan.peminjaman',null],
                    ['Buku Peminjaman Barang','Buku peminjaman lengkap dengan kondisi, tanggal, dan paraf petugas','Tabel + PDF','ri-book-open-line','primary','sarpras.laporan.buku-peminjaman','sarpras.laporan.buku-peminjaman.pdf'],
                    ['Pemeliharaan','Riwayat dan biaya pemeliharaan aset','Tabel','ri-tools-line','success','sarpras.laporan.pemeliharaan',null],
                    ['Nilai Aset','Nilai perolehan, nilai buku, dan penyusutan aset','Tabel','ri-money-dollar-circle-line','secondary','sarpras.laporan.nilai-aset',null],
                    ['Export Excel Aset','Unduh seluruh data aset dalam format Excel','Excel','ri-file-excel-line','danger','sarpras.laporan.export',null],
                ];
            @endphp
            @foreach($reports as $i => $report)
                <tr class="report-row" data-search="{{ strtolower($report[0].' '.$report[1]) }}">
                    <td>{{ $i + 1 }}</td>
                    <td><div class="d-flex align-items-center gap-2"><span class="avatar-xs"><span class="avatar-title rounded bg-{{ $report[4] }}-subtle text-{{ $report[4] }}"><i class="{{ $report[3] }}"></i></span></span><span class="fw-medium">{{ $report[0] }}</span></div></td>
                    <td class="small text-muted">{{ $report[1] }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $report[2] }}</span></td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route($report[5]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line me-1"></i>Lihat</a>@if($report[6])<a href="{{ route($report[6]) }}" class="btn btn-sm btn-outline-danger"><i class="ri-file-pdf-line"></i></a>@endif</div></td>
                </tr>
            @endforeach
            <tr id="reportNoResult" class="d-none"><td colspan="5" class="text-center py-4 text-muted">Laporan tidak ditemukan.</td></tr>
        </tbody></table></div></div>
    </div>

    <x-modal-notify id="reportGuideModal" tone="info" title="Panduan Laporan" message="Pilih laporan pada tabel. Gunakan tombol Lihat untuk membuka pratinjau dan PDF untuk mengunduh. Setiap laporan memiliki filter sendiri sesuai kebutuhan." />
@endsection

@push('script')
<script>
    (function () {
        const input = document.getElementById('reportSearch');
        const rows = document.querySelectorAll('.report-row');
        const empty = document.getElementById('reportNoResult');
        input.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            let visible = 0;
            rows.forEach(function (row) {
                const show = !term || row.dataset.search.includes(term);
                row.classList.toggle('d-none', !show);
                if (show) visible++;
            });
            empty.classList.toggle('d-none', visible > 0);
        });
    })();
</script>
@endpush
