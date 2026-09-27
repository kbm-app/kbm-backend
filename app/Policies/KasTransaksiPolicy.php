<?php

namespace App\Policies;

use App\Models\KasTransaksi;
use App\Models\User;

class KasTransaksiPolicy
{
    public function update(User $user, KasTransaksi $kasTransaksi): bool
    {
        return $this->bolehUbah($user, $kasTransaksi);
    }

    public function delete(User $user, KasTransaksi $kasTransaksi): bool
    {
        return $this->bolehUbah($user, $kasTransaksi);
    }

    // Selain super admin, pengajar/bendahara kelas hanya boleh mengubah transaksi hari ini
    private function bolehUbah(User $user, KasTransaksi $kasTransaksi): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }
        return $kasTransaksi->isHariIni()
            && $kasTransaksi->kelas->bisaKelolaKas($user);
    }
}
