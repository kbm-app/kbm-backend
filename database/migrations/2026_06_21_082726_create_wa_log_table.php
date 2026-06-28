<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_log', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['absensi', 'jadwal', 'kas', 'pengumuman']);
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('nomor_tujuan', 20);
            $table->string('nama_penerima', 100);
            $table->text('pesan');
            $table->enum('status', ['terkirim', 'gagal', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tipe', 'status']);
            $table->index('referensi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_log');
    }
};
