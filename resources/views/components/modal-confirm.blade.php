@props([
    'id' => 'confirmModal',
    'icon' => 'https://cdn.lordicon.com/lupuorrc.json',
    'primaryColor' => '#121331',
    'secondaryColor' => '#08a88a',
    'title' => 'Konfirmasi',
    'message' => '',
    'submitLabel' => 'Lanjutkan',
    'submitIcon' => 'ri-check-line',
    'submitClass' => 'btn-success',
    'formId' => null,
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

                    <div class="text-start mb-4">
                        {{ $slot }}
                    </div>

                    <div class="hstack gap-2 justify-content-center">
                        <button type="button" class="btn btn-link link-secondary fw-medium material-shadow-none" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1 align-middle"></i> Batal
                        </button>
                        <button type="submit"
                                @if($formId) form="{{ $formId }}" @endif
                                class="btn {{ $submitClass }}">
                            <i class="{{ $submitIcon }} me-1"></i>{{ $submitLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>