<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar', 'murid']);
    }

    public function view(User $user, Kelas $kelas): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }

        if ($user->role->value === 'pengajar') {
            return $kelas->kelasGuru()
                ->whereHas('pengajar', fn($q) => $q->where('user_id', $user->id))
                ->exists();
        }

        return false;
    }

    // Ketua & penerobos boleh melihat jadwal kelasnya, tanpa akses detail kelas lain
    public function viewJadwal(User $user, Kelas $kelas): bool
    {
        return $this->view($user, $kelas)
            || Kelas::whereKey($kelas->id)->aksesJadwal($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function manageGuru(User $user, Kelas $kelas): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function manageMurid(User $user, Kelas $kelas): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function viewKasTransaksi(User $user, Kelas $kelas): bool
    {
        return $kelas->bisaKelolaKas($user);
    }

    public function catatKasTransaksi(User $user, Kelas $kelas): bool
    {
        return $this->viewKasTransaksi($user, $kelas);
    }

    public function managePengurus(User $user, Kelas $kelas): bool
    {
        return $user->role->value === 'super_admin';
    }
}
