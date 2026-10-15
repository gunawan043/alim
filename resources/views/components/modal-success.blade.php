{{-- Modal sukses/verifikasi bergaya lord-icon (zoomIn). --}}
@props([
    'id' => 'successModal',
    'title' => 'Berhasil',
    'message' => '',
    'icon' => 'https://cdn.lordicon.com/lupuorrc.json',
    'primaryColor' => '#08a88a',
    'secondaryColor' => '#121331',
])

<div class="modal fade zoomIn" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mt-2 text-center">
                    <lord-icon src="{{ $icon }}" trigger="loop"
                        colors="primary:{{ $primaryColor }},secondary:{{ $secondaryColor }}" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <h4>{{ $title }}</h4>
                        @if($message)
                            <p class="text-muted mx-4 mb-0">{!! $message !!}</p>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-success" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
