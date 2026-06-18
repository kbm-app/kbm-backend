<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kurikulum\ReorderRequest;
use App\Http\Requests\Kurikulum\StoreMateriRequest;
use App\Http\Requests\Kurikulum\UpdateMateriRequest;
use App\Models\BabKurikulum;
use App\Models\Kurikulum;
use App\Models\Materi;
use App\Services\KurikulumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriController extends Controller
{
    public function __construct(private KurikulumService $service) {}

    public function index(BabKurikulum $babKurikulum): JsonResponse
    {
        $this->authorize('view', $babKurikulum->kurikulum);

        return response()->json([
            'data' => $babKurikulum->materi()->get(),
        ]);
    }

    public function store(StoreMateriRequest $request, BabKurikulum $babKurikulum): JsonResponse
    {
        $this->authorize('manageMateri', $babKurikulum->kurikulum);

        $data = array_merge($request->validated(), [
            'kurikulum_id' => $babKurikulum->kurikulum_id,
        ]);

        if (!isset($data['urutan'])) {
            $data['urutan'] = $babKurikulum->materi()->max('urutan') + 1;
        }

        $materi = Materi::create($data);
        return response()->json(['materi' => $materi], 201);
    }

    public function update(UpdateMateriRequest $request, Materi $materi): JsonResponse
    {
        $this->authorize('manageMateri', $materi->kurikulum);

        $materi->update($request->validated());
        return response()->json(['materi' => $materi]);
    }

    public function destroy(Materi $materi): JsonResponse
    {
        $this->authorize('manageMateri', $materi->kurikulum);

        $materi->delete();
        return response()->json(null, 204);
    }

    public function reorder(ReorderRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('manageMateri', $kurikulum);

        DB::transaction(function () use ($request) {
            foreach ($request->validated('items') as $item) {
                Materi::where('id', $item['id'])->update(['urutan' => $item['urutan']]);
            }
        });

        return response()->json(['data' => $kurikulum->materi()->get()]);
    }

    public function selesaikanUmum(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('manageProgress', $materi->kurikulum);

        $request->validate([
            'pertemuan_id' => ['nullable', 'integer', 'exists:pertemuan,id'],
        ]);

        $this->service->selesaikanMateriUmum($materi, $request->pertemuan_id);

        return response()->json([
            'message' => 'Materi berhasil ditandai selesai untuk semua murid aktif.',
        ]);
    }

    public function progressBulan(Kurikulum $kurikulum, string $bulan): JsonResponse
    {
        $this->authorize('view', $kurikulum);

        $bulanValid = ['januari','februari','maret','april','mei','juni',
                       'juli','agustus','september','oktober','november','desember'];

        if (!in_array($bulan, $bulanValid)) {
            return response()->json(['message' => 'Nama bulan tidak valid.'], 422);
        }

        return response()->json($this->service->hitungProgressBulan($kurikulum, $bulan));
    }
}
