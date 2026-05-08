<!-- Remove Item Modal -->
<div id="removeItemModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close"></button>
            </div>
            <div class="modal-body">
                <div class="mt-2 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                        colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <h4>Yakin Hapus Data?</h4>
                        <p class="text-muted mx-4 mb-0" id="removeItemModalText">Data yang dihapus tidak bisa dikembalikan.</p>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn w-sm btn-danger" id="remove-item-btn">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('removeItemModal');
    if (!modal) return;

    var modalText = document.getElementById('removeItemModalText');
    var confirmBtn = document.getElementById('remove-item-btn');
    var targetForm = null;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.delete-btn');
        if (!btn) return;

        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;

        if (btn.dataset.message) modalText.textContent = btn.dataset.message;
        else modalText.textContent = 'Data yang dihapus tidak bisa dikembalikan.';

        targetForm = form;
        new bootstrap.Modal(modal).show();
    });

    confirmBtn.addEventListener('click', function () {
        if (targetForm) targetForm.submit();
    });
});
</script>