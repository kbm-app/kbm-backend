<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User, pengajar & murid dihapus secara soft delete. FK riwayat (pertemuan, absensi, progress, dst)
 * diubah dari CASCADE ke RESTRICT sebagai pengaman: hard delete yang tidak sengaja akan ditolak
 * database, bukan ikut menghapus riwayat — seperti hilangnya absensi Kelas 2 saat akun pengajar dihapus.
 */
return new class extends Migration
{
    /** [tabel, kolom, tabel_referensi] */
    private array $fkRiwayat = [
        ['pengajar',              'user_id',     'users'],
        ['musyawarah',            'created_by',  'users'],
        ['pertemuan',             'pengajar_id', 'pengajar'],
        ['absensi_pengajar',      'pengajar_id', 'pengajar'],
        ['kelas_pengajar',        'pengajar_id', 'pengajar'],
        ['absensi_murid',         'murid_id',    'murid'],
        ['progress_materi_murid', 'murid_id',    'murid'],
        ['murid_kelas',           'murid_id',    'murid'],
        ['wali_murid',            'murid_id',    'murid'],
        ['kelas_pengurus',        'murid_id',    'murid'],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        foreach ($this->fkRiwayat as [$tabel, $kolom, $ref]) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom, $ref) {
                $table->dropForeign([$kolom]);
                $table->foreign($kolom)->references('id')->on($ref)->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->fkRiwayat as [$tabel, $kolom, $ref]) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom, $ref) {
                $table->dropForeign([$kolom]);
                $table->foreign($kolom)->references('id')->on($ref)->cascadeOnDelete();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
