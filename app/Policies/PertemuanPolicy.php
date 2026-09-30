<?php

namespace App\Policies;

use App\Enums\JabatanPengurus;
use App\Models\AbsensiMurid;
use App\Models\Pertemuan;
use App\Models\User;

class PertemuanPolicy
{
    /** Super admin, pengajar, atau murid yang menjadi ketua/penerobos kelas. */
    private function bolehAksesAbsensi(User $user): bool
    {
        return in_array($user->role->value, ['super_admin', 'pengajar'])
            || $user->punyaJabatan(...JabatanPengurus::pengelolaAbsensi());
    }

    public function viewAny(User $user): bool
    {
        return $this->bolehAksesAbsensi($user);
    }

    public function view(User $user, Pertemuan $pertemuan): bool
    {
        return $pertemuan->kelas->bisaKelolaAbsensi($user);
    }

    public function create(User $user): bool
    {
        return $this->bolehAksesAbsensi($user);
    }

    public function update(User $user, Pertemuan $pertemuan): bool
    {
        return $pertemuan->kelas->bisaKelolaAbsensi($user);
    }

    // Hapus sesi tetap hanya untuk super admin & pengajar kelas
    public function delete(User $user, Pertemuan $pertemuan): bool
    {
        if ($pertemuan->status !== 'berlangsung') {
            return false;
        }
        if ($user->role->value === 'super_admin') {
            return true;
        }
        return $user->role->value === 'pengajar' && $pertemuan->kelas->bisaKelolaAbsensi($user);
    }

    public function inputAbsensi(User $user, Pertemuan $pertemuan): bool
    {
        return $pertemuan->kelas->bisaKelolaAbsensi($user);
    }

    public function tutupSesi(User $user, Pertemuan $pertemuan): bool
    {
        return $pertemuan->kelas->bisaKelolaAbsensi($user);
    }

    /** Sesi berlangsung: pengelola absensi kelas; sesi selesai: hanya super admin (koreksi). */
    public function sinkronMurid(User $user, Pertemuan $pertemuan): bool
    {
        return match ($pertemuan->status) {
            'berlangsung' => $pertemuan->kelas->bisaKelolaAbsensi($user),
            'selesai'     => $user->role->value === 'super_admin',
            default       => false,
        };
    }

    public function viewRekap(User $user): bool
    {
        return $this->bolehAksesAbsensi($user);
    }

    public function koreksi(User $user, AbsensiMurid $absensiMurid): bool
    {
        return $user->role->value === 'super_admin';
    }
}
