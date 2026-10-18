<?php

namespace App\Services\Sarpras;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoProofService
{
    /**
     * Simpan foto bukti. Prioritas: tautan Google Drive/URL yang diberikan,
     * lalu upload file ke disk yang dikonfigurasi.
     *
     * @return array{path:?string,url:?string,provider:string}
     */
    public function store(?UploadedFile $file, ?string $url, string $folder): array
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return ['path' => null, 'url' => $url, 'provider' => 'external'];
        }

        if (! $file) {
            return ['path' => null, 'url' => null, 'provider' => 'none'];
        }

        $disk = config('sarpras.photo_disk', 'public');
        $path = $file->store($folder, $disk);

        return [
            'path' => $path,
            'url' => $disk === 'public' ? Storage::disk('public')->url($path) : null,
            'provider' => $disk,
        ];
    }
}
