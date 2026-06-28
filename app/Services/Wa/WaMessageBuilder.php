<?php

namespace App\Services\Wa;

class WaMessageBuilder
{
    public static function absensiAlpha(
        string $namaMurid,
        string $namaKelas,
        string $namaProgram,
        string $tanggal,
    ): string {
        return <<<MSG
        [KBM Masjid]
        Assalamualaikum wr. wb.

        Kami informasikan bahwa *{$namaMurid}* tidak hadir (alpha) pada kegiatan:
        • Program: {$namaProgram}
        • Kelas: {$namaKelas}
        • Tanggal: {$tanggal}

        Mohon konfirmasi ketidakhadiran kepada pengajar.
        Jazakumullah khairan.
        MSG;
    }

    public static function reminderJadwal(
        string $namaProgram,
        string $namaKelas,
        string $hari,
        string $jamMulai,
    ): string {
        return <<<MSG
        [KBM Masjid] Pengingat Kegiatan

        Assalamualaikum wr. wb.

        Kegiatan *{$namaProgram}* untuk kelas *{$namaKelas}* akan dilaksanakan:
        • Hari: {$hari}
        • Jam: {$jamMulai}

        Harap hadir tepat waktu.
        Jazakumullah khairan.
        MSG;
    }

    public static function reminderKas(string $namaKelas, string $bulan): string
    {
        return <<<MSG
        [KBM Masjid] Pengingat Shodaqoh

        Assalamualaikum wr. wb.

        Ini adalah pengingat shodaqoh/kas untuk kelas *{$namaKelas}* bulan *{$bulan}*.

        Pembayaran dapat diserahkan kepada pengajar kelas.
        Jazakumullah khairan.
        MSG;
    }

    public static function pengumuman(string $judul, string $konten): string
    {
        return <<<MSG
        [KBM Masjid] *{$judul}*

        {$konten}
        MSG;
    }
}
