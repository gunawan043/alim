@php
    $kopNama = $school?->kop_nama ?: $school?->name ?: 'Satuan Pendidikan';
    $kopAlamat = $school?->kop_alamat ?: $school?->address;
    $kopTelp = $school?->kop_telp ?: $school?->phone;
    $kopEmail = $school?->kop_email ?: $school?->email;
    $kopWebsite = $school?->kop_website ?: $school?->website;
    $kopNpsn = $school?->npsn ?: $school?->kop_npsn;

    $logoBase64 = null;
    if ($school?->logo_path) {
        $logoAbs = storage_path('app/public/'.$school->logo_path);
        if (file_exists($logoAbs)) {
            $ext = strtolower(pathinfo($logoAbs, PATHINFO_EXTENSION) ?: 'png');
            $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoAbs));
        }
    }
@endphp

<div class="kop">
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="kop-text">
                <div class="nama">{{ $kopNama }}</div>
                @if($kopAlamat)<div class="sub">{{ $kopAlamat }}</div>@endif
                <div class="sub">
                    @if($kopTelp) Telp. {{ $kopTelp }} @endif
                    @if($kopEmail) · {{ $kopEmail }} @endif
                    @if($kopWebsite) · {{ $kopWebsite }} @endif
                </div>
                @if($kopNpsn)<div class="npsn">NPSN: {{ $kopNpsn }}</div>@endif
            </td>
            <td style="width:60px"></td>
        </tr>
    </table>
</div>
