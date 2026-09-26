<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kurikulum\ProgressBulkRequest;
use App\Http\Requests\Kurikulum\UpdateProgressRequest;
use App\Models\Kurikulum;
use App\Models\Materi;
use App\Models\Murid;
use App\Models\PenyampaianMateri;
use App\Models\ProgressMateriMurid;
use App\Services\KurikulumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressMateriController extends Controller
{
    public function __construct(private KurikulumService $service) {}

    public function progressKelas(Request $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('view', $kurikulum);

        // Kurikulum bisa dipakai beberapa kelas — ?kelas_id membatasi ke satu kelas
        $kelasId = $request->integer('kelas_id') ?: null;
        abort_if($kelasId && ! $kurikulum->dipakaiKelas($kelasId), 422, 'Kelas tidak memakai kurikulum ini.');

        $muridIds = $kurikulum->muridAktifIds($kelasId);

        $murid = Murid::whereIn('id', $muridIds)->orderBy('nama')->get(['id', 'nama', 'jenis_kelamin']);

        $materi = $kurikulum->materi()->with('bab:id,kode,nama')->get();

        $umum     = $materi->where('tipe', 'umum')->values();
        $individu = $materi->where('tipe', 'individu')->values();

        // Individu: capaian per murid
        $progress = ProgressMateriMurid::whereIn('murid_id', $muridIds)
            ->whereIn('materi_id', $individu->pluck('id'))
            ->get(['id', 'materi_id', 'murid_id', 'status', 'catatan', 'pertemuan_id', 'tanggal_selesai']);

        // Umum: penyampaian per kelas
        $penyampaian = PenyampaianMateri::whereIn('materi_id', $umum->pluck('id'))
            ->whereIn('kelas_id', $kelasId ? [$kelasId] : $kurikulum->kelas()->pluck('kelas.id'))
            ->get(['id', 'materi_id', 'kelas_id', 'pertemuan_id', 'metode', 'tanggal']);

        return response()->json([
            'murid'       => $murid,
            'materi'      => [
                'umum'     => $umum,
                'individu' => $individu,
            ],
            'progress'    => $progress,
            'penyampaian' => $penyampaian,
        ]);
    }

    public function progressMurid(Kurikulum $kurikulum, Murid $murid): JsonResponse
    {
        $this->authorize('manageProgress', $kurikulum);

        $materi = $kurikulum->materi()->with('bab:id,kode,nama')->get();

        $progress = ProgressMateriMurid::where('murid_id', $murid->id)
            ->whereIn('materi_id', $materi->where('tipe', 'individu')->pluck('id'))
            ->get(['id', 'materi_id', 'status', 'catatan', 'pertemuan_id', 'tanggal_selesai']);

        // Materi umum: penyampaian di kelas murid ini (yang memakai kurikulum ini)
        $kelasMuridIds = $murid->kelasAktif()->pluck('kelas_id')->intersect($kurikulum->kelas()->pluck('kelas.id'));
        $penyampaian   = PenyampaianMateri::whereIn('materi_id', $materi->where('tipe', 'umum')->pluck('id'))
            ->whereIn('kelas_id', $kelasMuridIds)
            ->get(['id', 'materi_id', 'kelas_id', 'pertemuan_id', 'metode', 'tanggal']);

        return response()->json([
            'murid'       => $murid,
            'materi'      => $materi,
            'progress'    => $progress,
            'penyampaian' => $penyampaian,
        ]);
    }

    public function update(UpdateProgressRequest $request, ProgressMateriMurid $progressMateri): JsonResponse
    {
        $this->authorize('manageProgress', $progressMateri->materi->kurikulum);
        abort_if($progressMateri->materi->tipe !== 'individu', 422, 'Progress per murid hanya untuk materi individu.');

        $data = $request->validated();
        if ($data['status'] === 'selesai' && !$progressMateri->tanggal_selesai) {
            $data['tanggal_selesai'] = now()->toDateString();
        }

        $progressMateri->update($data);
        return response()->json(['progress' => $progressMateri]);
    }

    public function bulk(ProgressBulkRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('manageProgress', $kurikulum);

        // Materi umum dicatat per kelas lewat penyampaian, bukan per murid
        $materiIds = collect($request->validated('items'))->pluck('materi_id')->unique();
        abort_if(
            Materi::whereIn('id', $materiIds)->where(fn ($q) => $q->where('tipe', '!=', 'individu')->orWhere('kurikulum_id', '!=', $kurikulum->id))->exists(),
            422,
            'Progress per murid hanya untuk materi individu di kurikulum ini.'
        );

        $now = now();
        $rows = collect($request->validated('items'))->map(fn ($item) => [
            'materi_id'       => $item['materi_id'],
            'murid_id'        => $item['murid_id'],
            'pertemuan_id'    => $item['pertemuan_id'] ?? null,
            'status'          => $item['status'],
            'catatan'         => $item['catatan'] ?? null,
            'tanggal_selesai' => $item['status'] === 'selesai' ? $now->toDateString() : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        ProgressMateriMurid::upsert(
            $rows,
            ['materi_id', 'murid_id'],
            ['pertemuan_id', 'status', 'catatan', 'tanggal_selesai', 'updated_at']
        );

        return response()->json(['message' => 'Progress berhasil diperbarui.']);
    }
}
