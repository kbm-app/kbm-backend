<?php

namespace Database\Seeders;

use App\Models\Murid;
use App\Models\User;
use App\Models\WaliMurid;
use Illuminate\Database\Seeder;

class MuridSeeder extends Seeder
{
    public function run(): void
    {
        $muridData = [
            // Murid dengan akun user
            [
                'email'         => 'budi@kbm.id',
                'nama'          => 'Budi Santoso',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2012-05-10',
                'alamat'        => 'Jl. Kenanga No. 3, Jakarta Selatan',
                'tanggal_masuk' => '2022-07-01',
                'status'        => 'aktif',
                'wali'          => [
                    [
                        'email'      => 'santoso@kbm.id',
                        'nama'       => 'Santoso',
                        'hubungan'   => 'ayah',
                        'phone'      => '081200000020',
                        'pekerjaan'  => 'Wiraswasta',
                        'is_primary' => true,
                    ],
                    [
                        'email'      => null,
                        'nama'       => 'Umi Kalsum',
                        'hubungan'   => 'ibu',
                        'phone'      => '081200000022',
                        'pekerjaan'  => 'Ibu Rumah Tangga',
                        'is_primary' => false,
                    ],
                ],
            ],
            [
                'email'         => 'dewi@kbm.id',
                'nama'          => 'Dewi Rahayu',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2013-09-20',
                'alamat'        => 'Jl. Tulip No. 7, Depok',
                'tanggal_masuk' => '2022-07-01',
                'status'        => 'aktif',
                'wali'          => [
                    [
                        'email'      => 'rahayu@kbm.id',
                        'nama'       => 'Rahayu',
                        'hubungan'   => 'ibu',
                        'phone'      => '081200000021',
                        'pekerjaan'  => 'Guru SD',
                        'is_primary' => true,
                    ],
                ],
            ],

            // Murid tanpa akun user (tidak punya login)
            [
                'email'         => null,
                'nama'          => 'Fajar Nugroho',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2011-02-14',
                'alamat'        => 'Jl. Dahlia No. 1, Tangerang',
                'tanggal_masuk' => '2021-01-10',
                'status'        => 'aktif',
                'wali'          => [
                    [
                        'email'      => null,
                        'nama'       => 'Nugroho',
                        'hubungan'   => 'ayah',
                        'phone'      => '081300000030',
                        'pekerjaan'  => 'Pegawai Negeri',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'email'         => null,
                'nama'          => 'Aisyah Putri',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2013-06-18',
                'alamat'        => 'Jl. Cempaka No. 9, Bogor',
                'tanggal_masuk' => '2023-01-15',
                'status'        => 'aktif',
                'wali'          => [
                    [
                        'email'      => null,
                        'nama'       => 'Putri Handayani',
                        'hubungan'   => 'ibu',
                        'phone'      => '081300000031',
                        'pekerjaan'  => 'Dokter',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'email'         => null,
                'nama'          => 'Rizky Firmansyah',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2010-11-30',
                'alamat'        => 'Jl. Kebon Jeruk No. 15, Jakarta Barat',
                'tanggal_masuk' => '2020-08-01',
                'status'        => 'alumni',
                'wali'          => [
                    [
                        'email'      => null,
                        'nama'       => 'Firmansyah',
                        'hubungan'   => 'ayah',
                        'phone'      => '081300000032',
                        'pekerjaan'  => 'Pengusaha',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'email'         => null,
                'nama'          => 'Nadia Sari',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2014-03-25',
                'alamat'        => 'Jl. Pahlawan No. 4, Bekasi',
                'tanggal_masuk' => '2023-07-01',
                'status'        => 'aktif',
                'wali'          => [
                    [
                        'email'      => null,
                        'nama'       => 'Sari Wulandari',
                        'hubungan'   => 'ibu',
                        'phone'      => '081300000033',
                        'pekerjaan'  => 'Ibu Rumah Tangga',
                        'is_primary' => true,
                    ],
                    [
                        'email'      => null,
                        'nama'       => 'Hendra Sari',
                        'hubungan'   => 'ayah',
                        'phone'      => '081300000034',
                        'pekerjaan'  => 'Karyawan Swasta',
                        'is_primary' => false,
                    ],
                ],
            ],
        ];

        foreach ($muridData as $item) {
            $userId = null;
            if ($item['email']) {
                $user = User::where('email', $item['email'])->first();
                $userId = $user?->id;
            }

            $murid = Murid::firstOrCreate(
                ['nama' => $item['nama'], 'tanggal_lahir' => $item['tanggal_lahir']],
                [
                    'user_id'       => $userId,
                    'jenis_kelamin' => $item['jenis_kelamin'],
                    'alamat'        => $item['alamat'],
                    'tanggal_masuk' => $item['tanggal_masuk'],
                    'status'        => $item['status'],
                ]
            );

            foreach ($item['wali'] as $wali) {
                $waliUserId = null;
                if ($wali['email']) {
                    $waliUser = User::where('email', $wali['email'])->first();
                    $waliUserId = $waliUser?->id;
                }

                WaliMurid::firstOrCreate(
                    ['murid_id' => $murid->id, 'phone' => $wali['phone']],
                    [
                        'user_id'    => $waliUserId,
                        'nama'       => $wali['nama'],
                        'hubungan'   => $wali['hubungan'],
                        'pekerjaan'  => $wali['pekerjaan'],
                        'is_primary' => $wali['is_primary'],
                    ]
                );
            }
        }
    }
}
