<?php

namespace App\Http\Controllers;

use App\Services\ElasticsearchService;
use Illuminate\Http\Request;

class RecruitmentSearchController extends Controller
{
    protected $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Search candidates/profiles
     */
    public function searchCandidates(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:terbaru,terlama,nama_asc,nama_desc,umur_termuda,umur_tertua,pengalaman_terbanyak,pengalaman_tersedikit'
        ]);

        $results = $this->elasticsearch->searchRecruitmentProfiles(
            $request->q,
            $request->except(['q', 'page', 'per_page', 'sort']),
            $request->page ?? 1,
            $request->per_page ?? 15
        );

        return response()->json([
            'success' => true,
            'data' => $results['data'],
            'aggregations' => $results['aggregations'],
            'pagination' => $results['pagination']
        ]);
    }

    /**
     * Search jobs
     */
    public function searchJobs(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $results = $this->elasticsearch->searchJobs(
            $request->q,
            $request->except(['q', 'page', 'per_page']),
            $request->page ?? 1,
            $request->per_page ?? 15
        );

        return response()->json([
            'success' => true,
            'data' => $results['data'],
            'aggregations' => $results['aggregations'],
            'pagination' => $results['pagination']
        ]);
    }

    /**
     * Advanced search form
     */
    public function advancedSearch(Request $request)
    {
        // Get filter options from database
        $options = [
            'jenjang_pendidikan' => \App\Models\RecruitmentEducation::distinct('jenjang')->pluck('jenjang'),
            'jenis_kelamin' => ['L', 'P'],
            'agama' => ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'],
            'status_perkawinan' => ['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'],
            'provinsi' => \App\Models\RecruitmentProfile::distinct('provinsi')->whereNotNull('provinsi')->pluck('provinsi'),
            'skills' => \App\Models\RecruitmentSkill::distinct('nama_skill')->limit(100)->pluck('nama_skill')
        ];

        return response()->json([
            'success' => true,
            'filters' => $options
        ]);
    }

    /**
     * Search suggestions (auto-complete)
     */
    public function suggestions(Request $request)
    {
        $query = $request->q;
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // Get suggestions from multiple sources
        $suggestions = [
            'skills' => \App\Models\RecruitmentSkill::where('nama_skill', 'LIKE', "%{$query}%")
                ->limit(5)
                ->pluck('nama_skill')
                ->map(fn($s) => ['type' => 'skill', 'text' => $s]),
            
            'positions' => \App\Models\RecruitmentWorkExperience::where('posisi_terakhir', 'LIKE', "%{$query}%")
                ->limit(5)
                ->pluck('posisi_terakhir')
                ->map(fn($s) => ['type' => 'position', 'text' => $s]),
            
            'companies' => \App\Models\RecruitmentWorkExperience::where('nama_perusahaan', 'LIKE', "%{$query}%")
                ->limit(5)
                ->pluck('nama_perusahaan')
                ->map(fn($s) => ['type' => 'company', 'text' => $s]),
            
            'schools' => \App\Models\RecruitmentEducation::where('nama_sekolah', 'LIKE', "%{$query}%")
                ->limit(5)
                ->pluck('nama_sekolah')
                ->map(fn($s) => ['type' => 'school', 'text' => $s])
        ];

        $results = collect($suggestions)->flatten(1)->values();

        return response()->json([
            'success' => true,
            'suggestions' => $results
        ]);
    }
}