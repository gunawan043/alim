<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WakaController extends Controller
{
    /**
     * Dashboard utama Waka / Admin TU
     */
    public function dashboard()
    {
        return view('waka.dashboard');
    }
}
