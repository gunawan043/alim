<?php

namespace App\Http\Controllers\Osipp;

use App\Http\Controllers\Controller;
use App\Models\Osipp\OsippPeriod;
use App\Services\Osipp\OsippAccess;
use App\Services\Osipp\OsippWorkflowService;
use App\Services\Osipp\OsippWorkspaceService;
use Illuminate\Http\Request;

abstract class OsippBaseController extends Controller
{
    public function __construct(
        protected readonly OsippAccess $access,
        protected readonly OsippWorkspaceService $workspace,
        protected readonly OsippWorkflowService $workflow,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function context(Request $request, ?string $periodId = null): array
    {
        return $this->workspace->context($request, $periodId);
    }

    protected function ensureManage(array $context): void
    {
        abort_unless($context['canManage'] ?? false, 403, 'Anda tidak berwenang mengelola data OSIPP.');
    }

    protected function ensureVerify(array $context): void
    {
        abort_unless($context['canVerify'] ?? false, 403, 'Anda tidak berwenang memverifikasi data OSIPP.');
    }

    protected function ensureEvaluate(array $context): void
    {
        abort_unless($context['canEvaluate'] ?? false, 403, 'Anda tidak berwenang mengevaluasi kepengurusan OSIPP.');
    }

    protected function authorizePeriod(OsippPeriod $period): void
    {
        $this->workspace->ensurePeriodAccess(request()->user(), $period);
    }

    protected function ensurePeriodOwnership(OsippPeriod $contextPeriod, OsippPeriod $period): void
    {
        abort_unless((string) $contextPeriod->id === (string) $period->id, 404);
        $this->authorizePeriod($period);
    }
}
