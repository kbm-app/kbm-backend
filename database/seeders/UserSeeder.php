<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Super Admin
            [
                'name'      => 'Super Admin',
                'email'     => 'superadmin@kbm.id',
                'phone'     => '081200000001',
                'password'  => 'password',
                'role'      => UserRole::SuperAdmin,
                'is_active' => true,
            ],

            // Pengajar
            [
                'name'      => 'Ahmad Fauzi',
                'email'     => 'ahmad.fauzi@kbm.id',
                'phone'     => '081200000002',
                'password'  => 'password',
                'role'      => UserRole::Pengajar,
                'is_active' => true,
            ],
            [
                'name'      => 'Siti Aminah',
                'email'     => 'siti.aminah@kbm.id',
                'phone'     => '081200000003',
                'password'  => 'password',
                'role'      => UserRole::Pengajar,
                'is_active' => true,
            ],
            [
                'name'      => 'Muhammad Ridwan',
                'email'     => 'ridwan@kbm.id',
                'phone'     => '081200000004',
                'password'  => 'password',
                'role'      => UserRole::Pengajar,
                'is_active' => false,
            ],

            // Murid (dengan akun user)
            [
                'name'      => 'Budi Santoso',
                'email'     => 'budi@kbm.id',
                'phone'     => '081200000010',
                'password'  => 'password',
                'role'      => UserRole::Murid,
                'is_active' => true,
            ],
            [
                'name'      => 'Dewi Rahayu',
                'email'     => 'dewi@kbm.id',
                'phone'     => '081200000011',
                'password'  => 'password',
                'role'      => UserRole::Murid,
                'is_active' => true,
            ],

            // Wali Murid
            [
                'name'      => 'Santoso',
                'email'     => 'santoso@kbm.id',
                'phone'     => '081200000020',
                'password'  => 'password',
                'role'      => UserRole::WaliMurid,
                'is_active' => true,
            ],
            [
                'name'      => 'Rahayu',
                'email'     => 'rahayu@kbm.id',
                'phone'     => '081200000021',
                'password'  => 'password',
                'role'      => UserRole::WaliMurid,
                'is_active' => true,
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
