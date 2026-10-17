<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dokumen ISO</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0"
       style="background:#ffffff;margin:30px auto;border-radius:8px;overflow:hidden">

    <!-- HEADER -->
    <tr>
        <td style="background:#0f4c9e;padding:20px;text-align:center">
            <img src="https://raw.githubusercontent.com/gunawan043/alim/main/public/build/images/alim-light-name.png"
                 alt="ALIM"
                 height="70"
                 style="display:block;margin:auto">
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding:30px;color:#333">

            {{-- Aksi badge --}}
            @php
                $aksiConfig = match ($aksi) {
                    'dibuat'      => ['icon' => '🆕', 'label' => 'Dokumen Baru Dibuat', 'color' => '#198754', 'bg' => '#d1e7dd'],
                    'diperbarui'  => ['icon' => '✏️', 'label' => 'Dokumen Diperbarui',  'color' => '#0d6efd', 'bg' => '#cfe2ff'],
                    'dihapus'     => ['icon' => '🗑️', 'label' => 'Dokumen Dihapus',      'color' => '#dc3545', 'bg' => '#f8d7da'],
                    default        => ['icon' => '📄', 'label' => 'Perubahan Dokumen',     'color' => '#6c757d', 'bg' => '#f8f9fa'],
                };
            @endphp

            <div style="display:inline-block;padding:6px 16px;border-radius:20px;
                        background:{{ $aksiConfig['bg'] }};color:{{ $aksiConfig['color'] }};
                        font-weight:bold;font-size:14px;margin-bottom:20px">
                {{ $aksiConfig['icon'] }} {{ $aksiConfig['label'] }}
            </div>

            <h2 style="margin-top:0;color:#0f4c9e">
                {{ $dokumen->nama_dokumen }}
            </h2>

            <p>Yth. <strong>{{ $recipient->name }}</strong>,</p>

            <p>
                Terdapat perubahan pada dokumen ISO di divisi
                <strong>{{ $dokumen->divisi->nama ?? '—' }}</strong>:
            </p>

            <!-- Detail table -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="margin:20px 0;border:1px solid #dee2e6;border-radius:8px;overflow:hidden">
                <tr style="background:#f8f9fa">
                    <td style="padding:10px 16px;font-weight:bold;color:#495057;border-bottom:1px solid #dee2e6;width:35%">Kode Dokumen</td>
                    <td style="padding:10px 16px;color:#212529;border-bottom:1px solid #dee2e6">
                        <code style="background:#e7f1ff;color:#0d47a1;padding:2px 8px;border-radius:4px;font-size:13px">
                            {{ $dokumen->kode_dokumen ?? '—' }}
                        </code>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 16px;font-weight:bold;color:#495057;border-bottom:1px solid #dee2e6">Nama Dokumen</td>
                    <td style="padding:10px 16px;color:#212529;border-bottom:1px solid #dee2e6">{{ $dokumen->nama_dokumen }}</td>
                </tr>
                <tr style="background:#f8f9fa">
                    <td style="padding:10px 16px;font-weight:bold;color:#495057;border-bottom:1px solid #dee2e6">Kategori</td>
                    <td style="padding:10px 16px;color:#212529;border-bottom:1px solid #dee2e6">
                        @if($dokumen->kategori === 'FORMULIR')
                            <span style="background:#cff4fc;color:#0aa8b5;padding:2px 8px;border-radius:4px;font-size:12px">FORMULIR</span>
                        @else
                            <span style="background:#cfe2ff;color:#0d47a1;padding:2px 8px;border-radius:4px;font-size:12px">PROSEDUR</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 16px;font-weight:bold;color:#495057;border-bottom:1px solid #dee2e6">Revisi</td>
                    <td style="padding:10px 16px;color:#212529;border-bottom:1px solid #dee2e6">{{ $dokumen->revisi_ke ?? '0' }}</td>
                </tr>
                <tr style="background:#f8f9fa">
                    <td style="padding:10px 16px;font-weight:bold;color:#495057;border-bottom:1px solid #dee2e6">Tanggal Berlaku</td>
                    <td style="padding:10px 16px;color:#212529;border-bottom:1px solid #dee2e6">
                        {{ $dokumen->tanggal_berlaku?->format('d/m/Y') ?? '—' }}
                    </td>
                </tr>
                @if($oldNama && $oldNama !== $dokumen->nama_dokumen)
                <tr>
                    <td style="padding:10px 16px;font-weight:bold;color:#495057">Nama Sebelumnya</td>
                    <td style="padding:10px 16px;color:#dc3545;text-decoration:line-through">{{ $oldNama }}</td>
                </tr>
                @endif
            </table>

            <!-- CTA Button -->
            <div style="text-align:center;margin:30px 0">
                <a href="{{ url('/personalia/' . $recipient->id . '/dokumen-iso') }}"
                   style="display:inline-block;padding:12px 30px;
                          background:#0f4c9e;color:#ffffff;text-decoration:none;
                          border-radius:6px;font-weight:bold;font-size:14px">
                    📋 Lihat Daftar Dokumen ISO
                </a>
            </div>

            <hr style="margin:30px 0;border:none;border-top:1px solid #eee">

            <p style="font-size:12px;color:#777">
                Email ini dikirim otomatis oleh sistem ALIM Dokumen ISO.
                Anda menerima email ini karena tersubscribe ke divisi terkait.
            </p>

        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background:#f1f1f1;padding:15px;text-align:center;
                   font-size:12px;color:#666">
            © {{ date('Y') }} ALIM by Pondok Pesan Aren Malaysia
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>