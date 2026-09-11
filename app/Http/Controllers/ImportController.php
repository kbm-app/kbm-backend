<?php

namespace App\Http\Controllers;

use App\Imports\MuridImport;
use App\Imports\PengajarImport;
use App\Models\Murid;
use App\Models\Pengajar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function murid(Request $request): JsonResponse
    {
        $this->authorize('create', Murid::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $import = new MuridImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message'    => 'Import selesai',
            'total_rows' => $import->totalRows,
            'success'    => $import->successCount,
            'skipped'    => $import->errorCount,
            'errors'     => collect($import->getFailures())->map(fn ($f) => [
                'row'       => $f->row(),
                'attribute' => $f->attribute(),
                'errors'    => $f->errors(),
            ])->values(),
        ]);
    }

    public function pengajar(Request $request): JsonResponse
    {
        $this->authorize('create', Pengajar::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $import = new PengajarImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message'    => 'Import selesai',
            'total_rows' => $import->totalRows,
            'success'    => $import->successCount,
            'skipped'    => $import->errorCount,
            'errors'     => collect($import->getFailures())->map(fn ($f) => [
                'row'       => $f->row(),
                'attribute' => $f->attribute(),
                'errors'    => $f->errors(),
            ])->values(),
        ]);
    }
}
