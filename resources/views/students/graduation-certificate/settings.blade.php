@extends('layouts.master')

@section('title')
    Format Surat Keterangan Lulus
@endsection

@section('css')
    @include('shared._styles')
    <style>
        .skl-table td { vertical-align: middle; }
        .skl-table .form-control-sm, .skl-table .form-select-sm { font-size: .8rem; }
        .skl-table tr.is-hidden { opacity: .55; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Akademik
        @endslot
        @slot('li_2')
            Data Santri
        @endslot
        @slot('title')
            Format Surat Keterangan Lulus
        @endslot
    @endcomponent

    <div class="alert alert-info py-2 px-3 small">
        <i class="ri-information-line me-1"></i>
        Kelulusan adalah jalur keluar santri dengan dokumen sendiri. Atur <strong>format cetak</strong>,
        <strong>kelompok mata pelajaran</strong>, dan <strong>nama mata pelajaran</strong> yang muncul di SKL
        untuk <strong>{{ $school->name }}</strong>.
    </div>

    <form method="POST" action="{{ route('user.students.graduation-certificate.settings.save', ['userId' => $userId]) }}">
        @csrf
        <input type="hidden" name="school_id" value="{{ $schoolId }}">

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="ri-layout-line me-1"></i>Layout Cetak</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">Ukuran kertas</label>
                                <select name="paper_size" class="form-select form-select-sm">
                                    @foreach (['a4' => 'A4', 'folio' => 'Folio', 'letter' => 'Letter'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('paper_size', $setting->paper_size) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Jenis font</label>
                                <select name="font_family" class="form-select form-select-sm">
                                    @foreach (['Times New Roman', 'Arial', 'Georgia', 'Bookman Old Style', 'Tahoma'] as $font)
                                        <option value="{{ $font }}" @selected(old('font_family', $setting->font_family) === $font)>{{ $font }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Margin atas (mm)</label>
                                <input type="number" step="0.5" min="0" max="50" name="margin_top"
                                    class="form-control form-control-sm" value="{{ old('margin_top', $setting->margin_top) }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Margin bawah (mm)</label>
                                <input type="number" step="0.5" min="0" max="50" name="margin_bottom"
                                    class="form-control form-control-sm" value="{{ old('margin_bottom', $setting->margin_bottom) }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Margin kiri (mm)</label>
                                <input type="number" step="0.5" min="0" max="50" name="margin_left"
                                    class="form-control form-control-sm" value="{{ old('margin_left', $setting->margin_left) }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Margin kanan (mm)</label>
                                <input type="number" step="0.5" min="0" max="50" name="margin_right"
                                    class="form-control form-control-sm" value="{{ old('margin_right', $setting->margin_right) }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Ukuran font (pt)</label>
                                <input type="number" step="0.5" min="6" max="24" name="font_size"
                                    class="form-control form-control-sm" value="{{ old('font_size', $setting->font_size) }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Jarak baris</label>
                                <input type="number" step="0.05" min="1" max="2.5" name="line_height"
                                    class="form-control form-control-sm" value="{{ old('line_height', $setting->line_height) }}">
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-2">
                            @foreach ([
                                'show_kop' => 'Cetak kop sekolah',
                                'show_npsn' => 'Tampilkan NPSN',
                                'show_ijazah' => 'Tampilkan nomor ijazah',
                                'show_photo' => 'Tampilkan foto santri',
                                'show_nilai' => 'Tampilkan tabel nilai',
                                'show_rata_rata' => 'Tampilkan rata-rata',
                                'show_signature' => 'Tampilkan tanda tangan',
                            ] as $field => $label)
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="{{ $field }}"
                                            name="{{ $field }}" value="1" @checked(old($field, $setting->{$field}))>
                                        <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="ri-file-text-line me-1"></i>Isi Dokumen</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small mb-1">Judul dokumen</label>
                                <input type="text" name="title" class="form-control form-control-sm"
                                    value="{{ old('title', $setting->title) }}" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Nomor surat</label>
                                <input type="text" name="number_prefix" class="form-control form-control-sm"
                                    placeholder="400.3.11.1/......."
                                    value="{{ old('number_prefix', $setting->number_prefix) }}" maxlength="100">
                                <div class="form-text small">Dicetak sebagai "Nomor: ...". Kosongkan bila belum diatur.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Kurikulum</label>
                                <input type="text" name="curriculum" class="form-control form-control-sm"
                                    value="{{ old('curriculum', $setting->curriculum) }}" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Kalimat kriteria kelulusan</label>
                                <textarea name="criteria_text" rows="2" class="form-control form-control-sm"
                                    placeholder="Kosongkan untuk memakai kalimat otomatis.">{{ old('criteria_text', $setting->criteria_text) }}</textarea>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Kota penerbitan</label>
                                <input type="text" name="city" class="form-control form-control-sm"
                                    placeholder="{{ $school->city?->display_name }}"
                                    value="{{ old('city', $setting->city) }}" maxlength="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Jabatan penanda tangan</label>
                                <input type="text" name="signer_jabatan" class="form-control form-control-sm"
                                    value="{{ old('signer_jabatan', $setting->signer_jabatan) }}" maxlength="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Nama penanda tangan</label>
                                <input type="text" name="signer_name" class="form-control form-control-sm"
                                    placeholder="{{ $school->principal_full_name }}"
                                    value="{{ old('signer_name', $setting->signer_name) }}" maxlength="150">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">NIP penanda tangan</label>
                                <input type="text" name="signer_nip" class="form-control form-control-sm"
                                    placeholder="{{ $school->principal_nupy_display }}"
                                    value="{{ old('signer_nip', $setting->signer_nip) }}" maxlength="50">
                            </div>
                        </div>

                        <hr class="my-3">

                        <label class="form-label small fw-semibold mb-2">Judul kelompok mata pelajaran</label>
                        <div class="row g-2">
                            @foreach ($groups as $key => $defaultLabel)
                                <div class="col-6">
                                    <input type="text" name="group_labels[{{ $key }}]"
                                        class="form-control form-control-sm"
                                        value="{{ old('group_labels.' . $key, $setting->groupLabel($key)) }}"
                                        placeholder="{{ $defaultLabel }}" maxlength="100">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-light py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="mb-0"><i class="ri-list-check-2 me-1"></i>Mata Pelajaran pada SKL</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="addRow()">
                        <i class="ri-add-line me-1"></i>Tambah baris
                    </button>
                    <button type="submit" class="btn btn-sm btn-soft-secondary"
                        formaction="{{ route('user.students.graduation-certificate.settings.import', ['userId' => $userId]) }}"
                        formmethod="POST" formnovalidate>
                        <i class="ri-download-2-line me-1"></i>Tarik mapel dari master
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm skl-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:56px">Tampil</th>
                            <th>Nama di SKL</th>
                            <th style="width:220px">Kelompok</th>
                            <th style="width:90px">Urutan</th>
                            <th style="width:56px"></th>
                        </tr>
                    </thead>
                    <tbody id="rowsBody">
                        @foreach ($rows as $i => $row)
                            <tr class="{{ $row->is_visible ? '' : 'is-hidden' }}">
                                <td class="text-center">
                                    <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $row->id }}">
                                    <input type="checkbox" class="form-check-input" name="rows[{{ $i }}][is_visible]"
                                        value="1" @checked($row->is_visible)>
                                </td>
                                <td>
                                    <input type="hidden" name="rows[{{ $i }}][subject_id]" value="{{ $row->subject_id }}">
                                    <input type="text" name="rows[{{ $i }}][display_name]" class="form-control form-control-sm"
                                        value="{{ $row->display_name }}" maxlength="150" required>
                                </td>
                                <td>
                                    <select name="rows[{{ $i }}][group_key]" class="form-select form-select-sm">
                                        @foreach ($groups as $key => $label)
                                            <option value="{{ $key }}" @selected($row->group_key === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="rows[{{ $i }}][sort_order]" class="form-control form-control-sm"
                                        value="{{ $row->sort_order }}" min="0" max="999">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-soft-danger" onclick="removeRow(this)">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="text-muted small">
                        Nilai diambil dari rata-rata nilai akhir (sumatif) pada tahun ajaran kelulusan.
                        Baris tanpa mapel master akan dicetak tanpa nilai.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line me-1"></i>Simpan Format
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card mt-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="ri-shield-check-line me-1"></i>Tanda Tangan Digital &amp; Validasi</h6>
        </div>
        <div class="card-body">
            @include('documents._signature_settings', ['type' => 'skl'])
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="ri-eye-line me-1"></i>Pratinjau</h6>
        </div>
        <div class="card-body">
            @if ($sampleStudents->isEmpty())
                <p class="text-muted small mb-0">Belum ada santri berstatus <strong>Lulus</strong> untuk dijadikan contoh pratinjau.</p>
            @else
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Santri lulus</label>
                        <select id="previewStudent" class="form-select form-select-sm">
                            @foreach ($sampleStudents as $sample)
                                <option value="{{ $sample->id }}">
                                    {{ $sample->name }}{{ $sample->graduation_year ? ' — ' . $sample->graduation_year : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-soft-primary btn-sm" onclick="openPreview()">
                            <i class="ri-printer-line me-1"></i>Buka PDF SKL
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        let rowIndex = {{ $rows->count() }};

        function addRow() {
            const body = document.getElementById('rowsBody');
            const template = `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" name="rows[__I__][is_visible]" value="1" checked>
                    </td>
                    <td>
                        <input type="text" name="rows[__I__][display_name]" class="form-control form-control-sm" maxlength="150" required>
                    </td>
                    <td>
                        <select name="rows[__I__][group_key]" class="form-select form-select-sm">
                            @foreach ($groups as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="rows[__I__][sort_order]" class="form-control form-control-sm" value="__I__" min="0" max="999">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-soft-danger" onclick="removeRow(this)">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>`;

            body.insertAdjacentHTML('beforeend', template.replaceAll('__I__', rowIndex++));
        }

        function removeRow(button) {
            button.closest('tr').remove();
        }

        document.addEventListener('change', function (event) {
            if (event.target.matches('input[type="checkbox"][name$="[is_visible]"]')) {
                event.target.closest('tr').classList.toggle('is-hidden', !event.target.checked);
            }
        });

        function openPreview() {
            const select = document.getElementById('previewStudent');
            if (!select) {
                return;
            }

            window.open(
                '{{ url('/') }}/{{ $userId }}/students/' + select.value + '/graduation-certificate',
                '_blank'
            );
        }
    </script>
@endsection
