{{--
    Modal quick-create Gedung Sarpras dari form Asrama.
    Parameter: $userId, $dormitory, $selectId (id elemen <select> tujuan), $previewFn (nama fungsi JS refresh preview).
--}}
<div class="modal fade" id="newBuildingModal_{{ $selectId }}" tabindex="-1" aria-labelledby="newBuildingModalLabel_{{ $selectId }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newBuildingModalLabel_{{ $selectId }}"><i class="ri-building-2-line me-1" aria-hidden="true"></i> Tambah Gedung Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="newBuildingForm_{{ $selectId }}">
                <div class="modal-body">
                    <div id="newBuildingError_{{ $selectId }}" class="alert alert-danger d-none mb-3" role="alert"></div>
                    <div class="mb-3">
                        <label for="new_building_name_{{ $selectId }}" class="form-label">Nama Gedung <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="new_building_name_{{ $selectId }}" class="form-control" placeholder="Contoh: Gedung Abu Bakar" required maxlength="100">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="new_building_code_{{ $selectId }}" class="form-label">Kode</label>
                            <input type="text" name="code" id="new_building_code_{{ $selectId }}" class="form-control" placeholder="Otomatis jika kosong" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label for="new_building_gender_{{ $selectId }}" class="form-label">Jenis</label>
                            <select name="gender" id="new_building_gender_{{ $selectId }}" class="form-select">
                                <option value="campur">Campur</option>
                                <option value="putra">Putra</option>
                                <option value="putri">Putri</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="new_building_notes_{{ $selectId }}" class="form-label">Catatan</label>
                        <textarea name="notes" id="new_building_notes_{{ $selectId }}" class="form-control" rows="2" maxlength="191"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="newBuildingSubmit_{{ $selectId }}">
                        <i class="ri-save-line me-1" aria-hidden="true"></i> Simpan Gedung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('newBuildingForm_{{ $selectId }}');
        var errorBox = document.getElementById('newBuildingError_{{ $selectId }}');
        var submitBtn = document.getElementById('newBuildingSubmit_{{ $selectId }}');

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            errorBox.classList.add('d-none');
            submitBtn.disabled = true;

            try {
                var response = await fetch('{{ route("user.asrama.wings.buildings.store", ["userId" => $userId, "asramaUuid" => $dormitory->id]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                var payload = await response.json();

                if (!response.ok) {
                    var message = payload.message || 'Gagal menyimpan gedung.';
                    if (payload.errors) {
                        message = Object.values(payload.errors).flat().join(' ');
                    }
                    errorBox.textContent = message;
                    errorBox.classList.remove('d-none');
                    return;
                }

                var select = document.getElementById('{{ $selectId }}');
                var label = payload.name + ' (' + payload.gender + ')';
                var option = new Option(label, payload.id, false, true);
                select.add(option);
                select.value = payload.id;

                var modal = bootstrap.Modal.getInstance(document.getElementById('newBuildingModal_{{ $selectId }}'));
                modal.hide();
                form.reset();

                @if(!empty($previewFn))
                    {{ $previewFn }}();
                @endif
            } catch (err) {
                errorBox.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
                errorBox.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
            }
        });
    });
</script>
