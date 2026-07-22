<?php

namespace App\Http\Controllers;

use App\Http\Requests\Murid\StoreMuridRequest;
use App\Http\Requests\Murid\UpdateMuridRequest;
use App\Models\Murid;
use App\Services\MuridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MuridController extends Controller
{
    public function __construct(private MuridService $muridService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Murid::with(['waliMurid', 'kelasAktif.kelas'])
            ->when($request->search, fn($q) => $q->where('nama', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->boolean('tanpa_kelas'), fn($q) => $q->whereDoesntHave('kelasAktif'))
            ->when($request->kelas_id, fn($q) => $q->whereHas('kelasAktif', fn($k) => $k->where('kelas_id', $request->kelas_id)))
            ->when($request->usia_min, fn($q) => $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= ?", [$request->usia_min]))
            ->when($request->usia_max, fn($q) => $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) <= ?", [$request->usia_max]));

        return response()->json($query->paginate(15));
    }

    public function store(StoreMuridRequest $request): JsonResponse
    {
        $murid = $this->muridService->create($request->validated());
        return response()->json(['murid' => $murid], 201);
    }

    public function show(Murid $murid): JsonResponse
    {
        return response()->json(['murid' => $murid->load('waliMurid', 'user')]);
    }

    public function update(UpdateMuridRequest $request, Murid $murid): JsonResponse
    {
        $murid = $this->muridService->update($murid, $request->validated());
        return response()->json(['murid' => $murid]);
    }

    public function deleteImpact(Murid $murid): JsonResponse
    {
        $this->authorize('delete', $murid);
        return response()->json($this->muridService->deleteImpact($murid));
    }

    public function destroy(Murid $murid): JsonResponse
    {
        $this->authorize('delete', $murid);
        $this->muridService->delete($murid);
        return response()->json(null, 204);
    }
}
