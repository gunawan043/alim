    <form method="POST" action="{{ route('user.kurikulum.perangkat.update', ['userId' => $userId, 'id' => $perangkat->id]) }}">
        @csrf @method('PUT')

        {{-- ── Informasi Umum ─────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i> Informasi Umum</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" value="{{ $perangkat->judul }}" required maxlength="255" {{ $disabled }}>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Kelas</label>
                        <div class="form-control bg-light">{{ $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}</div>
                        <div class="form-text">Perangkat berlaku untuk seluruh rombel pada tingkat ini.</div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Format Cetak</label>
                        <input type="hidden" name="tipe" value="{{ $isAgama ? 'agama' : 'umum' }}">
                        <div class="form-control bg-light">
                            {{ $isAgama ? 'Agama / Bahasa Arab' : 'Umum' }}
                        </div>
                        <div class="form-text">Mengikuti jenis mapel.</div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" {{ $disabled }}>
                            <option value="draft" {{ $perangkat->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            @if($perangkat->status === 'final')
                                <option value="final" selected>Final — via approval rumpun</option>
                            @endif
                        </select>
                        <div class="form-text">Final ditetapkan otomatis setelah seluruh guru serumpun menyetujui.</div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Alokasi Waktu</label>
                        <input type="text" name="desain[alokasi_waktu]" class="form-control"
                               value="{{ $alokasiValue }}" maxlength="100" {{ $disabled }}>
                        <div class="form-text">Otomatis dari total JP ATP; dapat disesuaikan.</div>
                    </div>
                    @if($isAgama)
                        <div class="col-12">
                            <label class="form-label">Materi Pelajaran</label>
                            <textarea name="desain[identifikasi_materi]" rows="2" class="form-control" maxlength="5000" {{ $disabled }}>{{ old('desain.identifikasi_materi', $perangkat->desainValue('identifikasi_materi')) }}</textarea>
                        </div>
                    @endif
                    <div class="col-lg-9">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="catatan" class="form-control" value="{{ $perangkat->catatan }}" maxlength="2000" {{ $disabled }}>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CP & TP otomatis dari ATP ──────────────────────────── --}}
        @if($cps->isNotEmpty() || $tps->isNotEmpty())
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-link text-primary me-1"></i> CP &amp; TP (otomatis dari ATP)</h5>
                    <span class="badge bg-secondary-subtle text-secondary">Tidak perlu diisi ulang</span>
                </div>
                <div class="card-body">
                    @if(! $isAgama && $cps->isNotEmpty())
                        <h6 class="text-muted small text-uppercase mb-2">Capaian Pembelajaran</h6>
                        @foreach($cps as $cp)
                            <p class="small mb-1">
                                <span class="badge bg-info-subtle text-info">Fase {{ $cp->fase }}</span>
                                @if($cp->elemen) <span class="badge bg-light text-dark">{{ $cp->elemen }}</span> @endif
                                {{ $cp->deskripsi }}
                            </p>
                        @endforeach
                    @endif
                    @if($tps->isNotEmpty())
                        <h6 class="text-muted small text-uppercase mb-2 mt-3">Tujuan Pembelajaran</h6>
                        <ol class="small mb-0 ps-3">
                            @foreach($tps as $tp)
                                <li class="mb-1"><strong>{{ $tp->kode_tp }}</strong> — {{ $tp->deskripsi }}</li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Desain Pembelajaran / Pengalaman / Asesmen per tipe ── --}}
        @foreach($perangkat->groups() as $groupKey => $group)
            @continue($groupKey === 'asesmen')
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="{{ $group['icon'] }} text-primary me-1"></i> {{ $group['label'] }}</h5>
                    <p class="text-muted mb-0 small mt-1">{{ $group['desc'] }}</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($group['keys'] as $key)
                            @if(($key === 'model_pembelajaran' && ! $isAgama) || ($key === 'praktik_pedagogik' && $isAgama))
                                @continue
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">{{ $sections[$key] ?? $key }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        @if(($curriculumProfile ?? null)?->isKemenag())
            {{-- ── Isian Tambahan KBC ─────────────────────────────── --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-heart-3-line text-danger me-1"></i> Isian Format KBC</h5>
                    <p class="text-muted mb-0 small mt-1">Dipakai untuk cetak format KBC; format Pondok tetap memakai isian yang sama.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($isAgama)
                            <div class="col-md-6">
                                <label class="form-label">Dimensi Profil Lulusan</label>
                                <textarea name="desain[profil_lulusan]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue('profil_lulusan') }}</textarea>
                            </div>
                        @endif
                        @foreach([
                            'kbc_kesiapan_belajar' => 'Identifikasi Kesiapan Peserta Didik',
                            'kbc_karakteristik_materi' => 'Karakteristik Materi Pelajaran',
                            'kbc_iklim_budaya' => 'Iklim / Budaya Madrasah',
                            'kbc_topik_kontekstual' => 'Topik Pembelajaran Kontekstual',
                            'kbc_indikator' => 'Indikator Ketercapaian',
                            'kbc_diferensiasi' => 'Diferensiasi Pembelajaran',
                        ] as $key => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if((! $isAgama) && (($curriculumProfile ?? null)?->isDinas() ?? true))
            {{-- ── Isian Tambahan Merdeka ─────────────────────────── --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-government-line text-primary me-1"></i> Isian Format Merdeka</h5>
                    <p class="text-muted mb-0 small mt-1">Dipakai untuk cetak format Merdeka; format Pondok tetap memakai isian yang sama.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'kompetensi_awal' => 'Kompetensi Awal',
                            'sarana_prasarana' => 'Sarana dan Prasarana',
                            'target_peserta_didik' => 'Target Peserta Didik',
                            'iktp' => 'Indikator Ketercapaian Tujuan Pembelajaran (IKTP)',
                            'merdeka_pemahaman_bermakna' => 'Pemahaman Bermakna',
                            'merdeka_pertanyaan_pemantik' => 'Pertanyaan Pemantik',
                            'model_pembelajaran' => 'Model Pembelajaran',
                            'pengayaan' => 'Pengayaan',
                            'remedial' => 'Remedial',
                            'refleksi_guru' => 'Refleksi Guru',
                            'refleksi_peserta_didik' => 'Refleksi Peserta Didik',
                            'lampiran_lkpd' => 'Lampiran — LKPD',
                            'lampiran_bahan_bacaan' => 'Lampiran — Bahan Bacaan',
                            'glosarium' => 'Glosarium',
                            'daftar_pustaka' => 'Daftar Pustaka',
                        ] as $key => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <textarea name="desain[{{ $key }}]" rows="2" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Matriks Asesmen Formatif dan Sumatif ───────────────── --}}
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-draft-line text-primary me-1"></i> Asesmen Formatif dan Sumatif</h5>
                <p class="text-muted mb-0 small mt-1">Isi teknik, indikator, dan instrumen/bukti untuk awal, proses, dan akhir pembelajaran.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:18%">Jenis Asesmen</th>
                                <th style="width:20%">Teknik</th>
                                <th style="width:28%">Indikator</th>
                                <th>Instrumen/Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_ROWS as $rowKey => $rowLabel)
                                <tr>
                                    <td class="fw-semibold">{{ $rowLabel }}</td>
                                    @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_COLUMNS as $columnKey => $columnLabel)
                                        @continue($columnKey === 'jenis')
                                        <td>
                                            <textarea name="desain[asesmen_{{ $rowKey }}_{{ $columnKey }}]" rows="2"
                                                      class="form-control form-control-sm" maxlength="5000" {{ $disabled }}>{{ old('desain.asesmen_'.$rowKey.'_'.$columnKey, $perangkat->desainValue('asesmen_'.$rowKey.'_'.$columnKey)) }}</textarea>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Bagian fondasi lama (bila masih terisi) ────────────── --}}
        @if(! empty($legacyFilled))
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line text-secondary me-1"></i> Fondasi Pembelajaran Mendalam</h5>
                    <p class="text-muted mb-0 small mt-1">Bagian dari struktur sebelumnya yang masih terisi — tetap dipertahankan.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($legacyFilled as $key => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($canEdit)
            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-success">
                    <i class="ri-save-line align-bottom me-1"></i> Simpan {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}
                </button>
                <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light">Batal</a>
            </div>
        @endif
    </form>