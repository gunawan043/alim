<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalisisGtkController extends Controller
{
    /**
     * Analisis Beban Kerja GTK
     */
    public function bebanKerja(Request $request, string $userId)
    {
        return view('personalia.analisis-gtk.beban-kerja', compact('userId'));
    }

    /**
     * Rasio Ideal GTK
     */
    public function rasioIdeal(Request $request, string $userId)
    {
        return view('personalia.analisis-gtk.rasio-ideal', compact('userId'));
    }

    /**
     * Proyeksi SDM GTK
     */
    public function proyeksi(Request $request, string $userId)
    {
        return view('personalia.analisis-gtk.proyeksi', compact('userId'));
    }

    /**
     * Gap Analysis GTK
     */
    public function gap(Request $request, string $userId)
    {
        return view('personalia.analisis-gtk.gap', compact('userId'));
    }
}