<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use Illuminate\View\Compilers\BladeCompiler;

final readonly class AuthorizationBladeCompiler
{
    public function register(BladeCompiler $blade): void
    {
        $blade->directive('permission', function (string $expression): string {
            return $this->compile($expression, 'single');
        });

        $blade->directive('endpermission', function (): string {
            return '<?php endif; ?>';
        });

        $blade->directive('permissionany', function (string $expression): string {
            return $this->compile($expression, 'any');
        });

        $blade->directive('endpermissionany', function (): string {
            return '<?php endif; ?>';
        });
    }

    private function compile(string $expression, string $mode): string
    {
        $trimmed = trim($expression, " \t\n\r\0\x0B()");
        if ($trimmed === '') {
            return '<?php if (false): ?>';
        }

        $parts = array_map('trim', explode(',', $trimmed));
        $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));
        $literals = array_map(static fn ($p) => "'".addslashes($p)."'", $parts);

        $permissionList = '['.implode(',', $literals).']';

        $decision = $mode === 'any'
            ? '$__authzDecision = false; '
              .'foreach ($__authzPermissions as $__authzPerm) { '
              .'    if ($__authz->allows($__authzUser, $__authzPerm, $__authzContext)) { '
              .'        $__authzDecision = true; break; '
              .'    } '
              .'}'
            : '$__authzDecision = $__authz->allows($__authzUser, $__authzPermissions[0], $__authzContext);';

        return '<?php '
            .'$__authz = app(\\App\\Authorization\\Services\\AuthorizationManager::class); '
            .'$__authzUser = auth()->user(); '
            .'$__authzContext = app()->bound(\\App\\Authorization\\ValueObjects\\OrganizationContext::class) '
            .'? app(\\App\\Authorization\\ValueObjects\\OrganizationContext::class) : null; '
            ."\$__authzPermissions = {$permissionList}; "
            .'$__authzDecision = false; '
            .'if ($__authzContext instanceof \\App\\Authorization\\ValueObjects\\OrganizationContext && $__authzUser !== null) { '
            ."    {$decision} "
            .'} '
            .'if ($__authzDecision): ?>';
    }
}
