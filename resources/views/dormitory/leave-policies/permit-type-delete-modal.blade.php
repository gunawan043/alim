{{-- Modal konfirmasi hapus jenis izin --}}
<form method="POST" id="deletePermitTypeForm" action="" class="d-none">
    @csrf
    <input type="hidden" name="_method" value="DELETE" />
    <input type="hidden" name="id" value="" />
</form>

<x-modal-confirm id="deletePermitTypeModal" class="zoomIn"
    icon="https://cdn.lordicon.com/gsqxdxog.json"
    primaryColor="#f7b84b" secondaryColor="#f06548"
    title="Hapus Jenis Izin?"
    submitLabel="Hapus Tetap"
    submitIcon="ri-delete-bin-line"
    submitClass="btn-danger"
    formId="deletePermitTypeForm">
    <p class="text-center text-muted mb-0" id="confirmDeleteMessage">Anda akan menghapus jenis izin.</p>
</x-modal-confirm>
<script src="https://cdn.lordicon.com/lordicon.js"></script>
