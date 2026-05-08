<?php

namespace App\Http\Controllers;

use App\Models\User;

class SecureAccessController extends Controller
{
    public function dataPegawai()
    {
        $secure = request('secure_access');

        // TOKEN SEKALI PAKAI
        $secure->update(['used_at' => now()]);

        return view('pegawai.index', [
            'data' => User::with('gtkProfile')->get()
        ]);
    }
}

