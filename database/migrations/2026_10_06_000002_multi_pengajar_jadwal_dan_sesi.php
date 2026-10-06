<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu jadwal bisa diampu beberapa pengajar (bergantian/bersamaan dalam satu pertemuan)
        Schema::create('jadwal_pengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->foreignId('pengajar_id')->constrained('pengajar')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['jadwal_id', 'pengajar_id']);
        });

        DB::statement('
            INSERT INTO jadwal_pengajar (jadwal_id, pengajar_id, created_at, updated_at)
            SELECT id, pengajar_id, NOW(), NOW() FROM jadwal WHERE pengajar_id IS NOT NULL
        ');

        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengajar_id');
        });

        // Absensi pengajar: satu baris per pengajar yang bertugas di sesi (sebelumnya satu per sesi)
        Schema::table('absensi_pengajar', function (Blueprint $table) {
            $table->dropUnique(['pertemuan_id']);
            $table->unique(['pertemuan_id', 'pengajar_id']);
        });
    }

    public function down(): void
    {
        // Sesi dengan lebih dari satu pengajar disisakan satu baris (pengajar utama sesi)
        DB::statement('
            DELETE FROM absensi_pengajar ap USING pertemuan p
            WHERE ap.pertemuan_id = p.id AND ap.pengajar_id <> p.pengajar_id
        ');
        Schema::table('absensi_pengajar', function (Blueprint $table) {
            $table->dropUnique(['pertemuan_id', 'pengajar_id']);
            $table->unique(['pertemuan_id']);
        });

        Schema::table('jadwal', function (Blueprint $table) {
            $table->foreignId('pengajar_id')->nullable()->after('kelas_id')->constrained('pengajar')->nullOnDelete();
        });
        DB::statement('
            UPDATE jadwal SET pengajar_id = (
                SELECT MIN(pengajar_id) FROM jadwal_pengajar WHERE jadwal_pengajar.jadwal_id = jadwal.id
            )
        ');

        Schema::dropIfExists('jadwal_pengajar');
    }
};
