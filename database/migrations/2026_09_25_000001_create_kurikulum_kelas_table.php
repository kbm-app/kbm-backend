<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu kurikulum bisa dipakai beberapa kelas (mis. Kelas 3-1 & 3-2 memakai Kurikulum Kelas 3).
 * Relasi kurikulum.kelas_id dipindah ke pivot kurikulum_kelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulum_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained('kurikulum')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['kurikulum_id', 'kelas_id']);
        });

        DB::table('kurikulum')->select('id', 'kelas_id')->orderBy('id')->each(function ($row) {
            DB::table('kurikulum_kelas')->insert([
                'kurikulum_id' => $row->id,
                'kelas_id'     => $row->kelas_id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        });

        Schema::table('kurikulum', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }

    public function down(): void
    {
        Schema::table('kurikulum', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('id')->constrained('kelas')->cascadeOnDelete();
        });

        // Kembali ke satu kelas per kurikulum: ambil kelas pertama dari pivot
        DB::table('kurikulum_kelas')->orderBy('id')->get()->groupBy('kurikulum_id')->each(function ($rows, $kurikulumId) {
            DB::table('kurikulum')->where('id', $kurikulumId)->update(['kelas_id' => $rows->first()->kelas_id]);
        });

        Schema::dropIfExists('kurikulum_kelas');
    }
};
