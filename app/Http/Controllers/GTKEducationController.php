<?php

namespace App\Http\Controllers;

use App\Models\GtkEducation;
use Illuminate\Http\Request;

class GTKEducationController extends Controller
{
    public function store(Request $request, string $userId, string $uuid)
    {
        $request->validate([
            'user_id' => 'required',
            'jenjang_pendidikan' => 'required',
            'nama_satuan_pendidikan' => 'required',
            'tahun_lulus' => 'required|numeric',
            'status' => 'required',
        ]);

        $education = GtkEducation::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $education,
        ]);
    }

    public function show(string $userId, string $uuid, string $id)
    {
        return response()->json(GtkEducation::findOrFail($id));
    }

    public function update(Request $request, string $userId, string $uuid, string $id)
    {
        $education = GtkEducation::findOrFail($id);
        $education->update($request->all());

        return response()->json(['success' => true]);
    }

    public function destroy(string $userId, string $uuid, string $id)
    {
        GtkEducation::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
