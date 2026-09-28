<?php

namespace App\Console\Commands;

use App\Models\BabKurikulum;
use App\Models\Kurikulum;
use App\Models\Materi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;

/**
 * Menyamakan isi tabel materi dengan seeder kurikulum tanpa merusak progress.
 *
 * Urutan langkah (dalam satu transaksi):
 *  1. Rename judul lama → judul baru di tempat (ID materi tetap) sesuai RENAME.
 *  2. Materi yang cocok dengan seeder: sub_bab & metode disamakan (UPDATE).
 *  3. Materi yang tidak ada di seeder: dihapus HANYA jika tidak dirujuk oleh
 *     progress_materi_murid / penyampaian_materi. Kalau dirujuk → dilewati.
 *  4. Materi seeder yang belum ada di DB: dibuat lewat seeder.
 *
 * Default dry-run. Tambahkan --apply untuk menulis ke database.
 */
class SinkronKurikulum extends Command
{
    protected $signature = 'kurikulum:sinkron
                            {seeder : Nama class seeder, contoh: KurikulumKelas5Seeder}
                            {--bulan=* : Batasi ke bulan tertentu, contoh: --bulan=juli --bulan=agustus}
                            {--apply : Jalankan perubahan (tanpa ini hanya dry-run)}';

    protected $description = 'Samakan materi di database dengan seeder kurikulum tanpa menghapus materi yang sudah punya progress';

    /** Tabel yang merujuk materi.id dengan cascadeOnDelete. */
    private const TABEL_RUJUKAN = ['progress_materi_murid', 'penyampaian_materi'];

    /**
     * Judul yang berganti ejaan: [nama kurikulum => [[bulan, judul lama, judul baru], ...]].
     * Tanpa ini, judul baru akan dianggap materi baru dan progress judul lama tertinggal.
     */
    private const RENAME = [
        'Kurikulum Kelas 5' => [
            ['juli', 'Ulil amri, Guru, Mubaligh-mubalighot', 'Ulil Amri, Guru, Muballigh-Muballighot'],
            ['juli', 'Dasa-dasar akidah',                    'Dasar-dasar akidah'],
            ['juli', 'Praktik sholat beserta bacaanya',      'Praktik sholat beserta bacaannya'],
        ],
    ];

    private bool $apply;

