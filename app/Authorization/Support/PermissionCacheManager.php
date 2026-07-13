<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\Contracts\PermissionCacheManager as PermissionCacheManagerInterface;
use App\Authorization\DTO\PermissionBag;
use Illuminate\Cache\CacheManager;

final class PermissionCacheManager implements PermissionCacheManagerInterface
{
    private bool $effectiveUseTags;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly int $ttl,
        private readonly string $prefix,
        bool $useTags,
    ) {
        $this->effectiveUseTags = $this->resolveUseTags($useTags);
    }

    private function resolveUseTags(bool $configured): bool
    {
        if (! $configured) {
            return false;
        }

        try {
            $store = $this->cache->store();
            return $store instanceof \Illuminate\Cache\TaggableStore;
        } catch (\Throwable) {
            return false;
        }
    }

    public function remember(string $userId, string $scopeKey, callable $resolver): PermissionBag
    {
        $key = $this->keyFor($userId, $scopeKey);
        $tags = $this->tagsFor($userId);

        if ($this->effectiveUseTags) {
            $cached = $this->cache->tags($tags)->get($key);
        } else {
            $cached = $this->cache->get($key);
        }

        if ($cached !== null) {
            return PermissionBag::fromArray($cached);
        }

        $bag = $resolver();
        $this->put($bag, $userId, $scopeKey);

        return $bag;
    }

    public function get(string $userId, string $scopeKey): ?PermissionBag
    {
        $key = $this->keyFor($userId, $scopeKey);
        $tags = $this->tagsFor($userId);

        if ($this->effectiveUseTags) {
            $cached = $this->cache->tags($tags)->get($key);
        } else {
            $cached = $this->cache->get($key);
        }

        return $cached !== null ? PermissionBag::fromArray($cached) : null;
    }

    public function put(PermissionBag $bag, string $userId, ?string $scopeKey = null): void
    {
        $key = $this->keyFor($userId, (string) $scopeKey);
        $tags = $this->tagsFor($userId);

        $data = $bag->toArray();

        if ($this->effectiveUseTags) {
            $this->cache->tags($tags)->put($key, $data, $this->ttl);
        } else {
            $this->cache->put($key, $data, $this->ttl);
        }
    }

    public function forget(string $userId, string $scopeKey): void
    {
        $key = $this->keyFor($userId, $scopeKey);
        $tags = $this->tagsFor($userId);

        if ($this->effectiveUseTags) {
            $this->cache->tags($tags)->forget($key);
        } else {
            $this->cache->forget($key);
        }
    }

    public function forgetUser(int|string $userId): void
    {
        if (! $this->effectiveUseTags) {
            return;
        }

        $this->cache->tags([$this->tagForUser((string) $userId)])->flush();
    }

    public function forgetScope(string $scopeKey): void
    {
        // Scoped flush requires iterating tags; only feasible with tags.
        if (! $this->effectiveUseTags) {
            return;
        }

        $this->cache->store()->tags([])->flush();
    }

    public function warm(array $userIds): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            try {
                $user = \App\Models\User::find((string) $userId);
                if (! $user instanceof \App\Models\User) {
                    continue;
                }

                // Resolve organization contexts for this user
                $contexts = $this->getContextsForUser($user);

                foreach ($contexts as $context) {
                    $resolver = function () use ($user, $context): PermissionBag {
                        $builder = app(\App\Authorization\Contracts\PermissionBuilder::class);
                        return $builder->build($user, $context);
                    };

                    $scopeKey = (string) $context->toScopeKey();
                    $this->remember((string) $user->getKey(), $scopeKey, $resolver);
                    ++$count;
                }
            } catch (\Throwable) {
                // Warm is best-effort.
            }
        }

        return $count;
    }

    /**
     * @return array<int, \App\Authorization\ValueObjects\OrganizationContext>
     */
    private function getContextsForUser(\App\Models\User $user): array
    {
        $contexts = [];

        if (method_exists($user, 'organizationMemberships')) {
            $memberships = $user->organizationMemberships()
                ->with(['organization', 'workUnit.year'])
                ->where('status', 'active')
                ->get();

            foreach ($memberships as $membership) {
                $org = $membership->organization;
                $year = $membership->workUnit?->year;
                if ($org && $year) {
                    $contexts[] = new \App\Authorization\ValueObjects\OrganizationContext(
                        school: (string) $org->id,
                        academicYear: $year->tahun_ajaran,
                        role: $membership->role ?? 'teacher',
                    );
                }
            }
        }

        return $contexts;
    }

    public function isTaggable(): bool
    {
        return $this->effectiveUseTags;
    }

    private function keyFor(string $userId, string $scopeKey): string
    {
        return "{$this->prefix}:{$userId}:{$scopeKey}";
    }

    /**
     * @return array<int, string>
     */
    private function tagsFor(string $userId): array
    {
        return [$this->tagForUser($userId)];
    }

    private function tagForUser(string $userId): string
    {
        return "authz:user:{$userId}";
    }
}