<?php

namespace App\Services;

use App\Events\PertemuanSelesai;
use App\Models\AbsensiMurid;
use App\Models\AbsensiPengajar;
use App\Models\Jadwal;
use App\Models\Libur;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsensiService
{
    public function bukaSesi(array $data): Pertemuan
    {
        if (!empty($data['jadwal_id'])) {
            $this->pastikanJadwalCocok(Jadwal::findOrFail($data['jadwal_id']), $data);

            $sudahAda = Pertemuan::where('jadwal_id', $data['jadwal_id'])
                ->where('tanggal', $data['tanggal'])
                ->whereIn('status', ['berlangsung', 'selesai'])
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'jadwal_id' => 'Jadwal ini sudah memiliki sesi pada tanggal tersebut.',
                ]);
            }

            // Sesi tanpa jadwal (mis. sesi pengganti) tetap boleh dibuka di hari libur
            $libur = Libur::untukSesi((int) $data['kelas_id'], (int) $data['jadwal_id'], $data['tanggal'])->first();
            if ($libur) {
                throw ValidationException::withMessages([
                    'tanggal' => "Jadwal ini diliburkan pada tanggal tersebut ({$libur->keterangan}). Hapus liburnya dulu bila sesi tetap diadakan.",
                ]);
            }
        }

        return DB::transaction(function () use ($data) {
            $pertemuan = Pertemuan::create($data);

            // Buat draft absensi untuk semua peserta sesi (default alpha)
            $muridIds = $this->pesertaSesi($pertemuan);

            $now = now();
            $drafts = $muridIds->map(fn ($muridId) => [
                'pertemuan_id' => $pertemuan->id,
                'murid_id'     => $muridId,
                'status'       => 'alpha',
                'dicatat_oleh' => $data['pengajar_id'] ?? null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ])->values()->all();

            if (!empty($drafts)) {
                AbsensiMurid::insert($drafts);
            }

            // Buat absensi pengajar default hadir
            AbsensiPengajar::create([
                'pertemuan_id' => $pertemuan->id,
                'pengajar_id'  => $data['pengajar_id'],
                'status'       => 'hadir',
            ]);

            return $pertemuan->load(['kelas', 'program', 'pengajar.user', 'absensiMurid.murid', 'absensiPengajar']);
        });
    }

    /**
     * Tanggal sesi harus sesuai jadwal yang dipilih: kelas sama, hari sama, masih dalam masa
     * berlaku, dan untuk jadwal bulanan jatuh di minggu ke- yang benar. Sesi di luar jadwal
     * (mis. sesi pengganti) dibuka tanpa memilih jadwal.
     */
    public function pastikanJadwalCocok(Jadwal $jadwal, array $data): void
    {
        $hariList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $tanggal  = Carbon::parse($data['tanggal'])->startOfDay();
        $hari     = $hariList[$tanggal->dayOfWeekIso - 1];

        $error = match (true) {
            $jadwal->kelas_id !== null && $jadwal->kelas_id !== (int) $data['kelas_id']
                => 'Jadwal yang dipilih bukan milik kelas ini.',
            $jadwal->hari !== $hari
                => sprintf('Jadwal ini untuk hari %s, sedangkan tanggal yang dipilih hari %s.', ucfirst($jadwal->hari), ucfirst($hari)),
            // Minggu ke- dihitung sama seperti "Jadwal Minggu Ini": ceil(tanggal / 7)
            $jadwal->frekuensi === 'bulanan' && (int) ceil($tanggal->day / 7) !== $jadwal->minggu_ke
                => sprintf('Jadwal ini hanya pada %s minggu ke-%d setiap bulan.', ucfirst($jadwal->hari), $jadwal->minggu_ke),
            $tanggal->lt($jadwal->mulai_berlaku)
                || ($jadwal->selesai_berlaku && $tanggal->gt($jadwal->selesai_berlaku))
                => 'Tanggal yang dipilih di luar masa berlaku jadwal ini.',
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['tanggal' => $error]);
        }
    }

    public function inputAbsensiBulk(Pertemuan $pertemuan, array $absensiData, int $pencatatId): void
    {
        if ($pertemuan->status !== 'berlangsung') {
            throw ValidationException::withMessages([
                'status' => 'Absensi hanya bisa diisi pada sesi yang sedang berlangsung.',
            ]);
        }

        $now = now();
        $upsertData = collect($absensiData)->map(fn ($item) => [
            'pertemuan_id' => $pertemuan->id,
            'murid_id'     => $item['murid_id'],
            'status'       => $item['status'],
            'catatan'      => $item['keterangan'] ?? null,
            'dicatat_oleh' => $pencatatId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->all();

        AbsensiMurid::upsert(
            $upsertData,
            ['pertemuan_id', 'murid_id'],
            ['status', 'catatan', 'dicatat_oleh', 'updated_at']
        );
    }

    public function tutupSesi(Pertemuan $pertemuan, string $jamSelesai): Pertemuan
    {
        if ($pertemuan->status !== 'berlangsung') {
            throw ValidationException::withMessages([
                'status' => 'Sesi ini tidak sedang berlangsung.',
            ]);
        }

        if ($jamSelesai <= substr($pertemuan->jam_mulai, 0, 5)) {
            throw ValidationException::withMessages([
                'jam_selesai' => 'Jam selesai harus setelah jam mulai (' . substr($pertemuan->jam_mulai, 0, 5) . ').',
            ]);
        }

        // Validasi semua peserta sesi sudah punya absensi (murid yang didaftarkan setelah sesi
        // dibuka belum punya baris absensi → tambahkan dulu lewat "Perbarui daftar murid")
        $belumAda = $this->pesertaSesi($pertemuan)->diff($pertemuan->absensiMurid()->pluck('murid_id'));

        if ($belumAda->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => "Ada {$belumAda->count()} murid yang belum ada di daftar absensi. Klik \"Perbarui daftar murid\" lalu isi statusnya.",
            ]);
        }

        $pertemuan->update([
            'status'      => 'selesai',
            'jam_selesai' => $jamSelesai,
        ]);

        event(new PertemuanSelesai($pertemuan));

        return $pertemuan->fresh(['kelas', 'program', 'pengajar.user', 'absensiMurid.murid', 'absensiPengajar']);
    }

    /**
     * Peserta sebuah sesi: murid aktif di kelasnya yang sudah bergabung pada tanggal sesi
     * (tanggal bergabung kosong dianggap sudah bergabung).
     */
    public function pesertaSesi(Pertemuan $pertemuan): Collection
    {
        return MuridKelas::where('kelas_id', $pertemuan->kelas_id)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->whereHas('murid', fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('tanggal_masuk')
                ->orWhere('tanggal_masuk', '<=', $pertemuan->tanggal->toDateString())))
            ->pluck('murid_id')
            ->unique()
            ->values();
    }

    /**
     * Tambahkan peserta sesi yang belum ada di daftar absensi (mis. murid baru didaftarkan setelah
     * sesi dibuka) dengan status awal alpha. Absensi yang sudah terisi tidak diubah sama sekali.
     *
     * @return Collection<int, string> nama murid yang ditambahkan
     */
    public function sinkronMurid(Pertemuan $pertemuan, int $pencatatId): Collection
    {
        $baru = $this->pesertaSesi($pertemuan)->diff($pertemuan->absensiMurid()->pluck('murid_id'))->values();

        if ($baru->isNotEmpty()) {
            $now = now();
            AbsensiMurid::insertOrIgnore($baru->map(fn ($muridId) => [
                'pertemuan_id' => $pertemuan->id,
                'murid_id'     => $muridId,
                'status'       => 'alpha',
                'dicatat_oleh' => $pencatatId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ])->all());
        }

        return Murid::whereIn('id', $baru)->orderBy('nama')->pluck('nama');
    }

    /**
     * Rekap kehadiran per murid di satu kelas untuk sekumpulan pertemuan (mis. satu bulan).
     * Untuk tiap murid, pertemuan dihitung sejak absensi pertamanya di kelas itu, agar murid yang
     * baru bergabung tidak dianggap absen di pertemuan sebelum ia masuk. Murid tanpa absensi sama
     * sekali di kelas itu dihitung terhadap seluruh pertemuan.
     *
     * @param  Collection<int, Pertemuan>  $pertemuan  pertemuan periode (butuh id & tanggal)
     * @param  Collection<int, int>|null  $muridIds  batasi ke murid tertentu (mis. murid aktif); null = murid yang punya absensi
     * @return Collection<int, array> per murid_id
     */
    public function rekapKehadiranPerMurid(int $kelasId, Collection $pertemuan, ?Collection $muridIds = null): Collection
    {
        $absensi = AbsensiMurid::whereIn('pertemuan_id', $pertemuan->pluck('id'))
            ->when($muridIds, fn ($q) => $q->whereIn('murid_id', $muridIds))
            ->get(['pertemuan_id', 'murid_id', 'status'])
            ->groupBy('murid_id');

        $ids = ($muridIds ?? $absensi->keys())->values();

        $mulai = AbsensiMurid::join('pertemuan', 'pertemuan.id', '=', 'absensi_murid.pertemuan_id')
            ->where('pertemuan.kelas_id', $kelasId)
            ->whereIn('absensi_murid.murid_id', $ids)
            ->groupBy('absensi_murid.murid_id')
            ->selectRaw('absensi_murid.murid_id, min(pertemuan.tanggal) as mulai')
            ->pluck('mulai', 'murid_id');

        $nama = Murid::withTrashed()->whereIn('id', $ids)->pluck('nama', 'id');

        return $ids->mapWithKeys(function ($muridId) use ($absensi, $mulai, $nama, $pertemuan) {
            $counts = $absensi->get($muridId, collect())->countBy('status');
            $hadir  = ($counts['hadir'] ?? 0) + ($counts['terlambat'] ?? 0);
            $sejak  = $mulai->get($muridId);
            $total  = $sejak
                ? $pertemuan->filter(fn ($p) => $p->tanggal->toDateString() >= substr($sejak, 0, 10))->count()
                : $pertemuan->count();

            return [$muridId => [
                'murid_id'        => $muridId,
                'nama'            => $nama->get($muridId),
                'hadir'           => $counts['hadir'] ?? 0,
                'terlambat'       => $counts['terlambat'] ?? 0,
                'izin'            => $counts['izin'] ?? 0,
                'sakit'           => $counts['sakit'] ?? 0,
                'alpha'           => $counts['alpha'] ?? 0,
                'total_pertemuan' => $total,
                'persentase'      => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
            ]];
        });
    }

    public function hitungPersentaseKehadiran(int $muridId, int $bulan, int $tahun): float
    {
        $pertemuanIds = Pertemuan::selesai()
            ->whereHas('absensiMurid', fn ($q) => $q->where('murid_id', $muridId))
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('id');

        $total = $pertemuanIds->count();
        if ($total === 0) {
            return 0.0;
        }

        $hadir = AbsensiMurid::whereIn('pertemuan_id', $pertemuanIds)
            ->where('murid_id', $muridId)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        return round(($hadir / $total) * 100, 1);
    }
}
