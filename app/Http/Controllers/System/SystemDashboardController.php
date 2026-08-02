<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'users_total' => DB::table('users')->count(),
            'users_active' => DB::table('users')->where('is_active', true)->count(),
            'system_admins' => DB::table('users')->where('is_system_admin', true)->count(),
            'roles_total' => DB::table('roles')->count(),
            'permissions_total' => DB::table('permissions')->count(),
            'schools_total' => DB::table('schools')->count(),
            'dormitories_total' => DB::table('dormitories')->count(),
        ];

        return view('system.dashboard', compact('stats'));
    }

    public function features()
    {
        return view('system.features');
    }

    public function monitoring()
    {
        return view('system.monitoring');
    }

    public function maintenance()
    {
        return view('system.maintenance');
    }

    public function config()
    {
        return view('system.config');
    }

    public function devtools()
    {
        return view('system.devtools');
    }
}
