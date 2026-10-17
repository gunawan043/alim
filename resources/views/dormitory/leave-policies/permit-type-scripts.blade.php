{{--
    Scripts untuk interaksi modal hapus dan modal sukses pada halaman jenis izin.
--}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = @json(route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]));

    // ── Open Create Modal (tombol "Tambah Jenis Izin") ───
    const addBtn = document.querySelector('.js-add-permit');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            // Reset form ke mode create
            document.getElementById('permitTypeForm').action = '';
            document.getElementById('permitTypeFormMethod').value = 'POST';
            document.getElementById('permitTypeId').value = '';
            document.getElementById('ptLabel').value = '';
            document.getElementById('ptCode').value = '';
            document.getElementById('ptCategory').value = 'custom';
            document.getElementById('ptSortOrder').value = 100;
            document.getElementById('ptIcon').value = 'ri-file-list-3-line';
            document.getElementById('ptColor').value = 'primary';
            document.getElementById('ptDescription').value = '';
            document.getElementById('ptIsActive').checked = true;
            document.getElementById('permitTypeModalTitle').textContent = 'Tambah Jenis Izin';
            // Tampilkan modal
            const modal = new bootstrap.Modal(document.getElementById('permitTypeModal'));
            modal.show();
        });
    }

    // ── Handle Modal Form Submit (AJAX) ───
    const form = document.getElementById('permitTypeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="ri-spin-line me-1"></i> Menyimpan...';
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    // Tampilkan error di field jika ada
                    const errors = data.errors;
                    if (errors && typeof errors === 'object') {
                        Object.entries(errors).forEach(([field, messages]) => {
                            const msg = Array.isArray(messages) ? messages[0] : messages;
                            const input = document.getElementById('pt' + field.charAt(0).toUpperCase() + field.slice(1));
                            if (input) {
                                input.classList.add('is-invalid');
                                // Buang error sebelumnya
                                const existing = input.nextElementSibling;
                                if (existing && existing.classList.contains('invalid-feedback')) {
                                    existing.remove();
                                }
                                const feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback';
                                feedback.textContent = msg;
                                input.parentNode.insertBefore(feedback, input.nextSibling);
                            }
                        });
                    } else {
                        alert('Error: ' + (data.message || 'Simpan gagal.'));
                    }
                }
            })
            .catch(err => {
                alert('Jaringan: ' + err.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // ── Open Delete Modal (isi action + label dinamis) ───
    document.querySelectorAll('.js-delete-permit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const label = this.dataset.label;
            const deleteForm = document.getElementById('deletePermitTypeForm');
            deleteForm.action = `${baseUrl}/permit-types/${id}`;
            deleteForm.querySelector('input[name="id"]').value = id;
            const msgEl = document.getElementById('confirmDeleteMessage');
            if (msgEl) {
                msgEl.innerHTML = 'Anda akan menghapus jenis izin <strong>"' + label + '"</strong>. Data konfigurasi lama dan histori izin tetap tersimpan.';
            }
            const modal = new bootstrap.Modal(document.getElementById('deletePermitTypeModal'));
            modal.show();
        });
    });

    // ── Auto-show Modal Sukses jika ada flash message ───
    @if (session('success_message'))
        const successModalEl = document.getElementById('successPermitTypeModal');
        if (successModalEl) {
            const modal = new bootstrap.Modal(successModalEl);
            modal.show();
        }
    @endif
});
</script>
