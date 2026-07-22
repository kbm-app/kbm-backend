<?php

namespace App\Services;

use App\Models\AbsensiMurid;
use App\Models\KasTransaksi;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\ProgressMateriMurid;
use App\Models\WaliMurid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MuridService
{
    public function create(array $data): Murid
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['foto']) && $data['foto'] instanceof \Illuminate\Http\UploadedFile) {
                $data['foto'] = $data['foto']->store('murid/foto', 'r2');
            }

            $murid = Murid::create([
                'nama'          => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'alamat'        => $data['alamat'] ?? null,
                'foto'          => $data['foto'] ?? null,
                'tanggal_masuk' => $data['tanggal_masuk'] ?? null,
                'status'        => $data['status'] ?? 'aktif',
            ]);

            if (!empty($data['wali'])) {
                foreach ($data['wali'] as $wali) {
                    $murid->waliMurid()->create($wali);
                }
            }

            return $murid->load('waliMurid');
        });
    }

    public function update(Murid $murid, array $data): Murid
    {
        return DB::transaction(function () use ($murid, $data) {
            if (isset($data['foto']) && $data['foto'] instanceof \Illuminate\Http\UploadedFile) {
                if ($murid->foto) {
                    Storage::disk('r2')->delete($murid->foto);
                }
                $data['foto'] = $data['foto']->store('murid/foto', 'r2');
            }

            $murid->update($data);
            return $murid->fresh('waliMurid');
        });
    }

    public function updateStatus(Murid $murid, string $status): void
    {
        $murid->update(['status' => $status]);
    }

    public function deleteImpact(Murid $murid): array
    {
        return [
            'kelas_aktif'     => $murid->kelasAktif()->with('kelas:id,nama')->get()->pluck('kelas.nama'),
            'riwayat_kelas'   => $murid->muridKelas()->count(),
            'wali_murid'      => $murid->waliMurid()->count(),
            'absensi'         => AbsensiMurid::where('murid_id', $murid->id)->count(),
            'progress_materi' => ProgressMateriMurid::where('murid_id', $murid->id)->count(),
            'transaksi_kas'   => KasTransaksi::where('murid_id', $murid->id)->count(),
        ];
    }

    public function delete(Murid $murid): void
    {
        DB::transaction(function () use ($murid) {
            KasTransaksi::where('murid_id', $murid->id)->delete();
            ProgressMateriMurid::where('murid_id', $murid->id)->delete();
            AbsensiMurid::where('murid_id', $murid->id)->delete();
            MuridKelas::where('murid_id', $murid->id)->delete();
            WaliMurid::where('murid_id', $murid->id)->delete();

            if ($murid->foto) {
                Storage::disk('r2')->delete($murid->foto);
            }

            $murid->delete();
        });
    }
}
