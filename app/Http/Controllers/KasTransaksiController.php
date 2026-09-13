<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KasTransaksi;
use App\Services\KasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KasTransaksiController extends Controller
{
    public function __construct(private KasService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'kelas_id'    => 'required|exists:kelas,id',
            'kategori_id' => 'nullable|exists:kas_kategori,id',
            'jenis'       => 'nullable|in:pemasukan,pengeluaran',
            'bulan'       => 'nullable|integer|between:1,12',
            'tahun'       => 'nullable|integer',
        ]);

        $kelas = Kelas::findOrFail($request->kelas_id);
        $this->authorize('viewKasTransaksi', $kelas);

        $query = KasTransaksi::with(['kategori', 'murid'])
            ->where('kelas_id', $kelas->id)
            ->when($request->kategori_id, fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->when($request->jenis, fn ($q) => $q->whereHas('kategori', fn ($k) => $k->where('jenis', $request->jenis)))
            ->when($request->bulan, fn ($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->tahun, fn ($q) => $q->whereYear('tanggal', $request->tahun));

        return response()->json([
            'data' => $query->orderByDesc('tanggal')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kelas_id'    => 'required|exists:kelas,id',
            'kategori_id' => 'required|exists:kas_kategori,id',
            'murid_id'    => 'nullable|exists:murid,id',
            'jumlah'      => 'required|numeric|min:1',
            'keterangan'  => 'nullable|string|max:500',
            'tanggal'     => 'required|date',
        ]);

        $kelas = Kelas::findOrFail($data['kelas_id']);
        $this->authorize('catatKasTransaksi', $kelas);

        $data['dicatat_oleh'] = $request->user()->id;

        $transaksi = KasTransaksi::create($data);
        $transaksi->load(['kategori', 'murid']);

        return response()->json(['transaksi' => $transaksi], 201);
    }

    public function update(Request $request, KasTransaksi $kasTransaksi): JsonResponse
    {
        $this->authorize('update', $kasTransaksi);

        $data = $request->validate([
            'kategori_id' => 'sometimes|exists:kas_kategori,id',
            'murid_id'    => 'nullable|exists:murid,id',
            'jumlah'      => 'sometimes|numeric|min:1',
            'keterangan'  => 'nullable|string|max:500',
            'tanggal'     => 'sometimes|date',
        ]);

        $kasTransaksi->update($data);
        $kasTransaksi->load(['kategori', 'murid']);

        return response()->json(['transaksi' => $kasTransaksi]);
    }

    public function destroy(KasTransaksi $kasTransaksi): JsonResponse
    {
        $this->authorize('delete', $kasTransaksi);

        $kasTransaksi->delete();
        return response()->json(null, 204);
    }

    public function rekap(Request $request): JsonResponse
    {
        $user  = $request->user();
        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;

        $query = Kelas::where('is_aktif', true);

        if ($user->role->value === 'pengajar') {
            $query->whereHas('kelasGuru', fn ($q) =>
                $q->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
            );
        }

        $kelasList = $query->orderBy('nama')->get();

        $saldoPerKelas = $this->service->hitungSaldoBanyakKelas($kelasList->pluck('id'), $bulan, $tahun);

        $data = $kelasList->map(fn ($kelas) => [
            'kelas' => ['id' => $kelas->id, 'nama' => $kelas->nama],
            ...$saldoPerKelas[$kelas->id],
        ]);

        return response()->json(['data' => $data, 'bulan' => $bulan, 'tahun' => $tahun]);
    }

    public function rekapKelas(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('viewKasTransaksi', $kelas);

        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;

        return response()->json([
            'kelas' => ['id' => $kelas->id, 'nama' => $kelas->nama],
            ...$this->service->hitungSaldo($kelas->id, $bulan, $tahun),
        ]);
    }
}
