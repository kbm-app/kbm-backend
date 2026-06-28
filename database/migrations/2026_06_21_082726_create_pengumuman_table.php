<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            $table->text('konten');
            $table->enum('target', ['semua', 'murid', 'wali_murid', 'pengajar', 'kelas_tertentu']);
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamp('terkirim_at')->nullable();
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
