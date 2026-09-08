<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BindOrganizationContext;
use App\Http\Middleware\CheckIpBlocked;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureEmployeeAccess;
use App\Http\Middleware\EnsureRoleAccess;
use App\Http\Middleware\EnsureSuperAdminOrSystemAdmin;
use App\Http\Middleware\Localization;
use App\Http\Middleware\MinRoleLevel;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RestrictDormitoryUserFromStudents;
use App\Http\Middleware\RoleLevelMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SchoolContextMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\ShareRoleId;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\WaliSchoolContextMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            Localization::class,
            SubstituteBindings::class,
            ShareRoleId::class,
            SchoolContextMiddleware::class,
            BindOrganizationContext::class,
            CheckIpBlocked::class,
        ],

        'api' => [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
            SecurityHeadersMiddleware::class,
            RequestIdMiddleware::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'min.role' => MinRoleLevel::class,
        'role' => RoleMiddleware::class,
        'role.level' => RoleLevelMiddleware::class,
        'role.access' => EnsureRoleAccess::class,
        'super.or.system' => EnsureSuperAdminOrSystemAdmin::class,
        'school.context' => SchoolContextMiddleware::class,
        'wali.school.context' => WaliSchoolContextMiddleware::class,
        'ip.blocked' => CheckIpBlocked::class,
        'employee.access' => EnsureEmployeeAccess::class,
        'organization.context' => BindOrganizationContext::class,
        'permission' => RequirePermission::class,
        'permission-all' => RequirePermission::class,
        'dormitory.restrict' => RestrictDormitoryUserFromStudents::class,
    ];
}
