<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ConnectActorIdentity;
use App\Models\ConnectSsoHandoff;
use App\Models\Dormitory;
use App\Models\Permission;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data uji integrasi ALIM Core ↔ ALIM Connect (PDCA Cycle 9).
 *
 * Hanya boleh berjalan di APP_ENV=staging. Menolak jalan di local/production.
 *
 * Yang dibuat:
 *  - 1 asrama uji + 1 kepala asrama aktif (aktor penyetuju).
 *  - 2 akun SSO aktif: satu berizin `whatsapp_connect_view`, satu tanpa izin
 *    (untuk uji `permission_revoked`).
 *  - 1 mapping `connect_actor_identities` terverifikasi untuk kepala asrama.
 *  - 2 permit: satu `pending` yang masih berlaku, satu `pending` yang sudah
 *    lewat `expected_return_datetime` (uji `expired`).
 *
 * Kode handoff sengaja TIDAK dibuat di sini karena TTL-nya 60 detik; lihat
 * `php artisan alim:connect-staging-handoff`.
 *
 * Semua identitas memakai domain @staging.invalid dan nomor 6280000xxxx agar
 * jelas bukan data produksi. Seeder idempoten.
 *
 *   php artisan db:seed --class=ConnectStagingTestDataSeeder --env=staging
 */
class ConnectStagingTestDataSeeder extends Seeder
{
    public const WORK_UNIT_CODE = 'STG-CONNECT-WU';

    public const SCHOOL_NPSN = '99999901';

    public const DORM_CODE = 'STG-CONNECT-001';

    public const STUDENT_NIS = 'STG-0001';

    public const WING_CODE = 'STG-W1';

    public const ROOM_CODE = 'STG-R1';

    public const HEAD_EMAIL = 'kepala.asrama@staging.invalid';

    public const SSO_EMAIL = 'sso.penguji@staging.invalid';

    public const SSO_NOPERM_EMAIL = 'sso.tanpaizin@staging.invalid';

    public const WA_HEAD = '6280000000001';

    public const WA_UNMAPPED = '6280000000099';

    public const TEST_PASSWORD = 'Staging-Cycle9!';

    public function run(): void
    {
        if (app()->environment() !== 'staging') {
            $this->command->error('Seeder ini hanya untuk APP_ENV=staging (env sekarang: '.app()->environment().').');
            $this->command->line('Dibatalkan tanpa menulis data apa pun.');

            return;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->command->error('Database staging wajib SQLite terisolasi; driver sekarang: '.DB::connection()->getDriverName().'.');

            return;
        }

        $dormitory = $this->dormitory();
        $head = $this->head($dormitory);
        $ssoUser = $this->ssoUser();
        $ssoNoPerm = $this->user(self::SSO_NOPERM_EMAIL, 'SSO Penguji (tanpa izin)');
        $this->identity($head);

        $student = $this->student($dormitory);
        $room = $this->room($dormitory);
        $year = $this->academicYear();

        $active = $this->permit($dormitory, $head, $student, $room, $year, 'stg-active', now()->addHours(2), now()->addDays(2), 'pending');
        $expired = $this->permit($dormitory, $head, $student, $room, $year, 'stg-expired', now()->subHours(6), now()->subHours(1), 'pending');

        $this->command->info('=== Data uji staging ALIM Connect siap ===');
        $this->command->table(['Item', 'Nilai'], [
            ['Asrama', $dormitory->code.' — '.$dormitory->name],
            ['User id kepala asrama', $head->id],
            ['Email kepala asrama', $head->email],
            ['Password (semua akun uji)', self::TEST_PASSWORD],
            ['Identitas WA kepala (aktor)', self::WA_HEAD],
            ['Nomor tanpa mapping', self::WA_UNMAPPED],
            ['User SSO berizin', $ssoUser->email.' — '.$ssoUser->id],
            ['User SSO tanpa izin', $ssoNoPerm->email.' — '.$ssoNoPerm->id],
            ['Permit pending aktif', $active->id],
            ['Permit pending kedaluwarsa', $expired->id],
        ]);
        $this->command->line('Kode handoff: php artisan alim:connect-staging-handoff --env=staging');
    }

    private function workUnit(): WorkUnit
    {
        return WorkUnit::firstOrCreate(
            ['code' => self::WORK_UNIT_CODE],
            ['name' => 'Unit Uji Connect (staging)', 'type' => 'asrama', 'is_active' => true]
        );
    }

    private function school(WorkUnit $workUnit): School
    {
        return School::firstOrCreate(
            ['npsn' => self::SCHOOL_NPSN],
            [
                'work_unit_id' => $workUnit->id,
                'name' => 'Satuan Pendidikan Uji Connect (staging)',
                'school_level' => 'SMP',
                'school_status' => 'swasta',
                'operational_hours' => 'pagi',
                'is_active' => true,
                'school_gender' => 'putra',
                'boarding_type' => 'berasrama',
                'kopsis_active' => false,
                'min_effective_days_per_week' => 5,
            ]
        );
    }

