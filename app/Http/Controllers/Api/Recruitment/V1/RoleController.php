<?php

namespace App\Http\Controllers\Api\Recruitment\V1;

use App\Http\Controllers\Api\Shared\IntegrationController;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar role (Spatie) untuk kebutuhan pemetaan interviewer/penilai.
 *
 * GET /api/recruitment/v1/role
 */
class RoleController extends IntegrationController
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::query()->withCount('users');

        if (($search = $this->searchTerm($request)) !== null) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query
            ->orderBy('name')
            ->paginate($this->perPage($request, 100))
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'nama' => $role->name,
                'guard' => $role->guard_name,
                'level' => $role->level,
                'deskripsi' => $role->description,
                'jumlah_pengguna' => (int) ($role->users_count ?? 0),
            ]);

        return $this->paginated($paginator, null, 'Daftar role berhasil diambil.');
    }
}
