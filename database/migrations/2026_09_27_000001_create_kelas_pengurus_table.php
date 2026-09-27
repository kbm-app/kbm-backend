<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengurus kelas (ketua, sekretaris, bendahara, ...) adalah murid di kelas itu.
        // Satu jabatan boleh dipegang lebih dari satu murid.
        Schema::create('kelas_pengurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('murid_id')->constrained('murid')->cascadeOnDelete();
            $table->string('jabatan', 30);
            $table->timestamps();
            $table->unique(['kelas_id', 'murid_id', 'jabatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_pengurus');
    }
};
