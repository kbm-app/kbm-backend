<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsistenAi\EkstrakAbsensiRequest;
use App\Http\Requests\AsistenAi\SimpanAbsensiRequest;
use App\Models\Kelas;
use App\Services\Ai\AsistenAbsensiService;
use Illuminate\Http\JsonResponse;

/** Asisten Input AI (super admin) — lihat AsistenAbsensiService. */
class AsistenAiController extends Controller
{
    public function __construct(private AsistenAbsensiService $service) {}

    /** Baca laporan (teks/foto) menjadi draf absensi. Tidak menyimpan apa pun. */
    public function ekstrakAbsensi(EkstrakAbsensiRequest $request): JsonResponse
    {
        // Membaca foto tulisan tangan bisa memakan waktu lebih dari batas default PHP
        set_time_limit(180);

        $hasil = $this->service->ekstrak(
            Kelas::findOrFail($request->validated('kelas_id')),
            $request->validated('teks'),
            $request->file('gambar', []),
            $request->validated('provider'),
            $request->validated('model'),
        );

        return response()->json($hasil);
    }

    /** Simpan draf yang sudah diperiksa admin menjadi sesi + absensi. */
    public function simpanAbsensi(SimpanAbsensiRequest $request): JsonResponse
    {
        $pertemuan = $this->service->simpan($request->validated(), $request->user()->id);

        return response()->json(['pertemuan' => $pertemuan], 201);
    }
}
