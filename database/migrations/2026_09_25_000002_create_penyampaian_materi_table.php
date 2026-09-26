<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Materi umum = target penyampaian pengajar per kelas, bukan capaian per murid.
 * Sebelumnya dicatat satu baris per murid di progress_materi_murid dan metodenya di materi.metode
 * (satu untuk semua kelas). Sekarang satu baris per (materi, kelas) beserta metode penyampaiannya.
 * materi.metode kembali menjadi rencana/template kurikulum ('*').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyampaian_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('pertemuan_id')->nullable()->constrained('pertemuan')->nullOnDelete();
            $table->string('metode', 100)->nullable();
            $table->date('tanggal');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['materi_id', 'kelas_id']);
            $table->index('pertemuan_id');
        });

        // Kelas tiap baris progress umum: kelas pertemuannya, atau kelas murid yang memakai kurikulum materi tsb
        $rows = DB::select(<<<'SQL'
            SELECT p.materi_id,
                   COALESCE(pt.kelas_id, (
                       SELECT mk.kelas_id FROM murid_kelas mk
                       JOIN kurikulum_kelas kk ON kk.kelas_id = mk.kelas_id AND kk.kurikulum_id = m.kurikulum_id
                       WHERE mk.murid_id = p.murid_id
                       ORDER BY (mk.status = 'aktif') DESC, mk.id DESC LIMIT 1
                   )) AS kelas_id,
                   p.pertemuan_id, p.tanggal_selesai, p.created_at, p.updated_at, m.metode
            FROM progress_materi_murid p
            JOIN materi m ON m.id = p.materi_id AND m.tipe = 'umum'
            LEFT JOIN pertemuan pt ON pt.id = p.pertemuan_id
            WHERE p.status = 'selesai'
        SQL);

        collect($rows)
            ->filter(fn ($r) => $r->kelas_id !== null)
            ->groupBy(fn ($r) => "{$r->materi_id}_{$r->kelas_id}")
            ->each(function ($group) {
                $first  = $group->first();
                $metode = in_array(trim((string) $first->metode), ['', '*'], true) ? null : $first->metode;

                DB::table('penyampaian_materi')->insert([
                    'materi_id'    => $first->materi_id,
                    'kelas_id'     => $first->kelas_id,
                    'pertemuan_id' => $group->pluck('pertemuan_id')->filter()->min(),
                    'metode'       => $metode,
                    'tanggal'      => $group->pluck('tanggal_selesai')->filter()->min() ?? substr($first->created_at, 0, 10),
                    'created_at'   => $group->min('created_at'),
                    'updated_at'   => $group->max('updated_at'),
                ]);
            });

        // Metode aktual sudah pindah ke penyampaian_materi → materi.metode kembali jadi template
        DB::table('materi')->where('tipe', 'umum')->whereNotNull('metode')->whereNotIn('metode', ['', '*'])
            ->update(['metode' => '*']);

        DB::table('progress_materi_murid')
            ->whereIn('materi_id', DB::table('materi')->where('tipe', 'umum')->select('id'))
            ->delete();
    }

    public function down(): void
    {
        // Kembalikan ke satu baris per murid aktif di kelas, dan metode ke materi (best effort:
        // bila satu materi disampaikan di beberapa kelas dengan metode berbeda, diambil yang pertama)
        $penyampaian = DB::table('penyampaian_materi')->orderBy('id')->get();

        foreach ($penyampaian as $p) {
            $muridIds = DB::table('murid_kelas')->where('kelas_id', $p->kelas_id)
                ->where('status', 'aktif')->whereNull('tanggal_keluar')->pluck('murid_id');

            foreach ($muridIds as $muridId) {
                DB::table('progress_materi_murid')->insertOrIgnore([
                    'materi_id'       => $p->materi_id,
                    'murid_id'        => $muridId,
                    'pertemuan_id'    => $p->pertemuan_id,
                    'status'          => 'selesai',
                    'tanggal_selesai' => $p->tanggal,
                    'created_at'      => $p->created_at,
                    'updated_at'      => $p->updated_at,
                ]);
            }
        }

        $penyampaian->whereNotNull('metode')->groupBy('materi_id')->each(function ($group, $materiId) {
            DB::table('materi')->where('id', $materiId)->update(['metode' => $group->first()->metode]);
        });

        Schema::dropIfExists('penyampaian_materi');
    }
};
