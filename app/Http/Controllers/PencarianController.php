<?php

namespace App\Http\Controllers;

use App\Models\BankSoal;
use App\Models\PaketSoal;
use App\Models\PerangkatPembelajaran;
use App\Models\School;
use App\Models\Soal;
use App\Services\Ai\AiProviderService;
use App\Services\Ai\AiWebService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Pencarian terpadu: database internal, rekomendasi AI, dan internet.
 */
class PencarianController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly AiProviderService $ai,
        private readonly AiWebService $web,
    ) {}

    public function index(Request $request, string $userId)
    {
        $school = $this->school($request);
        $query = trim((string) $request->input('q', ''));
        $source = $request->input('source', 'db');
        $source = in_array($source, ['db', 'ai', 'internet'], true) ? $source : 'db';
        $type = $request->input('type', 'all');
        $type = in_array($type, ['all', 'soal', 'rpm', 'bank', 'paket'], true) ? $type : 'all';

        $soalResults = collect();
        $rpmResults = collect();
        $bankResults = collect();
        $paketResults = collect();
        $aiAnswer = null;
        $aiError = null;
        $webResults = [];
        $webError = null;

        if ($query !== '' && $source === 'db') {
            $bankIds = BankSoal::accessibleBy($userId, $school->id)->pluck('id');

            if (in_array($type, ['all', 'soal'], true)) {
                $soalResults = Soal::with(['bankSoal.subject:id,name', 'bankSoal.gradeLevel:id,name', 'creator:id,name'])
                    ->whereIn('bank_soal_id', $bankIds)
                    ->where(function ($q) use ($query) {
                        $q->where('pertanyaan', 'like', '%'.$query.'%')
                            ->orWhere('materi', 'like', '%'.$query.'%')
                            ->orWhere('tags', 'like', '%'.$query.'%');
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }

            if (in_array($type, ['all', 'rpm'], true)) {
                $rpmResults = PerangkatPembelajaran::with(['subject:id,name', 'gradeLevel:id,name', 'teacher:id,name'])
                    ->where('school_id', $school->id)
                    ->when(! $this->access->isKurikulumTeam($request->user()), function ($q) use ($request, $school) {
                        $viewable = $this->access->viewableSubjectIds($request->user(), $school->id);
                        $q->whereIn('subject_id', $viewable ?: ['-']);
                    })
                    ->where(function ($q) use ($query) {
                        $q->where('judul', 'like', '%'.$query.'%')
                            ->orWhere('catatan', 'like', '%'.$query.'%')
                            ->orWhere('desain', 'like', '%'.$query.'%');
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }

            if (in_array($type, ['all', 'bank'], true)) {
                $bankResults = BankSoal::accessibleBy($userId, $school->id)
                    ->with(['subject:id,name', 'gradeLevel:id,name', 'owner:id,name'])
                    ->where(function ($q) use ($query) {
                        $q->where('nama', 'like', '%'.$query.'%')
                            ->orWhere('deskripsi', 'like', '%'.$query.'%');
                    })
                    ->orderByDesc('updated_at')
                    ->limit(20)
                    ->get();
            }

            if (in_array($type, ['all', 'paket'], true)) {
                $paketResults = PaketSoal::with(['kisiKisi.subject:id,name', 'kisiKisi.gradeLevel:id,name'])
                    ->whereHas('kisiKisi', fn ($q) => $q->where('school_id', $school->id))
                    ->where(function ($q) use ($query) {
                        $q->where('judul', 'like', '%'.$query.'%')
                            ->orWhere('kode_paket', 'like', '%'.$query.'%');
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }
        }

        if ($query !== '' && $source === 'ai') {
            if (! $this->ai->isConfigured()) {
                $aiError = 'Provider AI belum dikonfigurasi. Super Admin dapat mengaktifkannya di Pengaturan AI.';
            } else {
                $result = $this->ai->chat([
                    ['role' => 'system', 'content' => 'Anda asisten pencarian kurikulum madrasah. Berikan rekomendasi ringkas dan praktis dalam bahasa Indonesia. Jangan mengarang data internal; bedakan rekomendasi ide dari data aplikasi.'],
                    ['role' => 'user', 'content' => "Sekolah: {$school->name}\nKata kunci: {$query}\n\nBerikan 3-5 rekomendasi: topik/materi, jenis soal atau RPM yang relevan, dan kata kunci pencarian lanjutan. Ringkas, gunakan poin."],
                ], ['max_tokens' => 600, 'temperature' => 0.3]);

                if ($result['ok']) {
                    $aiAnswer = trim((string) $result['content']);
                } else {
                    $aiError = $result['error'] ?: 'AI tidak dapat memberikan rekomendasi saat ini.';
                }
            }
        }

        if ($query !== '' && $source === 'internet') {
            $result = $this->web->search($query, 8);
            if (($result['ok'] ?? false) === true) {
                $webResults = $result['results'] ?? [];
            } else {
                $webError = $result['error'] ?? 'Pencarian internet tidak tersedia saat ini.';
            }
        }

        $total = $soalResults->count() + $rpmResults->count() + $bankResults->count() + $paketResults->count();

        return view('pencarian.index', compact(
            'userId', 'school', 'query', 'source', 'type',
            'soalResults', 'rpmResults', 'bankResults', 'paketResults', 'total',
            'aiAnswer', 'aiError', 'webResults', 'webError'
        ));
    }

    private function school(Request $request): School
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        abort_if(! $school, 422, 'Konteks satuan pendidikan tidak ditemukan.');

        return $school;
    }
}
