<?php

namespace App\Http\Controllers;

class GtkDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Simple static data for demo purposes
        $totalGtk = 15;
        $guruCount = 10;
        $tendikCount = 5;
        $newThisMonth = 2;
        $lakiCount = 8;
        $perempuanCount = 7;
        $recentUsers = [];

        return view('gtk.dashboard', compact(
            'user',
            'totalGtk',
            'guruCount',
            'tendikCount',
            'newThisMonth',
            'lakiCount',
            'perempuanCount',
            'recentUsers'
        ));
    }
}
