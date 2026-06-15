<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar']);
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
}
