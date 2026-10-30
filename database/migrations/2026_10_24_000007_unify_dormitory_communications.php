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
        Schema::table('humas_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('humas_announcements', 'visibility')) {
                $table->string('visibility', 20)->nullable()->after('audience');
            }
            if (! Schema::hasColumn('humas_announcements', 'needs_response')) {
                $table->boolean('needs_response')->default(false)->after('ack_required');
            }
            if (! Schema::hasColumn('humas_announcements', 'attachment_path')) {
                $table->string('attachment_path', 500)->nullable()->after('banner_path');
            }
        });

        if (! Schema::hasTable('humas_announcement_responses')) {
            Schema::create('humas_announcement_responses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('humas_announcement_id')
                    ->constrained('humas_announcements')
                    ->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('parent_name')->nullable();
                $table->enum('response_type', ['ack', 'question', 'complaint'])->default('ack');
                $table->text('message')->nullable();
                $table->timestamps();
                $table->unique(['humas_announcement_id', 'student_id'], 'humas_ann_resp_ann_student_unique');
                $table->index('humas_announcement_id');
            });
        }

        $this->migrateDormitoryPosts();
        $this->migrateDormitoryBroadcasts();
    }

    private function migrateDormitoryPosts(): void
    {
        if (! Schema::hasTable('dormitory_posts')) {
            return;
        }

        $hasImages = Schema::hasTable('dormitory_post_images');

        foreach (DB::table('dormitory_posts')->whereNull('deleted_at')->orderBy('id')->cursor() as $post) {
            if (DB::table('humas_announcements')->where('id', $post->id)->exists()) {
                continue;
            }

            $dormitory = DB::table('dormitories')->where('id', $post->dormitory_id)->first();
            $audience = match ($post->visibility) {
                'pengurus' => 'gtk',
                'umum' => 'all',
                default => 'wali',
            };
            $recipientGroup = match ($post->visibility) {
                'pengurus' => 'gtk',
                'umum' => 'both',
                default => 'wali',
            };
            $published = (bool) $post->is_active;

            DB::table('humas_announcements')->insert([
                'id' => $post->id,
                'school_id' => $dormitory?->school_id,
                'target_type' => 'dormitory',
                'dormitory_id' => $post->dormitory_id,
                'study_group_id' => null,
                'title' => $post->title,
                'slug' => Str::slug($post->title).'-'.substr($post->id, 0, 8),
                'category' => $post->category ?: 'umum',
                'severity' => 'info',
                'channels' => json_encode(['inapp', 'push']),
                'ack_required' => false,
                'recipient_group' => $recipientGroup,
                'audience' => $audience,
                'visibility' => $post->visibility,
                'needs_response' => (bool) $post->needs_response,
                'excerpt' => Str::limit(strip_tags((string) $post->content), 180),
                'content' => $post->content,
                'banner_path' => $post->banner_path,
                'attachment_path' => $post->attachment_path,
                'status' => $published ? 'published' : 'draft',
                'is_pinned' => (bool) $post->is_pinned,
                'published_at' => $published ? $post->created_at : null,
                'expires_at' => null,
                'created_by' => $post->created_by,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]);

            $migratedImages = false;
            if ($hasImages) {
                foreach (DB::table('dormitory_post_images')->where('dormitory_post_id', $post->id)->orderBy('sort_order')->get() as $image) {
                    DB::table('humas_announcement_images')->insert([
                        'id' => $image->id,
                        'humas_announcement_id' => $post->id,
                        'path' => $image->path,
                        'sort_order' => $image->sort_order,
                        'created_at' => $image->created_at,
                        'updated_at' => $image->updated_at,
                    ]);
                    $migratedImages = true;
                }
            }

            if (! $migratedImages && $post->banner_path) {
                DB::table('humas_announcement_images')->insert([
                    'id' => (string) Str::uuid(),
                    'humas_announcement_id' => $post->id,
                    'path' => $post->banner_path,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('dormitory_post_responses')) {
                foreach (DB::table('dormitory_post_responses')->where('post_id', $post->id)->get() as $response) {
                    DB::table('humas_announcement_responses')->insertOrIgnore([
                        'id' => $response->id,
                        'humas_announcement_id' => $post->id,
                        'student_id' => $response->student_id,
                        'parent_name' => $response->parent_name,
                        'response_type' => in_array($response->response_type, ['ack', 'question', 'complaint'], true)
                            ? $response->response_type
                            : 'ack',
                        'message' => $response->message,
                        'created_at' => $response->created_at,
                        'updated_at' => $response->updated_at,
                    ]);
                }
            }
        }
    }

    private function migrateDormitoryBroadcasts(): void
    {
        if (! Schema::hasTable('dormitory_emergency_broadcasts')) {
            return;
        }

        foreach (DB::table('dormitory_emergency_broadcasts')->whereNull('deleted_at')->orderBy('id')->cursor() as $broadcast) {
            if (DB::table('humas_announcements')->where('id', $broadcast->id)->exists()) {
                continue;
            }

            $dormitory = DB::table('dormitories')->where('id', $broadcast->dormitory_id)->first();
            $channels = match ($broadcast->broadcast_via) {
                'whatsapp' => ['wa'],
                'all' => ['inapp', 'push', 'wa'],
                default => ['inapp'],
            };

            DB::table('humas_announcements')->insert([
                'id' => $broadcast->id,
                'school_id' => $dormitory?->school_id,
                'target_type' => 'dormitory',
                'dormitory_id' => $broadcast->dormitory_id,
                'study_group_id' => null,
                'title' => $broadcast->title,
                'slug' => Str::slug($broadcast->title).'-'.substr($broadcast->id, 0, 8),
                'category' => 'broadcast',
                'severity' => $broadcast->severity,
                'channels' => json_encode($channels),
                'ack_required' => (bool) $broadcast->ack_required,
                'recipient_group' => 'wali',
                'audience' => 'wali',
                'visibility' => 'wali',
                'needs_response' => false,
                'excerpt' => Str::limit(strip_tags((string) $broadcast->content), 180),
                'content' => $broadcast->content,
                'banner_path' => $broadcast->banner_path,
                'attachment_path' => null,
                'status' => 'published',
                'is_pinned' => in_array($broadcast->severity, ['urgent', 'emergency'], true),
                'published_at' => $broadcast->created_at,
                'expires_at' => $broadcast->expires_at,
                'created_by' => $broadcast->created_by,
                'created_at' => $broadcast->created_at,
                'updated_at' => $broadcast->updated_at,
            ]);

            if ($broadcast->banner_path) {
                DB::table('humas_announcement_images')->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'humas_announcement_id' => $broadcast->id,
                    'path' => $broadcast->banner_path,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_announcement_responses');

        Schema::table('humas_announcements', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'needs_response', 'attachment_path']);
        });
    }
};
