{{-- Modal sukses --}}
<div class="modal fade" id="successPermitTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" style="color: #08a88a;">Berhasil!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="ri-check-circle-fill" style="font-size: 3rem; color: #08a88a;"></i>
                <p class="mt-3 mb-0">{{ session('success_message', 'Data telah berhasil disimpan.') }}</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
