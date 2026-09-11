<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaliMurid\StoreWaliMuridRequest;
use App\Http\Requests\WaliMurid\UpdateWaliMuridRequest;
use App\Models\Murid;
use App\Models\WaliMurid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaliMuridController extends Controller
{
    public function index(Murid $murid): JsonResponse
    {
        $this->authorize('view', $murid);

        return response()->json(['wali' => $murid->waliMurid]);
    }

    public function store(StoreWaliMuridRequest $request, Murid $murid): JsonResponse
    {
        $wali = $murid->waliMurid()->create($request->validated());
        return response()->json(['wali' => $wali], 201);
    }

    public function update(UpdateWaliMuridRequest $request, WaliMurid $waliMurid): JsonResponse
    {
        $waliMurid->update($request->validated());
        return response()->json(['wali' => $waliMurid]);
    }

    public function destroy(Request $request, WaliMurid $waliMurid): JsonResponse
    {
        abort_unless($request->user()->role->value === 'super_admin', 403);

        $waliMurid->delete();
        return response()->json(null, 204);
    }
}
