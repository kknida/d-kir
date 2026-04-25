<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\SapKclImport;
use Maatwebsite\Excel\Facades\Excel;

class SapImportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new SapKclImport, $request->file('file'));
            return back()->with('success', 'Data SAP berhasil diimport!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }
}