    public function handle(): int
    {
        $this->apply = (bool) $this->option('apply');

        $class = 'Database\\Seeders\\Kurikulum\\' . $this->argument('seeder');
        if (! class_exists($class)) {
            $this->error("Seeder {$class} tidak ditemukan.");
            return self::FAILURE;
        }

        $seeder        = new $class;
        $kurikulumNama = $this->panggil($seeder, 'kurikulumNama');
        $bulanFilter   = $this->option('bulan');

        $target = collect($this->panggil($seeder, 'materiData'))
            ->when($bulanFilter, fn ($c) => $c->whereIn('bulan', $bulanFilter))
            ->keyBy(fn ($r) => $this->kunci($r['bab'], $r['bulan'], $r['tipe'], $r['judul']));

        $kurikulum = Kurikulum::where('nama', $kurikulumNama)->first();
        if (! $kurikulum) {
            $this->error("Kurikulum '{$kurikulumNama}' belum ada. Jalankan seeder-nya dulu.");
            return self::FAILURE;
        }

        $this->info(($this->apply ? '== MODE APPLY ==' : '== DRY-RUN (tidak ada yang diubah) ==')
            . " {$kurikulumNama}" . ($bulanFilter ? ' [' . implode(', ', $bulanFilter) . ']' : ''));

        DB::transaction(function () use ($kurikulum, $kurikulumNama, $bulanFilter, $target, $class) {
            $this->rename($kurikulum, self::RENAME[$kurikulumNama] ?? [], $bulanFilter);
            $this->samakanDanBersihkan($kurikulum, $bulanFilter, $target);
            $this->tambahYangBelumAda($kurikulum, $target, $class);
        });

        if (! $this->apply) {
            $this->newLine();
            $this->comment('Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    private function rename(Kurikulum $kurikulum, array $daftar, array $bulanFilter): void
    {
        $this->newLine();
        $this->line('<options=bold>1. Rename judul (ID tetap)</>');

        foreach ($daftar as [$bulan, $lama, $baru]) {
            if ($bulanFilter && ! in_array($bulan, $bulanFilter, true)) {
                continue;
            }

            $materi = Materi::where('kurikulum_id', $kurikulum->id)
                ->where('target_bulan', $bulan)->where('judul', $lama)->get();

            foreach ($materi as $m) {
                $bentrok = Materi::where('kurikulum_id', $kurikulum->id)
                    ->where('bab_kurikulum_id', $m->bab_kurikulum_id)
                    ->where('target_bulan', $bulan)->where('tipe', $m->tipe)
                    ->where('judul', $baru)->exists();

                if ($bentrok) {
                    $this->error("  ! [{$bulan}] #{$m->id} dilewati: judul baru sudah ada ({$baru})");
                    continue;
                }

                $this->line("  ✓ [{$bulan}] #{$m->id} ({$this->ringkasRujukan($m->id)}): {$lama} → {$baru}");
                if ($this->apply) {
                    $m->update(['judul' => $baru]);
                }
            }
        }
    }

    private function samakanDanBersihkan(Kurikulum $kurikulum, array $bulanFilter, $target): void
    {
        $this->newLine();
        $this->line('<options=bold>2–3. Samakan sub_bab/metode & bersihkan materi yang tidak ada di seeder</>');

        $kodeBab = BabKurikulum::where('kurikulum_id', $kurikulum->id)->pluck('kode', 'id');

        $materi = Materi::where('kurikulum_id', $kurikulum->id)
            ->when($bulanFilter, fn ($q) => $q->whereIn('target_bulan', $bulanFilter))
            ->orderBy('id')->get();

        foreach ($materi as $m) {
            // Saat dry-run, rename langkah 1 belum tersimpan — terapkan di memori agar hasilnya akurat.
            $judul = $this->judulSetelahRename($kurikulum, $m);
            $row   = $target->get($this->kunci($kodeBab[$m->bab_kurikulum_id] ?? '?', $m->target_bulan, $m->tipe, $judul));

            if ($row) {
                $ubah = array_filter([
                    'sub_bab' => ($row['sub_bab'] ?? null) !== $m->sub_bab ? ($row['sub_bab'] ?? null) : false,
                    'metode'  => ($row['metode'] ?? null) !== $m->metode ? ($row['metode'] ?? null) : false,
                ], fn ($v) => $v !== false);

                if ($ubah) {
                    $detail = collect($ubah)->map(fn ($v, $k) => "{$k}: " . var_export($m->{$k}, true) . ' → ' . var_export($v, true))->implode(', ');
                    $this->line("  ~ [{$m->target_bulan}] #{$m->id} {$judul} | {$detail}");
                    if ($this->apply) {
                        $m->update($ubah);
                    }
                }
                continue;
            }

            $rujukan = $this->jumlahRujukan($m->id);
            if (array_sum($rujukan) > 0) {
                $this->error("  ! [{$m->target_bulan}] #{$m->id} TIDAK dihapus, masih dirujuk ({$this->ringkasRujukan($m->id)}): {$m->judul}");
                continue;
            }

            $this->line("  - [{$m->target_bulan}] #{$m->id} hapus (tidak ada di seeder, tanpa rujukan): {$m->judul}");
            if ($this->apply) {
                $m->delete();
            }
        }
    }

    private function tambahYangBelumAda(Kurikulum $kurikulum, $target, string $class): void
    {
        $this->newLine();
        $this->line('<options=bold>4. Tambah materi baru dari seeder</>');

        $kodeBab = BabKurikulum::where('kurikulum_id', $kurikulum->id)->pluck('kode', 'id');
        $ada = Materi::where('kurikulum_id', $kurikulum->id)->get()
            ->map(fn ($m) => $this->kunci($kodeBab[$m->bab_kurikulum_id] ?? '?', $m->target_bulan, $m->tipe, $this->judulSetelahRename($kurikulum, $m)));

        $baru = $target->keys()->diff($ada);
        foreach ($baru as $k) {
            $r = $target[$k];
            $this->line("  + [{$r['bulan']}] {$r['tipe']} | {$r['sub_bab']} :: {$r['judul']}");
        }

        if ($this->apply && $baru->isNotEmpty()) {
            $this->callSilent('db:seed', ['--class' => $class, '--force' => true]);
        }

        $this->line("  total: {$baru->count()} materi baru");
    }

    private function judulSetelahRename(Kurikulum $kurikulum, Materi $m): string
    {
        foreach (self::RENAME[$kurikulum->nama] ?? [] as [$bulan, $lama, $baru]) {
            if ($m->target_bulan === $bulan && $m->judul === $lama) {
                return $baru;
            }
        }
        return $m->judul;
    }

    private function jumlahRujukan(int $materiId): array
    {
        return collect(self::TABEL_RUJUKAN)
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->where('materi_id', $materiId)->count()])
            ->all();
    }

    private function ringkasRujukan(int $materiId): string
    {
        return collect($this->jumlahRujukan($materiId))->map(fn ($n, $t) => "{$t}={$n}")->implode(', ');
    }

    private function kunci(string $bab, ?string $bulan, string $tipe, string $judul): string
    {
        return implode('|', [$bab, $bulan, $tipe, $judul]);
    }

    private function panggil(object $seeder, string $method): mixed
    {
        $m = new ReflectionMethod($seeder, $method);
        $m->setAccessible(true);
        return $m->invoke($seeder);
    }
}
