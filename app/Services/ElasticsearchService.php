<?php

namespace App\Services;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    protected $client;

    protected $indexPrefix;

    protected $maxResultWindow = 10000;

    public function __construct()
    {
        $hosts = config('scout.elastic.hosts', ['localhost:9200']);
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries(config('scout.elastic.retries', 2))
            ->build();
        $this->indexPrefix = config('app.env').'_';
    }

    /**
     * Search recruitment profiles with advanced filters
     */
    public function searchRecruitmentProfiles($query, $filters = [], $page = 1, $perPage = 15)
    {
        $index = $this->indexPrefix.'recruitment_profiles';

        // Cek apakah index ada
        if (! $this->indexExists($index)) {
            $this->createRecruitmentIndex();
        }

        $must = [];
        $filter = [];

        // Full text search dengan boosting
        if ($query) {
            $must[] = [
                'bool' => [
                    'should' => [
                        // Exact match on important fields (boost tertinggi)
                        [
                            'match_phrase' => [
                                'nik' => [
                                    'query' => $query,
                                    'boost' => 5,
                                ],
                            ],
                        ],
                        [
                            'match_phrase' => [
                                'user.name' => [
                                    'query' => $query,
                                    'boost' => 4,
                                ],
                            ],
                        ],
                        [
                            'match_phrase' => [
                                'user.email' => [
                                    'query' => $query,
                                    'boost' => 3,
                                ],
                            ],
                        ],

                        // Fuzzy search for flexibility
                        [
                            'match' => [
                                'user.name' => [
                                    'query' => $query,
                                    'fuzziness' => 'AUTO',
                                    'boost' => 2,
                                ],
                            ],
                        ],

                        // Nested fields
                        [
                            'nested' => [
                                'path' => 'educations',
                                'query' => [
                                    'multi_match' => [
                                        'query' => $query,
                                        'fields' => [
                                            'educations.nama_sekolah^3',
                                            'educations.jurusan^2',
                                            'educations.fakultas',
                                        ],
                                        'fuzziness' => 'AUTO',
                                    ],
                                ],
                                'boost' => 3,
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'work_experiences',
                                'query' => [
                                    'multi_match' => [
                                        'query' => $query,
                                        'fields' => [
                                            'work_experiences.nama_perusahaan^3',
                                            'work_experiences.posisi_terakhir^4',
                                            'work_experiences.jobdesc',
                                        ],
                                        'fuzziness' => 'AUTO',
                                    ],
                                ],
                                'boost' => 4,
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'skills',
                                'query' => [
                                    'match' => [
                                        'skills.nama_skill' => [
                                            'query' => $query,
                                            'boost' => 3,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'trainings',
                                'query' => [
                                    'match' => [
                                        'trainings.nama_pelatihan' => [
                                            'query' => $query,
                                            'boost' => 2,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'documents',
                                'query' => [
                                    'match' => [
                                        'documents.ringkasan_profesional' => [
                                            'query' => $query,
                                            'boost' => 2,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        // FILTERS

        // Filter by education level
        if (! empty($filters['jenjang_pendidikan'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'educations',
                    'query' => [
                        'terms' => [
                            'educations.jenjang' => (array) $filters['jenjang_pendidikan'],
                        ],
                    ],
                ],
            ];
        }

        // Filter by graduation year range
        if (! empty($filters['tahun_lulus_min']) || ! empty($filters['tahun_lulus_max'])) {
            $range = [];
            if (! empty($filters['tahun_lulus_min'])) {
                $range['gte'] = $filters['tahun_lulus_min'];
            }
            if (! empty($filters['tahun_lulus_max'])) {
                $range['lte'] = $filters['tahun_lulus_max'];
            }

            $filter[] = [
                'nested' => [
                    'path' => 'educations',
                    'query' => [
                        'range' => [
                            'educations.tahun_lulus' => $range,
                        ],
                    ],
                ],
            ];
        }

        // Filter by IPK
        if (! empty($filters['ipk_min'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'educations',
                    'query' => [
                        'range' => [
                            'educations.ipk' => ['gte' => (float) $filters['ipk_min']],
                        ],
                    ],
                ],
            ];
        }

        // Filter by work experience
        if (! empty($filters['pengalaman_min'])) {
            $filter[] = [
                'range' => [
                    'total_pengalaman_tahun' => ['gte' => (float) $filters['pengalaman_min']],
                ],
            ];
        }

        // Filter by skills (must have ALL specified skills)
        if (! empty($filters['skills'])) {
            $skills = (array) $filters['skills'];
            foreach ($skills as $skill) {
                $filter[] = [
                    'nested' => [
                        'path' => 'skills',
                        'query' => [
                            'term' => [
                                'skills.nama_skill.keyword' => $skill,
                            ],
                        ],
                    ],
                ];
            }
        }

        // Filter by any skill (OR condition)
        if (! empty($filters['any_skill'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'skills',
                    'query' => [
                        'terms' => [
                            'skills.nama_skill.keyword' => (array) $filters['any_skill'],
                        ],
                    ],
                ],
            ];
        }

        // Filter by age range
        if (! empty($filters['umur_min']) || ! empty($filters['umur_max'])) {
            $range = [];
            if (! empty($filters['umur_min'])) {
                $range['gte'] = (int) $filters['umur_min'];
            }
            if (! empty($filters['umur_max'])) {
                $range['lte'] = (int) $filters['umur_max'];
            }
            $filter[] = ['range' => ['umur' => $range]];
        }

        // Filter by gender
        if (! empty($filters['jenis_kelamin'])) {
            $filter[] = [
                'terms' => [
                    'jenis_kelamin' => (array) $filters['jenis_kelamin'],
                ],
            ];
        }

        // Filter by marital status
        if (! empty($filters['status_perkawinan'])) {
            $filter[] = [
                'terms' => [
                    'status_perkawinan' => (array) $filters['status_perkawinan'],
                ],
            ];
        }

        // Filter by religion
        if (! empty($filters['agama'])) {
            $filter[] = [
                'terms' => [
                    'agama' => (array) $filters['agama'],
                ],
            ];
        }

        // Filter by location
        if (! empty($filters['provinsi'])) {
            $filter[] = [
                'term' => ['provinsi.keyword' => $filters['provinsi']],
            ];
        }

        if (! empty($filters['kota'])) {
            $filter[] = [
                'term' => ['kota_kabupaten.keyword' => $filters['kota']],
            ];
        }

        // Filter by application status for specific job
        if (! empty($filters['job_id']) && ! empty($filters['application_status'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'applications',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['applications.recruitment_job_id' => $filters['job_id']]],
                                ['terms' => ['applications.status' => (array) $filters['application_status']]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Filter by has applied to job
        if (! empty($filters['has_applied_to_job'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'applications',
                    'query' => [
                        'term' => ['applications.recruitment_job_id' => $filters['has_applied_to_job']],
                    ],
                ],
            ];
        }

        // Filter by submitted date range
        if (! empty($filters['submitted_from'])) {
            $filter[] = [
                'range' => [
                    'submitted_at' => ['gte' => $filters['submitted_from']],
                ],
            ];
        }

        if (! empty($filters['submitted_to'])) {
            $filter[] = [
                'range' => [
                    'submitted_at' => ['lte' => $filters['submitted_to']],
                ],
            ];
        }

        // Build the query
        $params = [
            'index' => $index,
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => $must ?: ['match_all' => new \stdClass],
                        'filter' => $filter,
                    ],
                ],
                'aggs' => $this->getRecruitmentAggregations(),
                'sort' => $this->buildRecruitmentSort($filters['sort'] ?? null),
                'highlight' => [
                    'fields' => [
                        'user.name' => ['number_of_fragments' => 0],
                        'educations.nama_sekolah' => ['number_of_fragments' => 2],
                        'work_experiences.nama_perusahaan' => ['number_of_fragments' => 2],
                        'work_experiences.posisi_terakhir' => ['number_of_fragments' => 2],
                        'skills.nama_skill' => ['number_of_fragments' => 0],
                    ],
                ],
            ],
        ];

        try {
            $results = $this->client->search($params);

            return $this->formatRecruitmentResults($results, $page, $perPage);
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed: '.$e->getMessage(), [
                'query' => $query,
                'filters' => $filters,
            ]);

            // Fallback ke database
            return $this->fallbackRecruitmentSearch($query, $filters, $page, $perPage);
        }
    }

    /**
     * Search jobs with filters
     */
    public function searchJobs($query, $filters = [], $page = 1, $perPage = 15)
    {
        $index = $this->indexPrefix.'recruitment_jobs';

        if (! $this->indexExists($index)) {
            $this->createJobsIndex();
        }

        $must = [];
        $filter = [];

        if ($query) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'kode_lowongan^5',
                        'judul^4',
                        'posisi^4',
                        'deskripsi_pekerjaan^2',
                        'persyaratan_umum',
                        'persyaratan_khusus',
                    ],
                    'fuzziness' => 'AUTO',
                    'operator' => 'and',
                ],
            ];
        }

        // Filter by job type
        if (! empty($filters['jenis_pegawai'])) {
            $filter[] = [
                'terms' => [
                    'jenis_pegawai' => (array) $filters['jenis_pegawai'],
                ],
            ];
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $filter[] = [
                'terms' => [
                    'status' => (array) $filters['status'],
                ],
            ];
        }

        // Filter by work unit
        if (! empty($filters['work_unit_id'])) {
            $filter[] = [
                'term' => ['work_unit_id_uuid' => $filters['work_unit_id']],
            ];
        }

        // Filter by date range
        if (! empty($filters['tanggal_mulai_from'])) {
            $filter[] = [
                'range' => [
                    'tanggal_mulai' => ['gte' => $filters['tanggal_mulai_from']],
                ],
            ];
        }

        if (! empty($filters['tanggal_selesai_to'])) {
            $filter[] = [
                'range' => [
                    'tanggal_selesai' => ['lte' => $filters['tanggal_selesai_to']],
                ],
            ];
        }

        // Filter by quota
        if (! empty($filters['kuota_tersedia'])) {
            $filter[] = [
                'script' => [
                    'script' => [
                        'source' => 'doc[\'kuota\'].value - doc[\'kuota_terisi\'].value > 0',
                    ],
                ],
            ];
        }

        $params = [
            'index' => $index,
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => $must ?: ['match_all' => new \stdClass],
                        'filter' => $filter,
                    ],
                ],
                'aggs' => [
                    'jenis_pegawai' => [
                        'terms' => ['field' => 'jenis_pegawai.keyword', 'size' => 10],
                    ],
                    'status' => [
                        'terms' => ['field' => 'status.keyword', 'size' => 10],
                    ],
                    'work_units' => [
                        'terms' => ['field' => 'work_unit_name.keyword', 'size' => 20],
                    ],
                ],
                'sort' => $this->buildJobSort($filters['sort'] ?? null),
            ],
        ];

        try {
            $results = $this->client->search($params);

            return $this->formatJobResults($results, $page, $perPage);
        } catch (\Exception $e) {
            Log::error('Elasticsearch jobs search failed: '.$e->getMessage());

            return $this->fallbackJobSearch($query, $filters, $page, $perPage);
        }
    }

    /**
     * Get aggregations for filters
     */
    protected function getRecruitmentAggregations()
    {
        return [
            'jenjang_pendidikan' => [
                'nested' => ['path' => 'educations'],
                'aggs' => [
                    'jenjang' => [
                        'terms' => ['field' => 'educations.jenjang.keyword', 'size' => 20],
                    ],
                ],
            ],
            'tahun_lulus' => [
                'nested' => ['path' => 'educations'],
                'aggs' => [
                    'tahun' => [
                        'terms' => ['field' => 'educations.tahun_lulus', 'size' => 50],
                    ],
                ],
            ],
            'ipk_range' => [
                'nested' => ['path' => 'educations'],
                'aggs' => [
                    'ipk' => [
                        'range' => [
                            'field' => 'educations.ipk',
                            'ranges' => [
                                ['to' => 2.5],
                                ['from' => 2.5, 'to' => 3.0],
                                ['from' => 3.0, 'to' => 3.5],
                                ['from' => 3.5],
                            ],
                        ],
                    ],
                ],
            ],
            'skill_populer' => [
                'nested' => ['path' => 'skills'],
                'aggs' => [
                    'skills' => [
                        'terms' => ['field' => 'skills.nama_skill.keyword', 'size' => 30],
                    ],
                ],
            ],
            'rentang_umur' => [
                'range' => [
                    'field' => 'umur',
                    'ranges' => [
                        ['to' => 25],
                        ['from' => 25, 'to' => 30],
                        ['from' => 30, 'to' => 35],
                        ['from' => 35, 'to' => 40],
                        ['from' => 40],
                    ],
                ],
            ],
            'jenis_kelamin' => [
                'terms' => ['field' => 'jenis_kelamin.keyword', 'size' => 5],
            ],
            'status_perkawinan' => [
                'terms' => ['field' => 'status_perkawinan.keyword', 'size' => 5],
            ],
            'agama' => [
                'terms' => ['field' => 'agama.keyword', 'size' => 10],
            ],
            'lokasi' => [
                'terms' => ['field' => 'provinsi.keyword', 'size' => 20],
            ],
            'pengalaman_kerja' => [
                'range' => [
                    'field' => 'total_pengalaman_tahun',
                    'ranges' => [
                        ['to' => 1],
                        ['from' => 1, 'to' => 3],
                        ['from' => 3, 'to' => 5],
                        ['from' => 5, 'to' => 10],
                        ['from' => 10],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build sort criteria
     */
    protected function buildRecruitmentSort($sort)
    {
        switch ($sort) {
            case 'terbaru':
                return [['submitted_at' => ['order' => 'desc']]];
            case 'terlama':
                return [['submitted_at' => ['order' => 'asc']]];
            case 'nama_asc':
                return [['user.name.keyword' => ['order' => 'asc']]];
            case 'nama_desc':
                return [['user.name.keyword' => ['order' => 'desc']]];
            case 'umur_termuda':
                return [['umur' => ['order' => 'asc']]];
            case 'umur_tertua':
                return [['umur' => ['order' => 'desc']]];
            case 'pengalaman_terbanyak':
                return [['total_pengalaman_tahun' => ['order' => 'desc']]];
            case 'pengalaman_tersedikit':
                return [['total_pengalaman_tahun' => ['order' => 'asc']]];
            default:
                return ['_score' => ['order' => 'desc']];
        }
    }

    /**
     * Build job sort
     */
    protected function buildJobSort($sort)
    {
        switch ($sort) {
            case 'terbaru':
                return [['created_at' => ['order' => 'desc']]];
            case 'terlama':
                return [['created_at' => ['order' => 'asc']]];
            case 'akan_tutup':
                return [['tanggal_selesai' => ['order' => 'asc']]];
            case 'judul_asc':
                return [['judul.keyword' => ['order' => 'asc']]];
            case 'judul_desc':
                return [['judul.keyword' => ['order' => 'desc']]];
            case 'kuota_tersisa':
                return [
                    '_script' => [
                        'type' => 'number',
                        'script' => [
                            'source' => 'doc[\'kuota\'].value - doc[\'kuota_terisi\'].value',
                        ],
                        'order' => 'desc',
                    ],
                ];
            default:
                return ['_score' => ['order' => 'desc']];
        }
    }

    /**
     * Format recruitment results
     */
    protected function formatRecruitmentResults($results, $page, $perPage)
    {
        $total = $results['hits']['total']['value'] ?? 0;
        $hits = $results['hits']['hits'] ?? [];

        $data = collect($hits)->map(function ($hit) {
            return [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'highlights' => $hit['highlight'] ?? [],
                ...$hit['_source'],
            ];
        });

        // Get aggregations
        $aggregations = [];
        if (isset($results['aggregations'])) {
            foreach ($results['aggregations'] as $key => $agg) {
                if (isset($agg['jenjang']['buckets'])) {
                    $aggregations[$key] = $agg['jenjang']['buckets'];
                } elseif (isset($agg['skills']['buckets'])) {
                    $aggregations[$key] = $agg['skills']['buckets'];
                } elseif (isset($agg['buckets'])) {
                    $aggregations[$key] = $agg['buckets'];
                }
            }
        }

        return [
            'data' => $data,
            'aggregations' => $aggregations,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
            'took_ms' => $results['took'] ?? 0,
            'max_score' => $results['hits']['max_score'] ?? 0,
        ];
    }

    /**
     * Format job results
     */
    protected function formatJobResults($results, $page, $perPage)
    {
        $total = $results['hits']['total']['value'] ?? 0;
        $hits = $results['hits']['hits'] ?? [];

        $data = collect($hits)->map(function ($hit) {
            $source = $hit['_source'];
            $source['kuota_tersisa'] = $source['kuota'] - $source['kuota_terisi'];
            $source['id'] = $hit['_id'];

            return $source;
        });

        // Get aggregations
        $aggregations = [];
        if (isset($results['aggregations'])) {
            foreach ($results['aggregations'] as $key => $agg) {
                $aggregations[$key] = $agg['buckets'] ?? [];
            }
        }

        return [
            'data' => $data,
            'aggregations' => $aggregations,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Check if index exists
     */
    protected function indexExists($index)
    {
        try {
            return $this->client->indices()->exists(['index' => $index]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create recruitment index
     */
    protected function createRecruitmentIndex()
    {
        $params = [
            'index' => $this->indexPrefix.'recruitment_profiles',
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 1,
                    'analysis' => [
                        'analyzer' => [
                            'indonesian_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding', 'stop', 'snowball'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'user' => [
                            'properties' => [
                                'name' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'email' => ['type' => 'keyword'],
                            ],
                        ],
                        'nik' => ['type' => 'keyword'],
                        'umur' => ['type' => 'integer'],
                        'jenis_kelamin' => ['type' => 'keyword'],
                        'agama' => ['type' => 'keyword'],
                        'status_perkawinan' => ['type' => 'keyword'],
                        'provinsi' => ['type' => 'keyword'],
                        'kota_kabupaten' => ['type' => 'keyword'],
                        'total_pengalaman_tahun' => ['type' => 'float'],
                        'submitted_at' => ['type' => 'date'],

                        'educations' => [
                            'type' => 'nested',
                            'properties' => [
                                'jenjang' => ['type' => 'keyword'],
                                'nama_sekolah' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'jurusan' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'tahun_lulus' => ['type' => 'integer'],
                                'ipk' => ['type' => 'float'],
                            ],
                        ],

                        'work_experiences' => [
                            'type' => 'nested',
                            'properties' => [
                                'nama_perusahaan' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'posisi_terakhir' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'jobdesc' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'lama_bekerja_bulan' => ['type' => 'integer'],
                            ],
                        ],

                        'skills' => [
                            'type' => 'nested',
                            'properties' => [
                                'nama_skill' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                                'level' => ['type' => 'keyword'],
                                'tahun_pengalaman' => ['type' => 'integer'],
                            ],
                        ],

                        'applications' => [
                            'type' => 'nested',
                            'properties' => [
                                'recruitment_job_id' => ['type' => 'keyword'],
                                'status' => ['type' => 'keyword'],
                                'nilai_akhir' => ['type' => 'float'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->client->indices()->create($params);
        } catch (\Exception $e) {
            Log::error('Failed to create recruitment index: '.$e->getMessage());
        }
    }

    /**
     * Create jobs index
     */
    protected function createJobsIndex()
    {
        $params = [
            'index' => $this->indexPrefix.'recruitment_jobs',
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 1,
                ],
                'mappings' => [
                    'properties' => [
                        'kode_lowongan' => ['type' => 'keyword'],
                        'judul' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                        'posisi' => ['type' => 'text', 'analyzer' => 'indonesian_analyzer'],
                        'jenis_pegawai' => ['type' => 'keyword'],
                        'status' => ['type' => 'keyword'],
                        'kuota' => ['type' => 'integer'],
                        'kuota_terisi' => ['type' => 'integer'],
                        'tanggal_mulai' => ['type' => 'date'],
                        'tanggal_selesai' => ['type' => 'date'],
                        'created_at' => ['type' => 'date'],
                        'work_unit_name' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ];

        try {
            $this->client->indices()->create($params);
        } catch (\Exception $e) {
            Log::error('Failed to create jobs index: '.$e->getMessage());
        }
    }

    /**
     * Fallback ke database
     */
    protected function fallbackRecruitmentSearch($query, $filters, $page, $perPage)
    {
        $dbQuery = \App\Models\RecruitmentProfile::query()
            ->with(['user', 'educations', 'workExperiences', 'skills']);

        if ($query) {
            $dbQuery->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
                ->orWhere('nik', 'LIKE', "%{$query}%")
                ->orWhereHas('educations', function ($q) use ($query) {
                    $q->where('nama_sekolah', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('workExperiences', function ($q) use ($query) {
                    $q->where('nama_perusahaan', 'LIKE', "%{$query}%")
                        ->orWhere('posisi_terakhir', 'LIKE', "%{$query}%");
                });
        }

        // Apply filters
        if (! empty($filters['jenis_kelamin'])) {
            $dbQuery->where('jenis_kelamin', $filters['jenis_kelamin']);
        }

        if (! empty($filters['provinsi'])) {
            $dbQuery->where('provinsi', $filters['provinsi']);
        }

        $results = $dbQuery->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'aggregations' => [],
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ];
    }

    /**
     * Fallback job search
     */
    protected function fallbackJobSearch($query, $filters, $page, $perPage)
    {
        $dbQuery = \App\Models\RecruitmentJob::query();

        if ($query) {
            $dbQuery->where('judul', 'LIKE', "%{$query}%")
                ->orWhere('kode_lowongan', 'LIKE', "%{$query}%")
                ->orWhere('posisi', 'LIKE', "%{$query}%");
        }

        if (! empty($filters['jenis_pegawai'])) {
            $dbQuery->where('jenis_pegawai', $filters['jenis_pegawai']);
        }

        if (! empty($filters['status'])) {
            $dbQuery->where('status', $filters['status']);
        }

        $results = $dbQuery->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'aggregations' => [],
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ];
    }
}
