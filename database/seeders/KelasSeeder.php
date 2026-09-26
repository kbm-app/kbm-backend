<?php

namespace Database\Seeders;

use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $kelas = [
            [
                'nama'             => 'PAUD',
                'deskripsi'        => 'Kelas untuk anak usia pra-sekolah (PAUD)',
                'rentang_usia_min' => 4,
                'rentang_usia_max' => 6,
                'kapasitas'        => 12,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 1',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 1',
                'rentang_usia_min' => 6,
                'rentang_usia_max' => 7,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 2',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 2',
                'rentang_usia_min' => 7,
                'rentang_usia_max' => 8,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 3-1',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 3',
                'rentang_usia_min' => 8,
                'rentang_usia_max' => 9,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 3-2',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 3',
                'rentang_usia_min' => 8,
                'rentang_usia_max' => 9,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 4',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 4',
                'rentang_usia_min' => 9,
                'rentang_usia_max' => 10,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 5',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 5',
                'rentang_usia_min' => 10,
                'rentang_usia_max' => 11,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas 6',
                'deskripsi'        => 'Kelas untuk usia setara SD kelas 6',
                'rentang_usia_min' => 11,
                'rentang_usia_max' => 12,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Pra-Remaja',
                'deskripsi'        => 'Kelas untuk usia setara SMP kelas 1-3',
                'rentang_usia_min' => 12,
                'rentang_usia_max' => 15,
                'kapasitas'        => 20,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Remaja',
                'deskripsi'        => 'Kelas untuk usia setara SMA kelas 1-3',
                'rentang_usia_min' => 15,
                'rentang_usia_max' => 18,
                'kapasitas'        => 20,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Usman',
                'deskripsi'        => 'Usia Mandiri — untuk usia setelah lulus SMA sampai dengan belum menikah',
                'rentang_usia_min' => 18,
                'rentang_usia_max' => null,
                'kapasitas'        => null,
                'is_aktif'         => true,
            ],
        ];

        foreach ($kelas as $item) {
            Kelas::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
