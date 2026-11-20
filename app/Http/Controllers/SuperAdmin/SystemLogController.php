<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    private const LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $entries = $this->readEntries();

        $stats = [
            'total' => count($entries),
            'critical' => collect($entries)->whereIn('level', ['EMERGENCY', 'ALERT', 'CRITICAL'])->count(),
            'error' => collect($entries)->where('level', 'ERROR')->count(),
            'warning' => collect($entries)->where('level', 'WARNING')->count(),
        ];

        $filtered = collect($entries);

        if ($request->filled('level')) {
            $filtered = $filtered->where('level', strtoupper((string) $request->level));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->search));
            $filtered = $filtered->filter(fn ($entry) => str_contains(mb_strtolower($entry['message']), $search)
                || str_contains(mb_strtolower($entry['trace']), $search));
        }

        $entries = $filtered->take(300)->values();
        $levels = self::LEVELS;

        return view('super-admin.system-logs.index', compact('entries', 'stats', 'levels', 'userId'));
    }

    public function download()
    {
        $path = storage_path('logs/laravel.log');

        abort_unless(is_file($path), 404, 'File log belum tersedia.');

        return response()->download($path, 'laravel-'.now()->format('Y-m-d-His').'.log');
    }

    /**
     * @return array<int, array{time: string, level: string, message: string, trace: string}>
     */
    private function readEntries(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [];
        }

        $maxBytes = 3 * 1024 * 1024;
        $size = filesize($path) ?: 0;
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        if ($size > $maxBytes) {
            fseek($handle, -$maxBytes, SEEK_END);
            fgets($handle); // Buang baris pertama yang mungkin terpotong.
        }

        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        $entries = [];
        $current = null;

        foreach (preg_split('/\R/', $content) as $line) {
            if (preg_match('/^\[(?<time>[^\]]+)\]\s+\S+\.(?<level>EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s*(?<message>.*)$/', $line, $matches)) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $current = [
                    'time' => $matches['time'],
                    'level' => $matches['level'],
                    'message' => $matches['message'],
                    'trace' => '',
                ];

                continue;
            }

            if ($current !== null && trim($line) !== '') {
                $current['trace'] .= $line."\n";
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return array_reverse($entries);
    }
}
