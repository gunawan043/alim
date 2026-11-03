<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityPost;
use App\Models\Security\SecurityShift;
use App\Models\Security\SecurityTeam;
use App\Models\Security\SecurityTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SecurityTeamController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $teams = SecurityTeam::query()
            ->with(['post', 'shift', 'leader', 'activeMembers.user'])
            ->withCount('members')
            ->orderBy('name')
            ->get();

        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();
        $shifts = SecurityShift::query()->where('is_active', true)->orderBy('start_time')->get();

        $members = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereIn('id', function ($sub) {
                    $sub->select('model_id')
                        ->from('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('roles.name', 'Satuan Keamanan');
                })
                    ->orWhereIn('id', function ($sub) {
                        $sub->select('user_id')
                            ->from('gtk_employments')
                            ->where('jabatan', 'like', '%Keamanan%');
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('security.teams.index', compact('userId', 'teams', 'posts', 'shifts', 'members'));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_teams,code'],
            'name' => ['required', 'string', 'max:100'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:security_shifts,id'],
            'leader_id' => ['nullable', 'uuid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.unique' => 'Kode regu sudah dipakai.',
        ]);

        SecurityTeam::create($data + ['is_active' => true]);

        return back()->with('success', 'Regu ditambahkan.');
    }

    public function update(Request $request, string $userId, string $team)
    {
        $team = SecurityTeam::query()->findOrFail($team);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('security_teams', 'code')->ignore($team->id)],
            'name' => ['required', 'string', 'max:100'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:security_shifts,id'],
            'leader_id' => ['nullable', 'uuid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $team->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Regu diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $team)
    {
        $team = SecurityTeam::query()->findOrFail($team);
        $team->delete();

        return back()->with('success', 'Regu dihapus.');
    }

    public function storeMember(Request $request, string $userId, string $team)
    {
        $team = SecurityTeam::query()->findOrFail($team);

        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'position' => ['required', 'in:komandan,anggota'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'user_id.required' => 'Pilih petugas yang akan dimasukkan ke regu.',
        ]);

        $exists = SecurityTeamMember::query()
            ->where('team_id', $team->id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Petugas tersebut sudah tergabung di regu ini.');
        }

        SecurityTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $data['user_id'],
            'position' => $data['position'],
            'start_date' => $data['start_date'] ?? today()->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Anggota regu ditambahkan.');
    }

    public function destroyMember(Request $request, string $userId, string $team, string $member)
    {
        $member = SecurityTeamMember::query()
            ->where('team_id', $team)
            ->findOrFail($member);

        $member->delete();

        return back()->with('success', 'Anggota dikeluarkan dari regu.');
    }
}
