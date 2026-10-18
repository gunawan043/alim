<?php

namespace Tests\Feature;

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\SuperAdmin\AiChatMonitorController;
use App\Models\AcademicYear;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiProviderSetting;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
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

    public function test_chat_tanpa_provider_mengembalikan_503(): void
    {
        $this->provider->forceFill(['is_active' => false])->save();

        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/ai-chat/send", ['message' => 'Halo'])
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
