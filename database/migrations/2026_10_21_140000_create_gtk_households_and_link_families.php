<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gtk_households')) {
            Schema::create('gtk_households', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('label', 191)->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('gtk_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('gtk_profiles', 'household_id')) {
                $table->foreignUuid('household_id')->nullable()->after('user_id')
                    ->constrained('gtk_households')->nullOnDelete();
            }
            if (! Schema::hasColumn('gtk_profiles', 'spouse_gtk_profile_id')) {
                $table->foreignUuid('spouse_gtk_profile_id')->nullable()->after('household_id')
                    ->constrained('gtk_profiles')->nullOnDelete();
            }
        });

        Schema::table('gtk_family_members', function (Blueprint $table) {
            if (! Schema::hasColumn('gtk_family_members', 'household_id')) {
                $table->foreignUuid('household_id')->nullable()->after('gtk_profile_id')
                    ->constrained('gtk_households')->nullOnDelete();
            }
            if (! Schema::hasColumn('gtk_family_members', 'linked_gtk_profile_id')) {
                $table->foreignUuid('linked_gtk_profile_id')->nullable()->after('household_id')
                    ->constrained('gtk_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('gtk_family_members', 'member_key')) {
                $table->string('member_key', 64)->nullable()->after('relationship');
                $table->index(['household_id', 'member_key'], 'gtk_family_household_member_key_index');
            }
        });

        // Satu rumah tangga awal untuk setiap GTK yang sudah ada.
        DB::table('gtk_profiles')->orderBy('created_at')->chunkById(200, function ($profiles) {
            foreach ($profiles as $profile) {
                if ($profile->household_id) {
                    continue;
                }

                $householdId = (string) Str::uuid();
                DB::table('gtk_households')->insert([
                    'id' => $householdId,
                    'label' => 'Rumah tangga GTK',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('gtk_profiles')->where('id', $profile->id)->update(['household_id' => $householdId]);
            }
        });

        DB::table('gtk_family_members')->orderBy('created_at')->chunkById(200, function ($members) {
            foreach ($members as $member) {
                $profile = DB::table('gtk_profiles')->where('id', $member->gtk_profile_id)->first();
                if (! $profile) {
                    continue;
                }

                $key = $this->memberKey($member);
                DB::table('gtk_family_members')->where('id', $member->id)->update([
                    'household_id' => $profile->household_id,
                    'member_key' => $key,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gtk_family_members', function (Blueprint $table) {
            if (Schema::hasColumn('gtk_family_members', 'member_key')) {
                $table->dropIndex('gtk_family_household_member_key_index');
                $table->dropColumn('member_key');
            }
            if (Schema::hasColumn('gtk_family_members', 'linked_gtk_profile_id')) {
                $table->dropForeign(['linked_gtk_profile_id']);
                $table->dropColumn('linked_gtk_profile_id');
            }
            if (Schema::hasColumn('gtk_family_members', 'household_id')) {
                $table->dropForeign(['household_id']);
                $table->dropColumn('household_id');
            }
        });

        Schema::table('gtk_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('gtk_profiles', 'spouse_gtk_profile_id')) {
                $table->dropForeign(['spouse_gtk_profile_id']);
                $table->dropColumn('spouse_gtk_profile_id');
            }
            if (Schema::hasColumn('gtk_profiles', 'household_id')) {
                $table->dropForeign(['household_id']);
                $table->dropColumn('household_id');
            }
        });

        Schema::dropIfExists('gtk_households');
    }

    private function memberKey(object $member): string
    {
        $name = strtolower(trim((string) $member->nama));
        $birth = $member->tanggal_lahir ? (string) $member->tanggal_lahir : '';
        $relationship = strtolower((string) $member->relationship);

        return substr(hash('sha256', $relationship.'|'.$name.'|'.$birth), 0, 64);
    }
};
