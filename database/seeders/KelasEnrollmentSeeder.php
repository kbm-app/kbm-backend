<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\Pengajar;
use App\Models\User;
use Illuminate\Database\Seeder;

class KelasEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $ta = '2025/2026';

        $this->assignPengajar($ta);
        $this->enrollMurid($ta);
    }

    private function assignPengajar(string $ta): void
    {
        $assignments = [
            // Ahmad Fauzi → utama di Tahfidz Dasar & Menengah
            [
                'pengajar_email' => 'ahmad.fauzi@kbm.id',
                'kelas_nama'     => 'Kelas Tahfidz Dasar',
                'peran'          => 'utama',
            ],
            [
                'pengajar_email' => 'ahmad.fauzi@kbm.id',
                'kelas_nama'     => 'Kelas Tahfidz Menengah',
                'peran'          => 'asisten',
            ],
            // Siti Aminah → utama di Pra-Tahfidz A & B
            [
                'pengajar_email' => 'siti.aminah@kbm.id',
                'kelas_nama'     => 'Kelas Pra-Tahfidz A',
                'peran'          => 'utama',
            ],
            [
                'pengajar_email' => 'siti.aminah@kbm.id',
                'kelas_nama'     => 'Kelas Pra-Tahfidz B',
                'peran'          => 'utama',
            ],
            [
                'pengajar_email' => 'siti.aminah@kbm.id',
                'kelas_nama'     => 'Kelas Tahfidz Dasar',
                'peran'          => 'asisten',
            ],
        ];

        foreach ($assignments as $item) {
            $user = User::where('email', $item['pengajar_email'])->first();
            if (! $user) {
                continue;
            }

            $pengajar = Pengajar::where('user_id', $user->id)->first();
            $kelas    = Kelas::where('nama', $item['kelas_nama'])->first();

            if (! $pengajar || ! $kelas) {
                continue;
            }

            KelasGuru::firstOrCreate(
                [
                    'kelas_id'    => $kelas->id,
                    'pengajar_id' => $pengajar->id,
                    'tahun_ajaran' => $ta,
                ],
                ['peran' => $item['peran']]
            );
        }
    }

    private function enrollMurid(string $ta): void
    {
        $enrollments = [
            ['murid_nama' => 'Budi Santoso',     'kelas_nama' => 'Kelas Tahfidz Dasar',    'status' => 'aktif',  'tanggal_masuk' => '2025-07-01', 'tanggal_keluar' => null],
            ['murid_nama' => 'Dewi Rahayu',      'kelas_nama' => 'Kelas Pra-Tahfidz B',    'status' => 'aktif',  'tanggal_masuk' => '2025-07-01', 'tanggal_keluar' => null],
            ['murid_nama' => 'Fajar Nugroho',    'kelas_nama' => 'Kelas Tahfidz Menengah', 'status' => 'aktif',  'tanggal_masuk' => '2025-07-01', 'tanggal_keluar' => null],
            ['murid_nama' => 'Aisyah Putri',     'kelas_nama' => 'Kelas Pra-Tahfidz A',    'status' => 'aktif',  'tanggal_masuk' => '2025-07-01', 'tanggal_keluar' => null],
            ['murid_nama' => 'Nadia Sari',       'kelas_nama' => 'Kelas Pra-Tahfidz A',    'status' => 'aktif',  'tanggal_masuk' => '2025-07-01', 'tanggal_keluar' => null],
            // Rizky lulus — enrollment lama sudah tutup
            ['murid_nama' => 'Rizky Firmansyah', 'kelas_nama' => 'Kelas Reguler 2023',      'status' => 'lulus', 'tanggal_masuk' => '2023-07-01', 'tanggal_keluar' => '2024-06-30'],
        ];

        foreach ($enrollments as $item) {
            $murid = Murid::where('nama', $item['murid_nama'])->first();
            $kelas = Kelas::where('nama', $item['kelas_nama'])->first();

            if (! $murid || ! $kelas) {
                continue;
            }

            MuridKelas::firstOrCreate(
                ['murid_id' => $murid->id, 'kelas_id' => $kelas->id, 'tahun_ajaran' => $item['status'] === 'lulus' ? '2023/2024' : $ta],
                [
                    'tanggal_masuk'  => $item['tanggal_masuk'],
                    'tanggal_keluar' => $item['tanggal_keluar'],
                    'status'         => $item['status'],
                ]
            );
        }
    }
}
