<?php

namespace App\Services;

use App\Models\AbsensiMurid;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\MuridKelas;
use App\Models\Pertemuan;
use Illuminate\Support\Facades\DB;

class KelasService
{
    public function assignPengajar(Kelas $kelas, array $data): KelasGuru
    {
        $exists = KelasGuru::where([
            'kelas_id'     => $kelas->id,
            'pengajar_id'  => $data['pengajar_id'],
            'tahun_ajaran' => $data['tahun_ajaran'],
        ])->exists();

        if ($exists) {
            abort(422, 'Pengajar sudah ditugaskan di kelas ini untuk tahun ajaran tersebut.');
        }

        return KelasGuru::create([
            'kelas_id'     => $kelas->id,
            'pengajar_id'  => $data['pengajar_id'],
            'peran'        => $data['peran'],
            'tahun_ajaran' => $data['tahun_ajaran'],
        ]);
    }

    public function enrollMurid(Kelas $kelas, array $data): MuridKelas
    {
        return DB::transaction(function () use ($kelas, $data) {
            $alreadyEnrolled = MuridKelas::where('murid_id', $data['murid_id'])
                ->where('status', 'aktif')
                ->whereNull('tanggal_keluar')
                ->exists();

            if ($alreadyEnrolled) {
                abort(422, 'Murid sudah terdaftar aktif di kelas lain. Keluarkan murid dari kelas sebelumnya terlebih dahulu.');
            }

            $muridKelas = MuridKelas::create([
                'murid_id'      => $data['murid_id'],
                'kelas_id'      => $kelas->id,
                'tahun_ajaran'  => $data['tahun_ajaran'],
                'tanggal_masuk' => $data['tanggal_masuk'] ?? now()->toDateString(),
                'status'        => 'aktif',
            ]);

            $this->syncAbsensiSesiBerlangsung($kelas->id);

            return $muridKelas;
        });
    }

    private function syncAbsensiSesiBerlangsung(int $kelasId): void
    {
        $pertemuanIds = Pertemuan::untukKelas($kelasId)
            ->berlangsung()
            ->pluck('id');

        if ($pertemuanIds->isEmpty()) {
            return;
        }

        $muridIds = MuridKelas::where('kelas_id', $kelasId)
            ->aktif()
            ->pluck('murid_id');

        $now = now();

        foreach ($pertemuanIds as $pertemuanId) {
            $muridBelumTercatat = $muridIds->diff(
                AbsensiMurid::where('pertemuan_id', $pertemuanId)->pluck('murid_id')
            );

            if ($muridBelumTercatat->isEmpty()) {
                continue;
            }

            AbsensiMurid::insert($muridBelumTercatat->map(fn ($muridId) => [
                'pertemuan_id' => $pertemuanId,
                'murid_id'     => $muridId,
                'status'       => 'alpha',
                'dicatat_oleh' => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ])->values()->all());
        }
    }

    public function naikKelas(Kelas $asal, Kelas $tujuan, array $muridIds): void
    {
        DB::transaction(function () use ($asal, $tujuan, $muridIds) {
            foreach ($muridIds as $muridId) {
                $mk = MuridKelas::where('murid_id', $muridId)
                    ->where('kelas_id', $asal->id)
                    ->where('status', 'aktif')
                    ->whereNull('tanggal_keluar')
                    ->first();

                if (!$mk) {
                    continue;
                }

                $mk->update([
                    'status'         => 'naik_kelas',
                    'tanggal_keluar' => now()->toDateString(),
                ]);

                MuridKelas::create([
                    'murid_id'      => $muridId,
                    'kelas_id'      => $tujuan->id,
                    'tahun_ajaran'  => $mk->tahun_ajaran,
                    'tanggal_masuk' => now()->toDateString(),
                    'status'        => 'aktif',
                ]);
            }

            $this->syncAbsensiSesiBerlangsung($tujuan->id);
        });
    }
}
