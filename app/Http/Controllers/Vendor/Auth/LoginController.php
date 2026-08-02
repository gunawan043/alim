<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Http\Controllers\Controller;
use App\Models\VendorPortalUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('vendor.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vendor_code' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $vendor = VendorPortalUser::where('vendor_code', $request->vendor_code)->first();

        if (! $vendor || ! $vendor->id) {
            return back()->withErrors(['vendor_code' => 'Vendor tidak ditemukan.']);
        }

        if (! $vendor->password) {
            return back()->withErrors(['vendor_code' => 'Akun vendor belum diaktifkan. Hubungi admin sekolah.']);
        }

        if (! password_verify($request->password, $vendor->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        Auth::guard('vendor')->login($vendor, $request->filled('remember'));

        $vendor->update(['last_portal_login' => now()]);

        session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
