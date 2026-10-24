    {{-- Modal Upload Kop, TTD & Stempel --}}
    <div class="modal fade" id="schoolMediaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data"
                      action="{{ route('user.schools.media', ['userId' => $userId, 'schoolId' => $school->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-upload-cloud-2-line me-2 text-primary"></i>Upload Kop, TTD &amp; Stempel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small">
                            <i class="ri-information-line me-1"></i>
                            Format gambar JPG/PNG. Logo dan kop maks. 2 MB; TTD/stempel maks. 1 MB. Centang hapus jika ingin menghapus file lama.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Logo Sekolah</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($school->logo_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ $school->logo_url }}" width="52" height="52" class="rounded" style="object-fit:cover" alt="Logo">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_logo_path" value="1"> Hapus logo lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">File Kop Surat</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($school->kop_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->kop_path) }}" height="52" class="rounded" style="object-fit:contain" alt="Kop">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_kop_path" value="1"> Hapus kop lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">TTD Kepala Sekolah (KSP)</label>
                                <input type="file" name="ttd_ksp_path" class="form-control" accept="image/png,image/jpeg">
                                @if($school->ttd_ksp_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->ttd_ksp_path) }}" height="60" class="rounded" style="object-fit:contain" alt="TTD KSP">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_ttd_ksp_path" value="1"> Hapus TTD lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stempel Sekolah</label>
                                <input type="file" name="stamp_path" class="form-control" accept="image/png,image/jpeg">
                                @if($school->stamp_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->stamp_path) }}" height="60" class="rounded" style="object-fit:contain" alt="Stempel">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_stamp_path" value="1"> Hapus stempel lama
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line me-1"></i>Simpan Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
