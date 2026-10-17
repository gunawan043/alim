@php
    $flashModals = [];
    if (session('success')) {
        $flashModals[] = ['id' => 'flashSuccessModal', 'tone' => 'success', 'title' => 'Berhasil', 'message' => session('success')];
    }
    if (session('error')) {
        $flashModals[] = ['id' => 'flashErrorModal', 'tone' => 'danger', 'title' => 'Gagal', 'message' => session('error')];
    }
    if (session('warning')) {
        $flashModals[] = ['id' => 'flashWarningModal', 'tone' => 'warning', 'title' => 'Peringatan', 'message' => session('warning')];
    }
    if (session('info')) {
        $flashModals[] = ['id' => 'flashInfoModal', 'tone' => 'info', 'title' => 'Informasi', 'message' => session('info')];
    }
    if (! session('error') && isset($errors) && $errors->any()) {
        $flashModals[] = ['id' => 'flashValidationModal', 'tone' => 'danger', 'title' => 'Periksa Input', 'message' => $errors->first()];
    }
@endphp

@foreach($flashModals as $modal)
    <x-modal-notify :id="$modal['id']" :tone="$modal['tone']" :title="$modal['title']" :message="$modal['message']" />
@endforeach

@if(count($flashModals))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @foreach($flashModals as $modal)
                    (function () {
                        var el = document.getElementById('{{ $modal['id'] }}');
                        if (el && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(el).show();
                        }
                    })();
                @endforeach
            });
        </script>
    @endpush
@endif
