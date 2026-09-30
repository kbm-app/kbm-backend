<?php

namespace App\Services;

use App\Models\AbsensiMurid;
use App\Models\KasTransaksi;
use App\Models\KelasPengurus;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\ProgressMateriMurid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
                'tempat_lahir'  => $data['tempat_lahir'] ?? null,
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

    /**
     * Absensi murid yang jatuh sebelum $tanggalMasuk — yang akan dihapus bila tanggal bergabung
     * diubah ke tanggal tsb (mis. murid ternyata baru bergabung di tengah bulan).
     */
    public function dampakTanggalMasuk(Murid $murid, string $tanggalMasuk): array
    {
        $absensi = AbsensiMurid::where('murid_id', $murid->id)
            ->whereHas('pertemuan', fn ($q) => $q->where('tanggal', '<', $tanggalMasuk))
            ->with('pertemuan:id,tanggal,kelas_id', 'pertemuan.kelas:id,nama')
            ->get();

        $tanggal = $absensi->map(fn ($a) => $a->pertemuan->tanggal->toDateString())->sort()->values();

        return [
            'jumlah'     => $absensi->count(),
            'dari'       => $tanggal->first(),
            'sampai'     => $tanggal->last(),
            'per_status' => $absensi->countBy('status'),
            'kelas'      => $absensi->pluck('pertemuan.kelas.nama')->unique()->values(),
        ];
    }

    public function update(Murid $murid, array $data): Murid
    {
        $hapusAbsensi = (bool) ($data['hapus_absensi_sebelum_masuk'] ?? false);
        unset($data['hapus_absensi_sebelum_masuk']);

        // Tanggal bergabung dimundurkan melewati absensi yang sudah ada → wajib konfirmasi hapus
        $tanggalBaru = $data['tanggal_masuk'] ?? null;
        $berubah     = $tanggalBaru && $tanggalBaru !== $murid->tanggal_masuk?->toDateString();
        $dampak      = $berubah ? $this->dampakTanggalMasuk($murid, $tanggalBaru) : ['jumlah' => 0];
        if ($dampak['jumlah'] > 0 && ! $hapusAbsensi) {
            throw ValidationException::withMessages([
                'tanggal_masuk' => "Ada {$dampak['jumlah']} absensi ({$dampak['dari']} s/d {$dampak['sampai']}) sebelum tanggal bergabung ini. Konfirmasi penghapusan absensi tersebut terlebih dahulu.",
            ]);
        }

        return DB::transaction(function () use ($murid, $data, $dampak, $tanggalBaru) {
            if ($dampak['jumlah'] > 0) {
                AbsensiMurid::where('murid_id', $murid->id)
                    ->whereHas('pertemuan', fn ($q) => $q->where('tanggal', '<', $tanggalBaru))
                    ->delete();

                // Tanggal masuk kelas aktif ikut disesuaikan agar tidak lebih awal dari tanggal bergabung
                MuridKelas::where('murid_id', $murid->id)
                    ->whereNull('tanggal_keluar')
                    ->where('tanggal_masuk', '<', $tanggalBaru)
                    ->update(['tanggal_masuk' => $tanggalBaru]);
            }

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

    /**
     * Soft delete murid. Riwayat absensi, progress materi, kas, wali & foto tetap tersimpan;
     * murid dikeluarkan dari kelas aktif (tanggal_keluar = hari ini) dan dari jabatan pengurus.
     */
    public function delete(Murid $murid): void
    {
        DB::transaction(function () use ($murid) {
            MuridKelas::where('murid_id', $murid->id)
                ->whereNull('tanggal_keluar')
                ->update(['tanggal_keluar' => now()->toDateString()]);
            KelasPengurus::where('murid_id', $murid->id)->delete();

            $murid->delete();
        });
    }
}
