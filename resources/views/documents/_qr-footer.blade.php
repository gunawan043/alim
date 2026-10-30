@php
    /**
     * @var array|null $verification
     *   document, token, url, qr_data_uri
     */
@endphp

@if(! empty($verification) && empty($verification['preview']))
    <div style="margin-top:8mm;border-top:1px dashed #94a3b8;padding-top:3mm;display:flex;align-items:center;gap:4mm;font-family:Arial,sans-serif;">
        @if(! empty($verification['qr_data_uri']))
            <img src="{{ $verification['qr_data_uri'] }}" alt="QR Verifikasi" style="width:22mm;height:22mm;flex-shrink:0;">
        @endif
        <div style="font-size:8pt;color:#334155;line-height:1.45;">
            <strong>Verifikasi Dokumen — QR ini terpisah dari blok tanda tangan.</strong><br>
            Pindai QR atau buka tautan berikut untuk memverifikasi keaslian:
            <span style="word-break:break-all;color:#1d4ed8;">{{ $verification['url'] }}</span>
            @if(($verification['document']->validation_mode ?? 'qr') === 'pades')
                <br><span style="color:#64748b;">Dokumen juga ditandatangani PAdES; validasi kriptografis dapat dilakukan di pembaca PDF.</span>
            @else
                <br><span style="color:#64748b;">QR ini merupakan pengesahan dokumen pada registry ALIM.</span>
            @endif
        </div>
    </div>
@endif
