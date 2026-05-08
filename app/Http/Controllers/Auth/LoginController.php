<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLogin()
    {
        Log::info('Show login page', [
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token()
        ]);
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        Log::info('Login attempt started', [
            'email' => $request->email,
            'session_id' => session()->getId(),
            'has_csrf' => $request->has('_token'),
            'csrf_token' => $request->input('_token'),
            'expected_csrf' => csrf_token()
        ]);

        // Validasi sederhana
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        // Debug credentials
        $credentials = $request->only('email', 'password');
        Log::info('Credentials', ['email' => $credentials['email']]);

        // Coba login
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            Log::info('Login successful', ['user_id' => Auth::id()]);

            $request->session()->regenerate();

            // Set active_role_id from user's first role
            $user = Auth::user();
            if ($user && $user->roles->isNotEmpty()) {
                $request->session()->put('active_role_id', $user->roles->first()->id);
            }

            return redirect()->intended('/');
        }

        Log::warning('Login failed', ['email' => $request->email]);
        
        return back()->withErrors([
            'login_failed' => 'Email atau password salah.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}