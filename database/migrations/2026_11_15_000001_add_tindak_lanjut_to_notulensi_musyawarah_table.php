<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notulensi_musyawarah', function (Blueprint $table) {
            // Isian tindak lanjut atas poin ini, dicatat saat musyawarah berikutnya.
            $table->text('tindak_lanjut')->nullable()->after('status_tindak_lanjut');
            // Musyawarah tempat tindak lanjut itu dicatat (biasanya bulan setelah poin dibuat).
            $table->foreignId('tindak_lanjut_musyawarah_id')
                ->nullable()
                ->after('tindak_lanjut')
                ->constrained('musyawarah')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notulensi_musyawarah', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tindak_lanjut_musyawarah_id');
            $table->dropColumn('tindak_lanjut');
        });
    }
};
