<?php

namespace App\Http\Controllers;

use App\Models\WaLog;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaLogController extends Controller
{
    public function __construct(private WaServiceInterface $wa) {}

    public function index(Request $request): JsonResponse
    {
        $query = WaLog::latest();

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        return response()->json($query->paginate(20));
    }

    public function retry(WaLog $waLog): JsonResponse
    {
        if ($waLog->status !== 'gagal') {
            return response()->json(['message' => 'Hanya log dengan status gagal yang bisa di-retry.'], 422);
        }

        $result = $this->wa->kirim($waLog->nomor_tujuan, $waLog->pesan);

        $waLog->update([
            'status'        => $result->berhasil ? 'terkirim' : 'gagal',
            'error_message' => $result->errorMessage,
        ]);

        return response()->json(['wa_log' => $waLog->fresh()]);
    }
}
