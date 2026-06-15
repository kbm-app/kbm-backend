<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_pengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('pengajar_id')->constrained('pengajar')->cascadeOnDelete();
            $table->enum('peran', ['utama', 'asisten']);
            $table->string('tahun_ajaran', 20);
            $table->timestamps();
            $table->unique(['kelas_id', 'pengajar_id', 'tahun_ajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_pengajar');
    }
};
