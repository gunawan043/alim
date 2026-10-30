<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lapisan pertahanan tambahan untuk unggahan file: menolak ekstensi yang
 * berpotensi dieksekusi/dijalankan di server atau browser (PHP, CGI, shell,
 * HTML/JS, SVG, .htaccess). Validasi utama tetap ada di Form Request.
 */
class BlockDangerousUploads
{
    /**
     * Ekstensi yang selalu ditolak, terlepas dari MIME yang diklaim.
     */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
        'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'com', 'bat', 'cmd',
        'js', 'mjs', 'html', 'htm', 'xhtml', 'svg', 'htaccess', 'htpasswd',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->files->count() === 0) {
            return $next($request);
        }

        foreach ($this->flattenFiles($request->allFiles()) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                abort(422, 'Tipe file tidak diizinkan: .'.$extension);
            }
        }

        return $next($request);
    }

    /**
     * @param  array<int|string, mixed>  $files
     * @return array<int, mixed>
     */
    private function flattenFiles(array $files): array
    {
        $flat = [];

        array_walk_recursive($files, function ($value) use (&$flat) {
            $flat[] = $value;
        });

        return $flat;
    }
}
