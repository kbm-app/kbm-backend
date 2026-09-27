<?php

namespace App\Policies;

use App\Enums\JabatanPengurus;
use App\Models\Kurikulum;
use App\Models\User;

class KurikulumPolicy
{
    /** Pengajar di salah satu kelas yang memakai kurikulum ini. */
    private function isPengajarKurikulum(User $user, Kurikulum $kurikulum): bool
    {
        return \App\Models\KelasGuru::whereIn('kelas_id', $kurikulum->kelas()->pluck('kelas.id'))
            ->whereHas('pengajar', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar'])
            || $user->punyaJabatan(...JabatanPengurus::pelihatKurikulum());
    }

    public function view(User $user, Kurikulum $kurikulum): bool
    {
        return match ($user->role->value) {
            'super_admin' => true,
            'pengajar'    => $this->isPengajarKurikulum($user, $kurikulum),
            // Ketua kelas: hanya lihat (tidak ada izin manage*)
            'murid'       => $kurikulum->kelas()->aksesKurikulum($user)->exists(),
            default       => false,
        };
    }

    // Hanya super_admin yang bisa CRUD kurikulum
    public function create(User $user): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function update(User $user, Kurikulum $kurikulum): bool
    {
        return $user->role->value === 'super_admin';
    }

    public function delete(User $user, Kurikulum $kurikulum): bool
    {
        return $user->role->value === 'super_admin';
    }

    // Pengajar bisa CRUD bab & materi di kelas yang mereka ajar
    public function manageBab(User $user, Kurikulum $kurikulum): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }
        return $user->role->value === 'pengajar' && $this->isPengajarKurikulum($user, $kurikulum);
    }

    public function manageMateri(User $user, Kurikulum $kurikulum): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }
        return $user->role->value === 'pengajar' && $this->isPengajarKurikulum($user, $kurikulum);
    }

    public function manageProgress(User $user, Kurikulum $kurikulum): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }
        return $user->role->value === 'pengajar' && $this->isPengajarKurikulum($user, $kurikulum);
    }
}
