@if(session('error') && str_contains((string) session('error'), 'Publikasi dibatalkan'))
    <div class="modal fade" id="publishBlockedModal" tabindex="-1" aria-labelledby="publishBlockedTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <img src="https://media.lordicon.com/icons/wired/outline/926-road-barrier.gif"
                         alt="Publikasi tidak dapat dilakukan"
                         style="width:140px;height:140px;object-fit:contain;filter:brightness(0) saturate(100%) invert(23%) sepia(93%) saturate(3000%) hue-rotate(345deg) brightness(95%) contrast(100%);">
                    <h4 class="mt-3 mb-2" id="publishBlockedTitle">Publikasi Dibatalkan</h4>
                    <p class="text-muted mb-4">{{ session('error') }}</p>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="ri-check-line me-1"></i>Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var element = document.getElementById('publishBlockedModal');
                if (element && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(element).show();
                }
            });
        </script>
    @endpush
@endif
