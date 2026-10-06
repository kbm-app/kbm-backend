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

    /**
     * Catat penyampaian materi umum di satu kelas. Kirim `metode` untuk mengisi/mengubah
     * cara penyampaiannya (mis. "Nasehat & Praktek").
     */
    public function selesaikanUmum(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('manageProgress', $materi->kurikulum);

        $request->validate([
            'pertemuan_id' => ['nullable', 'integer', 'exists:pertemuan,id'],
            'kelas_id'     => ['nullable', 'integer'],
            'metode'       => ['nullable', 'string', 'max:100'],
        ]);

        $penyampaian = $this->service->catatPenyampaian(
            $materi,
            $request->integer('pertemuan_id') ?: null,
            $request->integer('kelas_id') ?: null,
            $request->user()->id,
            $request->has('metode'),
            $request->input('metode'),
        );

        return response()->json([
            'message'     => 'Materi ditandai sudah disampaikan.',
            'penyampaian' => $penyampaian,
        ]);
    }

    /**
     * Batalkan penyampaian materi umum yang tercatat di satu sesi (koreksi). Penyampaian yang
     * tercatat di sesi lain atau tanpa sesi tidak tersentuh.
     */
    public function batalkanUmum(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('manageProgress', $materi->kurikulum);

        $data = $request->validate([
            'pertemuan_id' => ['required', 'integer', 'exists:pertemuan,id'],
        ]);

        $this->service->batalkanPenyampaian($materi, (int) $data['pertemuan_id']);

        return response()->json(null, 204);
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
