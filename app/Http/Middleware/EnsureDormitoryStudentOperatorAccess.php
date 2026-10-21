<?php

namespace App\Http\Middleware;

use App\Models\StudentOperatorAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDormitoryStudentOperatorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('Operator Asrama Santri')) {
            return $next($request);
        }

        $route = $request->route()?->getName() ?? '';
        if (! str_starts_with($route, 'user.asrama.')) {
            return $next($request);
        }

        $allowed = [
            'user.asrama.visits.index',
            'user.asrama.visits.create',
            'user.asrama.visits.store',
            'user.asrama.visits.show',
            'user.asrama.visits.card',
            'user.asrama.visits.check-in',
        ];

        if (! in_array($route, $allowed, true)) {
            abort(403, 'Menu ini tidak tersedia untuk operator asrama santri.');
        }

        $asramaUuid = $request->route('asramaUuid');
        if ($asramaUuid) {
            $allowedDormitory = StudentOperatorAssignment::query()
                ->where('user_id', $user->id)
                ->where('operator_type', 'asrama')
                ->where('dormitory_id', $asramaUuid)
                ->where('is_active', true)
                ->exists();

            abort_unless($allowedDormitory, 403, 'Anda tidak ditugaskan pada asrama ini.');
        }

        return $next($request);
    }
}
