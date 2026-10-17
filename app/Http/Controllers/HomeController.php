<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index(Request $request)
    {
        if (view()->exists($request->path())) {
            return view($request->path());
        }

        return abort(404);
    }

    public function root()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // System Administrator → dedicated dashboard (may have no Spatie role).
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return redirect()->route('system.dashboard');
        }

        if ($user->hasRole('Super Admin')) {
            return redirect()->route('system.dashboard');
        }

        $roles = $user->getRoleNames();

        // Boarding roles with dedicated dashboards
        // Asrama = single role: Kepala + Admin + Wali (divisi based on jabatan)
        if ($roles->contains('Asrama')) {
            return redirect()->route('user.dashboard.asrama', ['userId' => $user->id]);
        }

        if ($roles->contains('Satuan Pendidikan Pendidikan')) {
            return redirect()->route('dashboard.boarding-education');
        }

        if ($roles->contains('UKS')) {
            return redirect()->route('user.uks.dashboard', ['userId' => $user->id]);
        }

        // Existing dedicated dashboards for other roles
        if ($roles->contains('Keuangan')) {
            return redirect()->route('user.dashboard.keuangan', ['userId' => $user->id]);
        }

        if ($roles->contains('Pimpinan')) {
            return redirect()->route('user.dashboard.pimpinan', ['userId' => $user->id]);
        }

        if ($roles->contains('Departemen Tahfidz')) {
            return redirect()->route('user.dashboard.tahfidz', ['userId' => $user->id]);
        }

        if ($roles->contains('Humas Personalia')) {
            return redirect()->route('user.dashboard.personalia', ['userId' => $user->id]);
        }

        if ($roles->contains('Personalia')) {
            return redirect()->route('user.dashboard.personalia', ['userId' => $user->id]);
        }

        // Unit Rumah Tangga
        if ($roles->contains('Unit Rumah Tangga')) {
            return redirect()->route('user.dashboard.unit-rumah-tangga', ['userId' => $user->id]);
        }

        // Sarpras = single role: Admin + Staf (divisi based on jabatan)
        if ($roles->contains('Sarpras')) {
            return redirect()->route('dashboard');
        }

        if ($roles->contains('Administrator')) {
            return redirect()->route('dashboard.administrator');
        }

        // Role-based dashboard routing (role + jabatan)
        $roleName = strtolower(trim((string) $roles->first()));
        $jabatan = strtoupper(trim((string) ($user->employment?->jabatan ?? '')));

        $roleJabatanMap = [
            'satuan pendidikan' => [
                'WAKIL KEPALA' => 'user.dashboard.wakil-kepala',
                'KEPALA SATUAN PENDIDIKAN' => 'user.dashboard.kepala-satuan-pendidikan',
                'STAF TATA USAHA' => 'user.dashboard.staf-tata-usaha',
            ],
            'pimpinan' => [
                'WAKIL KEPALA' => 'user.dashboard.wakil-kepala',
                'KEPALA SATUAN PENDIDIKAN' => 'user.dashboard.kepala-satuan-pendidikan',
            ],
        ];

        if (isset($roleJabatanMap[$roleName])) {
            foreach ($roleJabatanMap[$roleName] as $jabatanPattern => $route) {
                if (str_contains($jabatan, $jabatanPattern)) {
                    return redirect()->route($route, ['userId' => $user->id]);
                }
            }
        }

        $routeMap = [
            'kepala satuan' => 'user.dashboard.kepala-satuan-pendidikan',
            'wakil kepala' => 'user.dashboard.wakil-kepala',
            'wali kelas' => 'user.dashboard.wali-kelas',
            'koordinator guru' => 'user.dashboard.koordinator-guru',
            'koordinator kurikulum' => 'user.dashboard.koordinator-kurikulum',
            'koordinator kesiswaan' => 'user.dashboard.koordinator-kesiswaan',
            'koordinator ekskul' => 'user.dashboard.koordinator-ekskul',
            'koordinator lab' => 'user.dashboard.koordinator-lab',
            'koordinator sarpras' => 'user.dashboard.koordinator-sarpras',
            'kepala tata usaha' => 'user.dashboard.ka-tata-usaha',
            'staf tata usaha' => 'user.dashboard.staf-tata-usaha',
            'staff tata usaha' => 'user.dashboard.staf-tata-usaha',
            'bendahara' => 'user.dashboard.bendahara',
            'keuangan' => 'user.dashboard.bendahara',
            'guru' => 'user.dashboard.guru',
            'pendidik' => 'user.dashboard.guru',
            'pengasuh' => 'user.dashboard.pengasuh',
            'wali asrama' => 'user.dashboard.wali-asrama',
            'admin tu' => 'user.dashboard.admin-tu',
            'admin asrama' => 'user.dashboard.admin-asrama',
        ];
        foreach ($roles as $rn) {
            $key = strtolower(trim($rn));
            foreach ($routeMap as $substr => $route) {
                if (str_contains($key, $substr)) {
                    return redirect()->route($route, ['userId' => $user->id]);
                }
            }
        }

        // Default → GTK dashboard for any remaining role (Guru/Tendik/GTK)
        return redirect('/dashboard/gtk');
    }

    /* Language Translation */
    public function lang($locale)
    {
        if ($locale) {
            App::setLocale($locale);
            Session::put('lang', $locale);
            Session::save();

            return redirect()->back()->with('locale', $locale);
        } else {
            return redirect()->back();
        }
    }

    public function updateProfile(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $user = User::find($id);
        $user->name = $request->get('name');
        $user->email = $request->get('email');

        if ($request->file('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time().'.'.$avatar->getClientOriginalExtension();
            $avatarPath = public_path('/images/');
            $avatar->move($avatarPath, $avatarName);
            $user->avatar = $avatarName;
        }

        $user->update();
        if ($user) {
            Session::flash('message', 'User Details Updated successfully!');
            Session::flash('alert-class', 'alert-success');

            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "User Details Updated successfully!"
            // ], 200); // Status code here
            return redirect()->back();
        } else {
            Session::flash('message', 'Something went wrong!');
            Session::flash('alert-class', 'alert-danger');

            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "Something went wrong!"
            // ], 200); // Status code here
            return redirect()->back();

        }
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! (Hash::check($request->get('current_password'), Auth::user()->password))) {
            return response()->json([
                'isSuccess' => false,
                'Message' => 'Your Current password does not matches with the password you provided. Please try again.',
            ], 200); // Status code
        } else {
            $user = User::find($id);
            $user->password = Hash::make($request->get('password'));
            $user->update();
            if ($user) {
                Session::flash('message', 'Password updated successfully!');
                Session::flash('alert-class', 'alert-success');

                return response()->json([
                    'isSuccess' => true,
                    'Message' => 'Password updated successfully!',
                ], 200); // Status code here
            } else {
                Session::flash('message', 'Something went wrong!');
                Session::flash('alert-class', 'alert-danger');

                return response()->json([
                    'isSuccess' => true,
                    'Message' => 'Something went wrong!',
                ], 200); // Status code here
            }
        }
    }
}
