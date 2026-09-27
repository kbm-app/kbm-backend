<?php

namespace App\Policies;

use App\Enums\JabatanPengurus;
use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar'])
            || $user->punyaJabatan(JabatanPengurus::Ketua, JabatanPengurus::Penerobos);
    }

    public function view(User $user, Program $program): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar']);
    }

    public function create(User $user): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function update(User $user, Program $program): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function manageKelas(User $user, Program $program): bool
    {
        return $user->role->value === 'super_admin';
    }
}
