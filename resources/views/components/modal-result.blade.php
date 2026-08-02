@props([
    'id' => 'resultModal',
    'icon' => 'https://cdn.lordicon.com/lupuorrc.json',
    'primaryColor' => '#121331',
    'secondaryColor' => '#08a88a',
    'title' => 'Berhasil!',
    'message' => '',
    'primaryActionUrl' => null,
    'primaryActionLabel' => 'Selesai',
    'secondaryActionLabel' => 'Tutup',
])

<div {{ $attributes->merge(['class' => 'modal fade']) }} id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <lord-icon src="{{ $icon }}" trigger="loop" colors="primary:{{ $primaryColor }},secondary:{{ $secondaryColor }}" style="width:120px;height:120px"></lord-icon>

                <div class="mt-4">
                    <h4 class="mb-3">{{ $title }}</h4>
                    @if($message)
                        <p class="text-muted mb-4">{!! $message !!}</p>
                    @endif
                    <div class="hstack gap-2 justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-link link-{{ $secondaryActionLabel === 'Tutup' ? 'success' : 'secondary' }} fw-medium material-shadow-none" data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i> {{ $secondaryActionLabel }}</a>
                        @if($primaryActionUrl)
                            <a href="{{ $primaryActionUrl }}" class="btn btn-success">{{ $primaryActionLabel }}</a>
                        @else
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">{{ $primaryActionLabel }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
