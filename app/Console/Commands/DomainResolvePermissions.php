<?php

namespace App\Console\Commands;

use App\Models\StructuralAssignment;
use App\Models\User;
use Illuminate\Console\Command;

class DomainResolvePermissions extends Command
{
    protected $signature = 'domain:resolve-permissions
                            {--user= : Filter by user ID}';

    protected $description = 'Compare existing effective permissions vs domain-based permissions for cutover verification';

    public function handle(): int
    {
        $userId = $this->option('user');

        $query = User::with(['roles']);
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->error('No users found.');

            return self::FAILURE;
        }

        $this->info("Comparing permissions for {$users->count()} user(s).\n");
        $this->info(str_repeat('-', 120));

        foreach ($users as $user) {
            $this->line("<fg=yellow>👤 {$user->name} ({$user->id})</>");

            // Existing: direct Spatie permissions
            $existingPerms = [];
            try {
                $existingPerms = $user->getAllPermissions()->pluck('name')->toArray();
            } catch (\Throwable $e) {
                $existingPerms = [];
            }

            // Domain-based: union of active assignment domain permissions
            $domainPerms = [];
            $assignments = StructuralAssignment::active()
                ->where('user_id', $user->id)
                ->with(['position.domain.permissions'])
                ->get();

            $activeDomains = [];
            foreach ($assignments as $assignment) {
                if ($assignment->position && $assignment->position->domain) {
                    $activeDomains[] = $assignment->position->domain->name;
                    foreach ($assignment->position->domain->permissions as $perm) {
                        $domainPerms[] = $perm->name;
                    }
                }
            }
            $domainPerms = array_unique($domainPerms);

            // Union (what hasDomainPermission would return)
            $unionPerms = array_values(array_unique(array_merge($existingPerms, $domainPerms)));
            sort($unionPerms);

            $this->line('  Active domains: '.implode(', ', $activeDomains ?: ['none']));
            $this->line('  Existing perms count: '.count($existingPerms));
            $this->line('  Domain perms count: '.count($domainPerms));
            $this->line('  Union perms count: '.count($unionPerms));

            // Show diff
            $missingFromExisting = array_diff($domainPerms, $existingPerms);
            $missingFromDomain = array_diff($existingPerms, $domainPerms);

            if (! empty($missingFromExisting)) {
                $this->line('  ⚠️ Permissions in domain but not in Spatie role grant:');
                foreach (array_slice($missingFromExisting, 0, 10) as $p) {
                    $this->line("     - {$p}");
                }
                if (count($missingFromExisting) > 10) {
                    $this->line('     ... and '.(count($missingFromExisting) - 10).' more');
                }
            }

            if (! empty($missingFromDomain)) {
                $this->line('  ⚠️ Permissions in Spatie role but not in domain matrix:');
                foreach (array_slice($missingFromDomain, 0, 10) as $p) {
                    $this->line("     - {$p}");
                }
                if (count($missingFromDomain) > 10) {
                    $this->line('     ... and '.(count($missingFromDomain) - 10).' more');
                }
            }

            if (empty($missingFromExisting) && empty($missingFromDomain)) {
                $this->line('  ✅ Existing and domain permissions are identical.');
            }

            $this->line(str_repeat('-', 120)."\n");
        }

        return self::SUCCESS;
    }
}
