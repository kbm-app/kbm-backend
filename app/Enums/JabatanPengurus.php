<?php

namespace App\Enums;

enum JabatanPengurus: string
{
    case Ketua      = 'ketua';
    case WakilKetua = 'wakil_ketua';
    case Sekretaris = 'sekretaris';
    case Bendahara  = 'bendahara';
    case Penerobos  = 'penerobos';

    /** Jabatan yang boleh membuka sesi & mengisi absensi kelasnya. */
    public static function pengelolaAbsensi(): array
    {
        return [self::Ketua, self::Penerobos];
    }

    /** Jabatan yang boleh melihat kurikulum & progres kelasnya. */
    public static function pelihatKurikulum(): array
    {
        return [self::Ketua];
    }
}
