<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadsSecure
{
    /**
     * Store uploaded files securely with mime validation.
     *
     * @param  array<string, mixed>  $rules
     *                                       ['field_name' => ['dir', 'mimes', 'max_kb']]
     *                                       mimes defaults to ['jpg','jpeg','png','gif','webp'] for images.
     *                                       max_kb defaults to 2048 (2MB).
     * @return array<string, mixed> Augmented request rules.
     */
    protected function secureUploadRules(array $rules): array
    {
        foreach ($rules as $field => $rule) {
            if (in_array($field, ['photo_path', 'file_path', 'cover_photo', 'logo', 'document'])) {
                $rules[$field] = [
                    'nullable',
                    'file',
                    'mimes:'.implode(',', $rule['mimes'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp']),
                    'max:'.($rule['max_kb'] ?? 2048),
                ];
            }
        }

        return $rules;
    }

    /**
     * Safely store a single uploaded file.
     *
     * Accepts two calling styles:
     *
     * 1. `storeSecureFile(Request $request, string $field, string $disk, string $folder, ?string $oldPath)`
     *    — Laravel-style. The request is consulted via `$request->file($field)`.
     *
     * 2. `storeSecureFile(UploadedFile $file, string $disk, string $folder, ?string $oldPath)`
     *    — file is passed directly. `$disk` defaults to 'public' and `$folder`
     *    is the storage prefix.
     *
     * @return string|null Stored path or null if no file was provided.
     */
    protected function storeSecureFile(mixed $source, string $arg2 = 'public', string $arg3 = 'uploads', mixed $arg4 = null): ?string
    {
        $file = null;
        $disk = 'public';
        $folder = 'uploads';
        $oldPath = null;

        if ($source instanceof Request) {
            $field = $arg2;
            $disk = $arg3 ?: 'public';
            $folder = $arg4 ?: 'uploads';
            $file = $source->file($field);
        } elseif ($source instanceof UploadedFile) {
            $file = $source;
            $disk = $arg2 ?: 'public';
            $folder = $arg3 ?: 'uploads';
            $oldPath = is_string($arg4) ? $arg4 : null;
        } else {
            return null;
        }

        if ($oldPath && is_string($oldPath) && str_starts_with($oldPath, $folder.'/')) {
            Storage::disk($disk)->delete($oldPath);
        }

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return $oldPath;
        }

        $safeMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $detected = $file->getMimeType();
        if (! isset($safeMimes[$detected])) {
            \Log::warning('Blocked file upload: '.$file->getClientOriginalName(), [
                'mime' => $detected,
            ]);

            return $oldPath;
        }

        $extension = $safeMimes[$detected];

        return $file->storeAs($folder, uniqid('up_', true).'.'.$extension, $disk);
    }
}
