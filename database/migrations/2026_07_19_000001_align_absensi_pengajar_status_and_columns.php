<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE absensi_pengajar DROP CONSTRAINT absensi_pengajar_status_check');

        DB::table('absensi_pengajar')->whereIn('status', ['izin', 'sakit'])->update(['status' => 'berhalangan']);
        DB::table('absensi_pengajar')->where('status', 'pengganti')->update(['status' => 'digantikan']);

        DB::statement("ALTER TABLE absensi_pengajar ADD CONSTRAINT absensi_pengajar_status_check CHECK (status::text = ANY (ARRAY['hadir', 'berhalangan', 'digantikan']::text[]))");
        DB::statement("ALTER TABLE absensi_pengajar ALTER COLUMN status SET DEFAULT 'hadir'");

        Schema::table('absensi_pengajar', function (Blueprint $table) {
            $table->renameColumn('pengajar_pengganti_id', 'pengganti_id');
            $table->renameColumn('catatan', 'keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_pengajar', function (Blueprint $table) {
            $table->renameColumn('pengganti_id', 'pengajar_pengganti_id');
            $table->renameColumn('keterangan', 'catatan');
        });

        DB::statement('ALTER TABLE absensi_pengajar DROP CONSTRAINT absensi_pengajar_status_check');

        DB::table('absensi_pengajar')->where('status', 'berhalangan')->update(['status' => 'izin']);
        DB::table('absensi_pengajar')->where('status', 'digantikan')->update(['status' => 'pengganti']);

        DB::statement("ALTER TABLE absensi_pengajar ADD CONSTRAINT absensi_pengajar_status_check CHECK (status::text = ANY (ARRAY['hadir', 'izin', 'sakit', 'pengganti']::text[]))");
        DB::statement("ALTER TABLE absensi_pengajar ALTER COLUMN status SET DEFAULT 'hadir'");
    }
};
