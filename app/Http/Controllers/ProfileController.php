<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function myProfile(Request $request, string $userId): View
    {
        $gtk = User::with([
            'gtkProfile.addresses.province',
            'gtkProfile.addresses.city',
            'gtkProfile.addresses.district',
            'gtkProfile.addresses.village',
            'gtkProfile.familyMembers',
            'employment',
            'educations',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'gtkHealthData',
        ])->findOrFail($userId);

        return view('profile.my', compact('gtk', 'userId'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function editMyProfile(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function updateMyProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Determine the correct redirect based on which route was hit.
        $currentRoute = $request->route()->getName();
        $redirectRoute = $currentRoute === 'profile.update' ? 'profile.edit' : 'user.profile.my';
        $redirectParams = $currentRoute === 'profile.update' ? [] : ['userId' => $user->id];

        return Redirect::route($redirectRoute, $redirectParams)->with('status', 'profile-updated');
    }

    /**
     * Upload a new avatar photo.
     */
    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $user = $request->user();

        if ($request->file('avatar')) {
            if ($user->avatar && $user->avatar !== 'default-avatar.jpg' && Storage::disk('public')->exists('avatars/'.$user->avatar)) {
                Storage::disk('public')->delete('avatars/'.$user->avatar);
            }

            $fileName = time().'_'.uniqid().'.'.$request->file('avatar')->extension();
            $request->file('avatar')->storeAs('avatars', $fileName, 'public');
            $user->avatar = $fileName;
            $user->save();
        }

        return Redirect::route('user.profile.my', ['userId' => $user->id])
            ->with('status', 'photo-uploaded');
    }

    /**
     * Delete the user's avatar photo.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar && $user->avatar !== 'default-avatar.jpg' && Storage::disk('public')->exists('avatars/'.$user->avatar)) {
            Storage::disk('public')->delete('avatars/'.$user->avatar);
        }

        $user->avatar = 'default-avatar.jpg';
        $user->save();

        return Redirect::route('user.profile.my', ['userId' => $user->id])
            ->with('status', 'photo-deleted');
    }

    /**
     * Download the user's CV document.
     */
    public function downloadCv(Request $request, string $uuid): RedirectResponse
    {
        $user = $request->user();

        if ((string) $user->id !== (string) $uuid && (! method_exists($user, 'isSystemAdmin') || ! $user->isSystemAdmin())) {
            abort(403, 'No tienes acceso a este recurso.');
        }

        // TODO: Implement CV generation once a CV model and storage are in place.
        return back()->with('error', 'El CV aún no está disponible para este usuario.');
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
