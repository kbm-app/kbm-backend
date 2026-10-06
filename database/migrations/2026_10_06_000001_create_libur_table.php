<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libur dicatat sebagai aturan (rentang tanggal + cakupan), bukan sebagai pertemuan:
        // - kelas_id null  → semua kelas (mis. libur akhir semester)
        // - jadwal_id diisi → hanya satu jadwal (meliburkan satu sesi dari form Buka Sesi)
        Schema::create('libur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwal')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('keterangan', 255);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libur');
    }
};
