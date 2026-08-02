{{-- Modal konfirmasi hapus jenis izin --}}
<div class="modal fade" id="deletePermitTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Hapus Jenis Izin?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmDeleteMessage">Anda akan menghapus jenis izin.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" id="deletePermitTypeForm" action="">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE" />
                    <input type="hidden" name="id" value="" />
                    <button type="submit" class="btn btn-danger">Hapus Tetap</button>
                </form>
            </div>
        </div>
    </div>
</div>