    private function dormitory(): Dormitory
    {        $existing = Dormitory::query()->where('code', self::DORM_CODE)->first();

        if ($existing) {
            return $existing;
        }

        return Dormitory::create([
            'work_unit_id' => $this->workUnit()->id,
            'school_id' => $this->school($this->workUnit())->id,
            'code' => self::DORM_CODE,
            'name' => 'Asrama Uji Connect (staging)',
            'gender' => 'putra',
            'address' => 'Staging — bukan lokasi nyata',
            'capacity' => 10,
            'total_rooms' => 0,
            'total_wings' => 0,
            'is_active' => true,
        ]);
    }

    private function head(Dormitory $dormitory): User
    {
        $head = $this->user(self::HEAD_EMAIL, 'Kepala Asrama Uji');

        if ((string) $dormitory->head_id !== (string) $head->id) {
            $dormitory->forceFill(['head_id' => $head->id])->save();
        }

        return $head;
    }

    private function ssoUser(): User
    {
        $user = $this->user(self::SSO_EMAIL, 'SSO Penguji Berizin');

        $permission = Permission::firstOrCreate(
            ['name' => 'whatsapp_connect_view', 'guard_name' => 'web'],
            ['group' => 'Integrasi', 'description' => 'Akses integrasi WhatsApp Connect']
        );

        if (! $user->hasDirectPermission($permission)) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    private function user(string $email, string $name): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'username' => Str::before($email, '@'),
            'password' => Hash::make(self::TEST_PASSWORD),
            'is_active' => true,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'deleted_at' => null,
        ])->save();

        return $user;
    }

    private function identity(User $head): ConnectActorIdentity
    {
        return ConnectActorIdentity::updateOrCreate(
            ['channel' => 'whatsapp', 'external_identity' => self::WA_HEAD],
            [
                'user_id' => $head->id,
                'display_name' => 'Kepala Asrama Uji (WA staging)',
                'is_active' => true,
                'verified_at' => now(),
                'verified_by' => null,
            ]
        );
    }

    private function student(Dormitory $dormitory): object
    {
        $existing = DB::table('students')->where('nis', self::STUDENT_NIS)->first();

        if ($existing) {
            return $existing;
        }

        $id = (string) Str::uuid();

        DB::table('students')->insert([
            'id' => $id,
            'school_id' => $dormitory->school_id,
            'nis' => self::STUDENT_NIS,
            'nisn' => self::STUDENT_NIS,
            'name' => 'Santri Uji Connect (staging)',
            'gender' => 'L',
            'special_needs' => false,
            'residence_type' => 'asrama',
            'transportation' => 'jalan_kaki',
            'sibling_count' => 0,
            'is_kps_receiver' => false,
            'is_kip_receiver' => false,
            'is_pip_eligible' => false,
            'status' => 'aktif',
            'wali_status' => 'yatim_piatu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('students')->where('id', $id)->first();
    }

    private function wing(Dormitory $dormitory): object
    {
        $existing = DB::table('dormitory_wings')->where('dormitory_id', $dormitory->id)->where('code', self::WING_CODE)->first();

        if ($existing) {
            return $existing;
        }

        $id = (string) Str::uuid();

        DB::table('dormitory_wings')->insert([
            'id' => $id,
            'dormitory_id' => $dormitory->id,
            'code' => self::WING_CODE,
            'name' => 'Sayap Uji',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 4,
            'total_rooms' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('dormitory_wings')->where('id', $id)->first();
    }

    private function room(Dormitory $dormitory): object
    {
        $existing = DB::table('dormitory_rooms')->where('dormitory_id', $dormitory->id)->where('code', self::ROOM_CODE)->first();

        if ($existing) {
            return $existing;
        }

        $id = (string) Str::uuid();

        DB::table('dormitory_rooms')->insert([
            'id' => $id,
            'dormitory_id' => $dormitory->id,
            'wing_id' => $this->wing($dormitory)->id,
            'code' => self::ROOM_CODE,
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 4,
            'room_type' => 'reguler',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('dormitory_rooms')->where('id', $id)->first();
    }

    private function academicYear(): object
    {
        $existing = DB::table('academic_years')->where('is_active', true)->first();

        if ($existing) {
            return $existing;
        }

        $id = (string) Str::uuid();

        DB::table('academic_years')->insert([
            'id' => $id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('academic_years')->where('id', $id)->first();
    }

    private function permit(
        Dormitory $dormitory,
        User $head,
        object $student,
        object $room,
        object $year,
        string $suffix,
        \DateTimeInterface $departure,
        \DateTimeInterface $expectedReturn,
        string $status
    ): object {
        $existing = DB::table('dormitory_permits')
            ->where('purpose', 'Uji integrasi Connect '.$suffix)
            ->first();

        if ($existing) {
            return $existing;
        }

        $id = (string) Str::uuid();

        DB::table('dormitory_permits')->insert([
            'id' => $id,
            'student_id' => $student->id,
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah (uji staging)',
            'purpose' => 'Uji integrasi Connect '.$suffix,
            'departure_datetime' => $departure,
            'expected_return_datetime' => $expectedReturn,
            'status' => $status,
            'created_by' => $head->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::createQuietly([
            'id' => (string) Str::uuid(),
            'user_id' => $head->id,
            'action' => 'STAGING_TEST_PERMIT_CREATED',
            'table_name' => 'dormitory_permits',
            'record_id' => $id,
            'metadata' => ['note' => 'Permit uji staging Connect ('.$suffix.')'],
        ]);

        return DB::table('dormitory_permits')->where('id', $id)->first();
    }
}
