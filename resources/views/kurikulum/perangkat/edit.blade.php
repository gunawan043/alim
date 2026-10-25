@extends('layouts.master')
@section('title', 'Edit Perangkat')

@section('css')
    @include('kurikulum._styles')

    <style>
        .sticky-actions {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: var(--bs-body-bg, #fff);
            border-top: 1px solid var(--bs-border-color, #dee2e6);
            padding: 0.75rem 1rem;
            margin-left: -1rem;
            margin-right: -1rem;
            border-radius: 0 0 .375rem .375rem;
            box-shadow: 0 -4px 12px rgba(0,0,0,.05);
        }
    </style>
@endsection

@php
    $userId = $userId ?? auth()->id();
    $sections = \App\Models\PerangkatPembelajaran::SECTIONS_RPM;
    $legacyFilled = $perangkat->legacyFilledSections();
    $atp = $perangkat->atp;
    $tps = $atp?->items?->map(fn ($item) => $item->tujuanPembelajaran)->filter() ?? collect();
    $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
    $canEdit = true;
    $disabled = '';
    $isAgama = ($formatType ?? 'umum') === 'agama';
    $alokasiValue = old('desain.alokasi_waktu', $perangkat->desainValue('alokasi_waktu') ?? ($perangkat->atp?->total_jp ? $perangkat->atp->total_jp.' JP' : ''));
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}">Preview</a> @endslot
        @slot('title') Edit Perangkat @endslot
    @endcomponent

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Edit {{ $perangkat->judul }}</h5>
                    <p class="text-muted mb-0 small">Perubahan disimpan ke data perangkat yang sama dan langsung dipakai di semua format cetak.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.kurikulum.ai.index', [
                            'userId' => $userId,
                            'type' => 'rpm',
                            'subject_id' => $perangkat->subject_id,
                            'grade_level_id' => $perangkat->grade_level_id,
                            'academic_year_id' => $perangkat->academic_year_id,
                            'semester' => $perangkat->semester,
                            'topik' => $perangkat->judul,
                        ]) }}" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="ri-sparkling-line me-1"></i> AI Assistant
                        </a>
                        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-eye-line me-1"></i> Kembali ke Preview
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> Terjadi kesalahan pada formulir:
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- FORM (partial) — tombol simpan sticky via CSS .sticky-actions --}}
    @include('kurikulum.perangkat.partials.edit-form')

    <div class="position-fixed bottom-0 end-0 m-3 d-flex align-items-center gap-2 p-2 bg-white border rounded shadow"
         style="z-index: 1050;">
        <span class="small text-muted d-none d-md-inline me-2">
            <i class="ri-information-line me-1"></i>Perubahan belum tersimpan
        </span>
        <button type="submit" form="perangkat-edit-form" class="btn btn-success">
            <i class="ri-save-3-line me-1"></i>Simpan Perangkat
        </button>
        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-light">
            Batal
        </a>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/ckeditor5/ckeditor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.ClassicEditor) return;

            const richKeys = new Set([
                'identifikasi_peserta_didik', 'identifikasi_materi', 'profil_lulusan',
                'kbc_kesiapan_belajar', 'kbc_karakteristik_materi', 'kbc_iklim_budaya',
                'kbc_topik_kontekstual', 'kbc_indikator', 'kbc_diferensiasi',
                'tema_kbc', 'materi_insersi', 'pilar_kbc', 'indikator_kbc', 'refleksi_kbc', 'asesmen_kbc',
                'kegiatan_awal', 'pengalaman_memahami', 'pengalaman_mengaplikasi',
                'pengalaman_refleksi', 'penutup',
                'kompetensi_awal', 'sarana_prasarana', 'target_peserta_didik', 'iktp',
                'merdeka_pemahaman_bermakna', 'merdeka_pertanyaan_pemantik', 'model_pembelajaran',
                'pengayaan', 'remedial', 'refleksi_guru', 'refleksi_peserta_didik',
                'lampiran_lkpd', 'lampiran_bahan_bacaan', 'glosarium', 'daftar_pustaka',
            ]);

            document.querySelectorAll('textarea[name^="desain["]').forEach(function (textarea) {
                const key = (textarea.name.match(/^desain\[([^\]]+)\]/) || [])[1];
                if (!key || !richKeys.has(key)) return;

                ClassicEditor.create(textarea, {
                    licenseKey: 'GPL',
                    language: 'en',
                    toolbar: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'insertTable', 'blockQuote', '|', 'bulletedList', 'numberedList', 'outdent', 'indent'],
                }).catch(function (error) {
                    console.error('CKEditor gagal dimuat', error);
                });
            });
        });
    </script>
@endsection