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
                'nama'             => 'Kelas Pra-Tahfidz A',
                'deskripsi'        => 'Kelas persiapan hafalan untuk anak usia 5-7 tahun',
                'rentang_usia_min' => 5,
                'rentang_usia_max' => 7,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Pra-Tahfidz B',
                'deskripsi'        => 'Kelas persiapan hafalan untuk anak usia 8-10 tahun',
                'rentang_usia_min' => 8,
                'rentang_usia_max' => 10,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Tahfidz Dasar',
                'deskripsi'        => 'Kelas hafalan Juz 30 untuk anak usia 10-13 tahun',
                'rentang_usia_min' => 10,
                'rentang_usia_max' => 13,
                'kapasitas'        => 20,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Tahfidz Menengah',
                'deskripsi'        => 'Kelas hafalan Juz 29-28 untuk anak usia 12-15 tahun',
                'rentang_usia_min' => 12,
                'rentang_usia_max' => 15,
                'kapasitas'        => 20,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Tahfidz Lanjutan',
                'deskripsi'        => 'Kelas hafalan lanjutan (Juz 1-10)',
                'rentang_usia_min' => 13,
                'rentang_usia_max' => null,
                'kapasitas'        => 15,
                'is_aktif'         => true,
            ],
            [
                'nama'             => 'Kelas Reguler 2023',
                'deskripsi'        => 'Kelas tahun ajaran 2023/2024 (sudah tidak aktif)',
                'rentang_usia_min' => null,
                'rentang_usia_max' => null,
                'kapasitas'        => null,
                'is_aktif'         => false,
            ],
        ];

        foreach ($kelas as $item) {
            Kelas::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
