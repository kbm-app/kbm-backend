<?php

namespace App\Services;

use App\Enums\JabatanPengurus;
use App\Models\Jadwal;
use App\Models\KelasPengurus;
use App\Models\Libur;
use App\Models\Pengajar;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Mencari sesi terjadwal yang absensinya belum dilakukan, dikelompokkan per user yang berhak
 * mengisinya: pengajar jadwal/sesi itu, serta murid pengurus kelas berjabatan ketua atau
 * penerobos (sama dengan aturan PertemuanPolicy). Super admin tidak diikutkan.
 *
 * Sebuah sesi (jadwal pada satu tanggal) dianggap BELUM diabsen bila jam selesainya sudah lewat dan:
 *  - belum ada pertemuan sama sekali, atau
 *  - pertemuannya masih berstatus "berlangsung" (belum ditutup).
 * Pertemuan "selesai" dan "batal" dianggap beres. Tanggal libur tidak dihitung.
 */
class ReminderAbsensiService
{
    private const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * @return Collection<int, array{user: User, nama: string, email: string, sesi: Collection<int, array<string, mixed>>}>
     *         dikunci id user; hanya akun aktif beremail yang punya sesi belum diabsen
     */
    public function belumDiabsen(CarbonInterface $sekarang, int $hariMundur = 14): Collection
    {
        $hariIni = Carbon::parse($sekarang->toDateString())->startOfDay();
        $dari    = $hariIni->copy()->subDays($hariMundur);

        $jadwals = Jadwal::query()
            ->whereNotNull('kelas_id')
            ->where('mulai_berlaku', '<=', $hariIni->toDateString())
            ->where(fn ($q) => $q->whereNull('selesai_berlaku')->orWhere('selesai_berlaku', '>=', $dari->toDateString()))
            ->with(['program', 'kelas', 'pengajar'])
            ->get()
            ->filter(fn (Jadwal $j) => $j->kelas && $j->kelas->is_aktif);

        if ($jadwals->isEmpty()) {
            return collect();
        }

        $pertemuan = Pertemuan::query()
            ->whereBetween('tanggal', [$dari->toDateString(), $hariIni->toDateString()])
            ->whereIn('kelas_id', $jadwals->pluck('kelas_id')->unique())
            ->with('absensiPengajar')
            ->get();

        $libur = Libur::query()->beririsan($dari->toDateString(), $hariIni->toDateString())->get();

        // Pengurus yang boleh mengisi absensi: jabatan ketua/penerobos dan murid-nya masih aktif di kelas itu
        $pengurusPerKelas = KelasPengurus::query()
            ->whereIn('kelas_id', $jadwals->pluck('kelas_id')->unique())
            ->whereIn('jabatan', array_map(fn ($j) => $j->value, JabatanPengurus::pengelolaAbsensi()))
            ->whereHas('murid', fn ($m) => $m->whereNull('murid.deleted_at')->whereNotNull('user_id')
                ->whereHas('kelasAktif', fn ($k) => $k->whereColumn('murid_kelas.kelas_id', 'kelas_pengurus.kelas_id')))
            ->with('murid')
            ->get()
            ->groupBy('kelas_id')
            ->map(fn ($g) => $g->pluck('murid.user_id')->unique()->values());

        // id pengajar aktif => id user-nya
        $userPengajar = Pengajar::query()->where('is_aktif', true)->pluck('user_id', 'id');

        /** @var array<int, array<int, array<string, mixed>>> $sesiPerUser */
        $sesiPerUser = [];

        foreach ($jadwals as $jadwal) {
            for ($tgl = $dari->copy(); $tgl->lte($hariIni); $tgl->addDay()) {
                if (! $this->jatuhPada($jadwal, $tgl)) {
                    continue;
                }

                // Sesi yang jam selesainya belum lewat belum boleh ditagih
                if (Carbon::parse($tgl->toDateString().' '.$jadwal->jam_selesai, $sekarang->timezone)->gt($sekarang)) {
                    continue;
                }

                if ($this->libur($libur, $jadwal, $tgl)) {
                    continue;
                }

                $sesi = $this->cariPertemuan($pertemuan, $jadwal, $tgl);

                if ($sesi && in_array($sesi->status, ['selesai', 'batal'], true)) {
                    continue;
                }

                // Sesi yang sudah dibuka: pengajar yang tercatat bertugas. Belum dibuka: semua pengajar jadwal.
                $pengajarIds = $sesi
                    ? $sesi->absensiPengajar->pluck('pengajar_id')->push($sesi->pengajar_id)->unique()
                    : $jadwal->pengajar->pluck('id');

                // Penagih = pengajar di atas + pengurus kelas (ketua/penerobos), tanpa duplikat
                $userIds = $pengajarIds
                    ->map(fn ($id) => $userPengajar[$id] ?? null)
                    ->merge($pengurusPerKelas[$jadwal->kelas_id] ?? [])
                    ->filter()
                    ->unique();

                foreach ($userIds as $userId) {
                    $sesiPerUser[$userId][] = [
                        'tanggal'      => $tgl->copy(),
                        'kelas'        => $jadwal->kelas->nama,
                        'program'      => $jadwal->program->nama ?? '-',
                        'jam_mulai'    => substr($jadwal->jam_mulai, 0, 5),
                        'jam_selesai'  => substr($jadwal->jam_selesai, 0, 5),
                        'sudah_dibuka' => $sesi !== null,
                    ];
                }
            }
        }

        if (! $sesiPerUser) {
            return collect();
        }

        // Akun nonaktif/dihapus atau tanpa email tidak diingatkan (User memakai SoftDeletes)
        return User::query()
            ->whereIn('id', array_keys($sesiPerUser))
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => [
                'user'  => $u,
                'nama'  => $u->name,
                'email' => $u->email,
                'sesi'  => collect($sesiPerUser[$u->id])
                    ->sortBy(fn ($s) => $s['tanggal']->toDateString().$s['jam_mulai'])
                    ->values(),
            ]]);
    }

    /** Apakah jadwal ini ada pada tanggal tsb (hari, masa berlaku, dan minggu ke- untuk jadwal bulanan). */
    private function jatuhPada(Jadwal $jadwal, Carbon $tgl): bool
    {
        if ($jadwal->hari !== self::HARI[$tgl->dayOfWeekIso - 1]) {
            return false;
        }
        if ($tgl->lt($jadwal->mulai_berlaku) || ($jadwal->selesai_berlaku && $tgl->gt($jadwal->selesai_berlaku))) {
            return false;
        }

        // Sama dengan "Jadwal Minggu Ini" dan AbsensiService::pastikanJadwalCocok
        return $jadwal->frekuensi !== 'bulanan' || (int) ceil($tgl->day / 7) === $jadwal->minggu_ke;
    }

    private function libur(Collection $libur, Jadwal $jadwal, Carbon $tgl): bool
    {
        $tanggal = $tgl->toDateString();

        return $libur->contains(fn (Libur $l) => $l->tanggal_mulai->toDateString() <= $tanggal
            && $l->tanggal_selesai->toDateString() >= $tanggal
            && ($l->kelas_id === null || $l->kelas_id === $jadwal->kelas_id)
            && ($l->jadwal_id === null || $l->jadwal_id === $jadwal->id));
    }

    /** Pertemuan untuk jadwal+tanggal ini; sesi tanpa jadwal_id (mis. dari Asisten AI) dicocokkan lewat kelas dan program. */
    private function cariPertemuan(Collection $pertemuan, Jadwal $jadwal, Carbon $tgl): ?Pertemuan
    {
        $kandidat = $pertemuan->filter(fn (Pertemuan $p) => $p->tanggal->toDateString() === $tgl->toDateString()
            && $p->kelas_id === $jadwal->kelas_id
            && ($p->jadwal_id === $jadwal->id || ($p->jadwal_id === null && $p->program_id === $jadwal->program_id)));

        // Bila ada beberapa, yang sudah beres menang agar tidak ditagih dobel
        return $kandidat->first(fn (Pertemuan $p) => in_array($p->status, ['selesai', 'batal'], true))
            ?? $kandidat->first();
    }
}
