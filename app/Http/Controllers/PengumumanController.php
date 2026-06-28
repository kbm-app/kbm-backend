<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Services\PengumumanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengumumanController extends Controller
{
    public function __construct(private PengumumanService $service) {}

    public function index(Request $request): JsonResponse
    {
        $pengumuman = Pengumuman::with(['pembuat:id,name', 'kelas:id,nama'])
            ->latest()
            ->paginate(15);

        return response()->json($pengumuman);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'judul'    => 'required|string|min:3|max:200',
            'konten'   => 'required|string|min:10',
            'target'   => 'required|in:semua,murid,wali_murid,pengajar,kelas_tertentu',
            'kelas_id' => 'required_if:target,kelas_tertentu|nullable|exists:kelas,id',
        ]);

        $pengumuman = Pengumuman::create([
            ...$data,
            'dibuat_oleh' => Auth::id(),
        ]);

        $this->service->kirim($pengumuman);

        $pengumuman->load(['pembuat:id,name', 'kelas:id,nama']);

        return response()->json(['pengumuman' => $pengumuman], 201);
    }

    public function show(Pengumuman $pengumuman): JsonResponse
    {
        $pengumuman->load(['pembuat:id,name', 'kelas:id,nama', 'waLogs']);

        $stats = [
            'terkirim' => $pengumuman->waLogs->where('status', 'terkirim')->count(),
            'gagal'    => $pengumuman->waLogs->where('status', 'gagal')->count(),
        ];

        return response()->json([
            'pengumuman' => $pengumuman,
            'stats'      => $stats,
        ]);
    }
}
