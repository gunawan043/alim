<?php

namespace Tests\Feature;

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\SuperAdmin\AiChatMonitorController;
use App\Models\AcademicYear;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiProviderSetting;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\JadwalKbm;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Ai\AiChatService;
use App\Services\Ai\AiContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private GradeLevel $grade;

    private StudyGroup $group;

    private User $admin;

    private AiProviderSetting $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Chat', 'code' => 'UCH',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '20202020',
            'name' => 'Sekolah Uji Chat',
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin Chat', 'email' => 'sa.chat@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->provider = AiProviderSetting::create([
            'name' => 'Provider Chat',
            'provider' => 'openai',
            'endpoint' => 'https://ai.chat.test/v1/chat/completions',
            'api_key' => 'sk-test',
            'model' => 'chat-test',
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach (['L', 'P'] as $index => $gender) {
            $student = Student::create([
                'school_id' => $this->school->id,
                'name' => 'Santri '.$index,
                'gender' => $gender,
                'status' => 'active',
                'nisn' => '100'.$index,
                'nis' => '00'.$index,
            ]);

            StudentClassHistory::create([
                'student_id' => $student->id,
                'study_group_id' => $this->group->id,
                'academic_year_id' => $this->year->id,
                'is_active' => true,
            ]);
        }

        Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Nonaktif',
            'gender' => 'L',
            'status' => 'inactive',
            'nisn' => '199',
        ]);
    }

    public function test_halaman_ai_workspace_dapat_dibuka_pegawai(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/ai");

        $response->assertOk();
        $response->assertSee('ALIM AI', false);
        $response->assertSee('Provider Chat', false);
    }

    public function test_mode_bantuan_menyimpan_pesan_tanpa_memanggil_ai(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", [
                'message' => 'Mohon bantuan untuk kendala rapor.',
                'mode' => 'support',
            ])
            ->assertOk()
            ->assertJsonPath('support', true);

        Http::assertNothingSent();

        $conversation = AiChatConversation::where('mode', 'support')->firstOrFail();
        $this->assertSame('open', $conversation->status);
        $this->assertSame(1, $conversation->messages()->count());

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/ai-chat/conversations")
            ->assertOk()
            ->assertJsonPath('items.0.mode', 'support');

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/ai-chat/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('conversation.mode', 'support');
    }

    public function test_super_admin_membalas_bantuan_dan_user_melihat_balasan(): void
    {
        $user = User::create([
            'name' => 'Guru Bantuan', 'email' => 'guru.bantuan@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'title' => 'Bantuan: kendala rapor',
            'mode' => 'support',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Rapor saya tidak muncul.',
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-chat/{$conversation->id}/reply", [
                'message' => 'Silakan cek menu rapor dan pilih semester yang benar.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Silakan cek menu rapor dan pilih semester yang benar.',
        ]);
        $this->assertSame('replied', $conversation->fresh()->status);

        $request = Request::create('/ai-chat/'.$conversation->id, 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $user);

        $response = app(AiChatController::class)->show($request, $user->id, $conversation->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Silakan cek menu rapor dan pilih semester yang benar.', (string) $response->getContent());

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/sa/ai-chat?conversation={$conversation->id}")
            ->assertOk()
            ->assertSee('Silakan cek menu rapor dan pilih semester yang benar.');
    }

    public function test_percakapan_baru_tidak_membawa_konteks_percakapan_lama(): void
    {
        $old = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan Lama',
            'mode' => 'ai',
            'status' => 'open',
        ]);
        AiChatMessage::create([
            'conversation_id' => $old->id,
            'role' => 'user',
            'content' => 'TOPIK_LAMA_JANGAN_DIBawa',
        ]);

        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Jawaban baru.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'Pertanyaan baru.'])
            ->assertOk()
            ->assertJsonPath('reply', 'Jawaban baru.');

        Http::assertSent(function ($request) {
            return ! collect($request['messages'] ?? [])->contains(
                fn ($message) => str_contains((string) ($message['content'] ?? ''), 'TOPIK_LAMA_JANGAN_DIBawa')
            );
        });
    }

    public function test_topik_baru_dalam_percakapan_yang_sama_tidak_membawa_riwayat_lama(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan Campuran',
            'mode' => 'ai',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'TOPIK_LAMA_JANGAN_DIULANG']);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => 'JAWABAN_LAMA_JANGAN_DIULANG']);

        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Jadwal baru.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", [
                'conversation_id' => $conversation->id,
                'message' => 'Apa jadwal mengajar saya hari ini?',
            ])
            ->assertOk()
            ->assertJsonPath('reply', 'Jadwal baru.');

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return ! str_contains($payload, 'TOPIK_LAMA_JANGAN_DIULANG')
                && ! str_contains($payload, 'JAWABAN_LAMA_JANGAN_DIULANG')
                && str_contains($payload, 'Apa jadwal mengajar saya hari ini?');
        });
    }

    public function test_konteks_kelas_yang_diampu_dikirim_ke_ai(): void
    {
        $teacher = User::create([
            'name' => 'Guru Kelas Chat', 'email' => 'guru.kelas.chat@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'FIQ', 'name' => 'Fiqih',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $decree = InstitutionDecree::create([
            'decree_number' => 'SK-CHAT-001',
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Uji Chat',
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'issued_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        TeachingAssignment::create([
            'decree_id' => $decree->id,
            'teacher_id' => $teacher->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ]);

        JadwalKbm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => now()->dayOfWeekIso,
            'slot_index' => 1,
            'start_time' => '07:00:00',
            'end_time' => '07:40:00',
            'room' => 'R-1',
            'is_active' => true,
        ]);

        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Anda mengajar 7A.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'Berapa jumlah santri di kelas yang aku ajar?',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $teacher);

        app(AiChatController::class)->send($request, $teacher->id, app(AiChatService::class), app(AiContextService::class));

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return str_contains($payload, 'Kelas yang diampu')
                && str_contains($payload, '7A')
                && str_contains($payload, 'Santri aktif di kelas yang diampu')
                && str_contains($payload, 'Jadwal mengajar hari ini')
                && str_contains($payload, 'Jadwal sekolah hari ini')
                && str_contains($payload, 'R-1')
                && str_contains($payload, 'count_my_students');
        });
    }

    public function test_jadwal_sekolah_tersedia_untuk_pengguna_tanpa_jadwal_pribadi(): void
    {
        $teacher = User::create([
            'name' => 'Guru Jadwal', 'email' => 'guru.jadwal@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        JadwalKbm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => now()->dayOfWeekIso,
            'slot_index' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:40:00',
            'room' => 'R-2',
            'is_active' => true,
        ]);

        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Jadwal sekolah tersedia.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'Apa jadwal sekolah hari ini?',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiChatController::class)->send(
            $request,
            $this->admin->id,
            app(AiChatService::class),
            app(AiContextService::class),
        );

        $this->assertTrue($response->getData(true)['ok'] ?? false);

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return str_contains($payload, 'Jadwal sekolah hari ini')
                && str_contains($payload, 'Matematika')
                && str_contains($payload, '7A')
                && ! str_contains($payload, 'Jadwal mengajar hari ini: 08:00');
        });
    }

    public function test_permintaan_rpm_tidak_memakai_tool_nilai(): void
    {
        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'RPM Bahasa Inggris Kelas 7: identitas, tujuan, langkah pembelajaran, asesmen.'],
                    'finish_reason' => 'stop',
                ]],
            ], 200),
        ]);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'bantu aku rancang rpm untuk mapel bahasa inggris kelas 7',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiChatController::class)->send(
            $request,
            $this->admin->id,
            app(AiChatService::class),
            app(AiContextService::class),
        );

        $payload = $response->getData(true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('text', $payload['blocks'][0]['type'] ?? null);

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return str_contains($payload, 'MODE KONTEN')
                && (int) ($request['max_tokens'] ?? 0) >= 4096
                && ! str_contains($payload, 'grades_summary')
                && ! str_contains($payload, 'admin_nilai_sumatif')
                && ! str_contains($payload, 'teacher_admin_books');
        });
    }

    public function test_rating_jawaban_ai_disimpan(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan Rating',
            'mode' => 'ai',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $message = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Jawaban untuk dinilai.',
        ]);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/{$conversation->id}/feedback", [
                'message_id' => $message->id,
                'rating' => 'up',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rating', 'up');

        $message->refresh();
        $this->assertSame('up', $message->metadata['rating'] ?? null);
        $this->assertNotEmpty($message->metadata['rated_at'] ?? null);
    }

    public function test_pertanyaan_umum_mapel_tidak_memaksa_tabel_nilai(): void
    {
        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Mata pelajaran adalah unit pembelajaran di sekolah.'],
                    'finish_reason' => 'stop',
                ]],
            ], 200),
        ]);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'apa itu mapel?',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiChatController::class)->send(
            $request,
            $this->admin->id,
            app(AiChatService::class),
            app(AiContextService::class),
        );

        $payload = $response->getData(true);
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertFalse(collect($payload['blocks'] ?? [])->contains(
            fn ($block) => ($block['type'] ?? null) === 'table'
        ));
    }

    public function test_jawaban_terpotong_diulang_dengan_batas_token_lebih_besar(): void
    {
        Http::fakeSequence()
            ->push([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Awal jawaban yang terpotong'],
                    'finish_reason' => 'length',
                ]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 8192, 'total_tokens' => 8292],
            ], 200)
            ->push([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Jawaban lengkap setelah retry.'],
                    'finish_reason' => 'stop',
                ]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
            ], 200);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'Jelaskan konsep penilaian autentik secara lengkap.',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiChatController::class)->send(
            $request,
            $this->admin->id,
            app(AiChatService::class),
            app(AiContextService::class),
        );

        $payload = $response->getData(true);
        $this->assertSame('Jawaban lengkap setelah retry.', $payload['reply'] ?? null);
        Http::assertSentCount(2);
    }

    public function test_web_search_menghasilkan_sumber_url(): void
    {
        config([
            'ai.web_search.provider' => 'duckduckgo',
            'ai.web_search.cache_minutes' => 0,
        ]);

        Http::fake([
            'html.duckduckgo.com/*' => Http::response(
                '<html><body><div class="result"><a class="result__a" href="//duckduckgo.com/l/?uddg=https%3A%2F%2Fexample.com%2Fberita">Contoh Berita Pendidikan</a><a class="result__snippet">Ringkasan berita pendidikan terbaru.</a></div></body></html>',
                200,
            ),
            'ai.chat.test/*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_web_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'web_search',
                                    'arguments' => '{"query":"berita pendidikan terbaru"}',
                                ],
                            ]],
                        ],
                        'finish_reason' => 'tool_calls',
                    ]],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => ['role' => 'assistant', 'content' => 'Berikut berita pendidikan terbaru.'],
                        'finish_reason' => 'stop',
                    ]],
                ], 200),
        ]);

        $request = Request::create('/ai-chat/send', 'POST', [
            'message' => 'Cari berita pendidikan terbaru dari internet.',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiChatController::class)->send(
            $request,
            $this->admin->id,
            app(AiChatService::class),
            app(AiContextService::class),
        );

        $payload = $response->getData(true);
        $this->assertTrue($payload['ok'] ?? false);

        $webSource = collect($payload['blocks'] ?? [])
            ->first(fn ($block) => ($block['type'] ?? null) === 'source' && ($block['url'] ?? null) === 'https://example.com/berita');

        $this->assertNotNull($webSource);
        $this->assertSame('Contoh Berita Pendidikan', $webSource['label']);
        $this->assertSame('Pencarian web', $webSource['tool']);
    }

    public function test_client_message_id_yang_sama_tidak_mengirim_dua_kali(): void
    {
        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Jawaban sekali.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $clientMessageId = (string) Str::uuid();
        $payload = [
            'message' => 'Berapa santri aktif?',
            'client_message_id' => $clientMessageId,
        ];

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", $payload)
            ->assertOk()
            ->assertJsonPath('reply', 'Jawaban sekali.');

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", $payload)
            ->assertOk()
            ->assertJsonPath('reply', 'Jawaban sekali.')
            ->assertJsonPath('cached', true);

        $this->assertSame(2, AiChatMessage::count());
        Http::assertSentCount(1);
    }

    public function test_handoff_ke_super_admin_mempertahankan_percakapan_yang_sama(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan AI',
            'mode' => 'ai',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Pertanyaan AI.']);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => 'Jawaban AI.']);

        Http::fake();

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", [
                'conversation_id' => $conversation->id,
                'message' => 'Saya ingin dibantu Super Admin.',
                'mode' => 'support',
            ])
            ->assertOk()
            ->assertJsonPath('support', true)
            ->assertJsonPath('conversation_id', $conversation->id);

        Http::assertNothingSent();

        $conversation->refresh();
        $this->assertSame('support', $conversation->mode);
        $this->assertSame(3, $conversation->messages()->count());
        $this->assertSame(1, AiChatConversation::count());
    }

    public function test_super_admin_membalas_via_ajax_tanpa_modal(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Bantuan AJAX',
            'mode' => 'support',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson("/{$this->admin->id}/sa/ai-chat/{$conversation->id}/reply", [
                'message' => 'Balasan langsung tanpa modal.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'replied');

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Balasan langsung tanpa modal.',
        ]);
    }

    public function test_balasan_identik_tidak_tersimpan_ganda(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Bantuan Idempoten',
            'mode' => 'support',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $url = "/{$this->admin->id}/sa/ai-chat/{$conversation->id}/reply";

        $this->actingAs($this->admin)->postJson($url, ['message' => 'Balasan sama.'])->assertOk();
        $this->actingAs($this->admin)->postJson($url, ['message' => 'Balasan sama.'])->assertOk();

        $this->assertSame(1, $conversation->messages()->where('role', 'admin')->where('content', 'Balasan sama.')->count());
    }

    public function test_kembali_ke_ai_mempertahankan_percakapan_dan_konteks(): void
    {
        $conversation = AiChatConversation::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'title' => 'Bantuan yang dilanjutkan AI',
            'mode' => 'support',
            'status' => 'replied',
            'last_message_at' => now(),
        ]);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Pertanyaan awal.']);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'admin', 'content' => 'Jawaban Super Admin sebelumnya.']);

        $this->actingAs($this->admin)
            ->postJson("/{$this->admin->id}/ai-chat/{$conversation->id}/resume-ai")
            ->assertOk()
            ->assertJsonPath('mode', 'ai');

        $this->assertSame('ai', $conversation->fresh()->mode);
        $this->assertSame(2, $conversation->messages()->count());

        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Lanjutan AI.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", [
                'conversation_id' => $conversation->id,
                'message' => 'Lanjutkan dengan AI.',
                'mode' => 'ai',
            ])
            ->assertOk()
            ->assertJsonPath('reply', 'Lanjutan AI.');

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return str_contains($payload, 'Pertanyaan awal.')
                && str_contains($payload, 'Jawaban Super Admin sebelumnya.')
                && str_contains($payload, 'Lanjutkan dengan AI.');
        });

        $this->assertSame(1, AiChatConversation::count());
    }

    public function test_widget_chat_tampil_di_layout(): void
    {
        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/profile/my")
            ->assertOk()
            ->assertSee('aiChatFab')
            ->assertSee('Tanya ALIM AI')
            ->assertSee('ALIM Assistant');
    }

    public function test_kirim_chat_menyimpan_riwayat(): void
    {
        Http::fake([
            'ai.chat.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Ada 2 santri aktif.'], 'finish_reason' => 'stop']],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'Berapa santri aktif?'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reply', 'Ada 2 santri aktif.');

        $conversation = AiChatConversation::firstOrFail();
        $this->assertSame($this->admin->id, $conversation->user_id);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertDatabaseHas('ai_chat_messages', ['conversation_id' => $conversation->id, 'role' => 'user']);
        $this->assertDatabaseHas('ai_chat_messages', ['conversation_id' => $conversation->id, 'role' => 'assistant']);
    }

    public function test_tool_count_students_dijalankan_sebelum_jawaban(): void
    {
        Http::fakeSequence()
            ->push([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => ['name' => 'count_students', 'arguments' => '{"status":"active"}'],
                        ]],
                    ],
                    'finish_reason' => 'tool_calls',
                ]],
            ], 200)
            ->push([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Saat ini ada 2 santri aktif.'], 'finish_reason' => 'stop']],
            ], 200);

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'Berapa santri aktif?'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $tool = collect($messages)->firstWhere('role', 'tool');

            return $tool !== null
                && str_contains((string) ($tool['content'] ?? ''), '"count":2')
                && ! str_contains((string) ($tool['content'] ?? ''), 'Santri 0');
        });
    }

    public function test_pesan_kecil_tidak_memanggil_provider_dan_tanpa_token(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'cek cek cek oke'])
            ->assertOk()
            ->assertJsonPath('reply', 'Oke, siap!')
            ->assertJsonPath('usage.total_tokens', 0);

        Http::assertNothingSent();
        $this->assertSame(2, AiChatMessage::count());
    }

    public function test_chat_tanpa_provider_mengembalikan_503(): void
    {
        $this->provider->forceFill(['is_active' => false])->save();

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'Berapa santri aktif?'])
            ->assertStatus(503)
            ->assertJsonPath('ok', false);
    }

    public function test_user_tidak_dapat_membuka_riwayat_user_lain(): void
    {
        $other = User::create([
            'name' => 'Guru Chat', 'email' => 'guru.chat@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $conversation = AiChatConversation::create([
            'user_id' => $other->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan Guru',
        ]);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Halo']);

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/ai-chat/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_super_admin_dapat_memantau_riwayat_chat(): void
    {
        $other = User::create([
            'name' => 'Guru Monitor', 'email' => 'guru.monitor@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $conversation = AiChatConversation::create([
            'user_id' => $other->id,
            'school_id' => $this->school->id,
            'title' => 'Percakapan yang Dipantau',
            'last_message_at' => now(),
        ]);
        AiChatMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Pertanyaan guru']);

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/sa/ai-chat")
            ->assertOk()
            ->assertSee('Percakapan yang Dipantau')
            ->assertSee('Guru Monitor');

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/sa/ai-chat/{$conversation->id}")
            ->assertOk()
            ->assertSee('Pertanyaan guru');
    }

    public function test_user_biasa_tidak_dapat_memantau_chat(): void
    {
        $regular = User::create([
            'name' => 'Guru Biasa Chat', 'email' => 'guru.biasa.chat@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->actingAs($regular);

        $request = Request::create('/sa/ai-chat', 'GET');
        $request->setUserResolver(fn () => $regular);

        $this->expectException(HttpException::class);
        app(AiChatMonitorController::class)->index($request, $regular->id);
    }
}
