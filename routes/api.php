<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BabKurikulumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasKategoriController;
use App\Http\Controllers\KasTransaksiController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KurikulumController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\MuridController;
use App\Http\Controllers\PengajarController;
use App\Http\Controllers\PertemuanController;
use App\Http\Controllers\ProgressMateriController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliMuridController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\WaLogController;
use App\Http\Controllers\WaSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::post('avatar', [AuthController::class, 'uploadAvatar']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/chart-absensi', [DashboardController::class, 'chartAbsensi']);
    Route::get('dashboard/chart-kas', [DashboardController::class, 'chartKas']);
    Route::get('dashboard/chart-materi', [DashboardController::class, 'chartMateri']);

    Route::apiResource('users', UserController::class);
    Route::put('users/{user}/toggle', [UserController::class, 'toggleActive']);

    Route::apiResource('pengajar', PengajarController::class);
    Route::put('pengajar/{pengajar}/toggle', [PengajarController::class, 'toggleAktif']);

    Route::apiResource('murid', MuridController::class);
    Route::get('murid/{murid}/wali', [WaliMuridController::class, 'index']);
    Route::post('murid/{murid}/wali', [WaliMuridController::class, 'store']);
    Route::put('wali-murid/{waliMurid}', [WaliMuridController::class, 'update']);
    Route::delete('wali-murid/{waliMurid}', [WaliMuridController::class, 'destroy']);

    Route::apiResource('kelas', KelasController::class);
    Route::get('kelas/{kelas}/pengajar', [KelasController::class, 'pengajarIndex']);
    Route::post('kelas/{kelas}/pengajar', [KelasController::class, 'assignPengajar']);
    Route::delete('kelas/{kelas}/pengajar/{pengajar}', [KelasController::class, 'lepaskanPengajar']);
    Route::get('kelas/{kelas}/murid', [KelasController::class, 'muridIndex']);
    Route::post('kelas/{kelas}/murid', [KelasController::class, 'enrollMurid']);
    Route::delete('kelas/{kelas}/murid/{murid}', [KelasController::class, 'keluarkanMurid']);
    Route::post('kelas/{kelas}/naik-kelas', [KelasController::class, 'naikKelas']);
    Route::get('kelas/{kelas}/jadwal', [JadwalController::class, 'jadwalKelas']);

    Route::apiResource('program', ProgramController::class);
    Route::put('program/{program}/toggle', [ProgramController::class, 'toggleAktif']);
    Route::post('program/{program}/kelas', [ProgramController::class, 'assignKelas']);
    Route::delete('program/{program}/kelas/{kelas}', [ProgramController::class, 'lepasKelas']);

    // Harus sebelum apiResource agar 'minggu-ini' tidak ditangkap sebagai {jadwal}
    Route::get('jadwal/minggu-ini', [JadwalController::class, 'mingguIni']);
    Route::apiResource('jadwal', JadwalController::class);
    Route::post('jadwal/{jadwal}/ganti', [JadwalController::class, 'ganti']);

    // Absensi
    Route::get('rekap/absensi-murid', [PertemuanController::class, 'rekapMurid']);
    Route::get('murid/{muridId}/rekap-absensi', [PertemuanController::class, 'rekapSatuMurid']);

    Route::apiResource('pertemuan', PertemuanController::class)->except(['store']);
    Route::post('pertemuan', [PertemuanController::class, 'store']);
    Route::post('pertemuan/{pertemuan}/selesai', [PertemuanController::class, 'selesai']);
    Route::post('pertemuan/{pertemuan}/batalkan', [PertemuanController::class, 'batalkan']);

    Route::get('pertemuan/{pertemuan}/absensi', [PertemuanController::class, 'absensiIndex']);
    Route::post('pertemuan/{pertemuan}/absensi', [PertemuanController::class, 'absensiBulk']);
    Route::put('absensi-murid/{absensiMurid}', [PertemuanController::class, 'absensiUpdate']);

    Route::post('pertemuan/{pertemuan}/absensi-pengajar', [PertemuanController::class, 'absensiPengajarStore']);
    Route::put('pertemuan/{pertemuan}/absensi-pengajar', [PertemuanController::class, 'absensiPengajarStore']);

    // Kurikulum
    // Harus sebelum {kurikulum} agar 'aktif-kelas' tidak ditangkap sebagai param
    Route::get('kurikulum/aktif-kelas/{kelas}', [KurikulumController::class, 'aktifUntukKelas']);
    Route::get('kurikulum', [KurikulumController::class, 'index']);
    Route::post('kurikulum', [KurikulumController::class, 'store']);
    Route::get('kurikulum/{kurikulum}', [KurikulumController::class, 'show']);
    Route::put('kurikulum/{kurikulum}', [KurikulumController::class, 'update']);
    Route::delete('kurikulum/{kurikulum}', [KurikulumController::class, 'destroy']);
    Route::post('kurikulum/{kurikulum}/duplikat', [KurikulumController::class, 'duplikat']);

    // Bab Kurikulum
    Route::get('kurikulum/{kurikulum}/bab', [BabKurikulumController::class, 'index']);
    Route::post('kurikulum/{kurikulum}/bab', [BabKurikulumController::class, 'store']);
    Route::put('bab-kurikulum/{babKurikulum}', [BabKurikulumController::class, 'update']);
    Route::delete('bab-kurikulum/{babKurikulum}', [BabKurikulumController::class, 'destroy']);
    Route::post('kurikulum/{kurikulum}/bab/urutan', [BabKurikulumController::class, 'reorder']);

    // Materi
    Route::get('bab-kurikulum/{babKurikulum}/materi', [MateriController::class, 'index']);
    Route::post('bab-kurikulum/{babKurikulum}/materi', [MateriController::class, 'store']);
    Route::put('materi/{materi}', [MateriController::class, 'update']);
    Route::delete('materi/{materi}', [MateriController::class, 'destroy']);
    Route::post('kurikulum/{kurikulum}/materi/urutan', [MateriController::class, 'reorder']);
    Route::post('materi/{materi}/selesai-umum', [MateriController::class, 'selesaikanUmum']);
    Route::get('kurikulum/{kurikulum}/materi/bulan/{bulan}', [MateriController::class, 'progressBulan']);

    // Progress
    Route::get('kurikulum/{kurikulum}/progress', [ProgressMateriController::class, 'progressKelas']);
    Route::get('kurikulum/{kurikulum}/progress/{murid}', [ProgressMateriController::class, 'progressMurid']);
    Route::put('progress-materi/{progressMateri}', [ProgressMateriController::class, 'update']);
    Route::post('kurikulum/{kurikulum}/progress-bulk', [ProgressMateriController::class, 'bulk']);

    // Kas — Kategori (super admin only)
    Route::get('kas/kategori', [KasKategoriController::class, 'index']);
    Route::post('kas/kategori', [KasKategoriController::class, 'store']);
    Route::put('kas/kategori/{kasKategori}', [KasKategoriController::class, 'update']);
    Route::delete('kas/kategori/{kasKategori}', [KasKategoriController::class, 'destroy']);

    // Kas — Rekap (harus sebelum {kelas} agar tidak ditangkap sebagai param)
    Route::get('kas/rekap', [KasTransaksiController::class, 'rekap']);
    Route::get('kas/rekap/{kelas}', [KasTransaksiController::class, 'rekapKelas']);

    // Kas — Transaksi
    Route::get('kas/transaksi', [KasTransaksiController::class, 'index']);
    Route::post('kas/transaksi', [KasTransaksiController::class, 'store']);
    Route::put('kas/transaksi/{kasTransaksi}', [KasTransaksiController::class, 'update']);
    Route::delete('kas/transaksi/{kasTransaksi}', [KasTransaksiController::class, 'destroy']);

    // Pengumuman
    Route::get('pengumuman', [PengumumanController::class, 'index']);
    Route::post('pengumuman', [PengumumanController::class, 'store']);
    Route::get('pengumuman/{pengumuman}', [PengumumanController::class, 'show']);

    // WA Log
    Route::get('wa-log', [WaLogController::class, 'index']);
    Route::post('wa-log/{waLog}/retry', [WaLogController::class, 'retry']);

    // Settings WA (Super Admin)
    Route::get('settings/wa', [WaSettingsController::class, 'show']);
    Route::put('settings/wa', [WaSettingsController::class, 'update']);
    Route::get('settings/wa/status', [WaSettingsController::class, 'status']);
    Route::get('settings/wa/qr', [WaSettingsController::class, 'qr']);
    Route::post('settings/wa/reconnect', [WaSettingsController::class, 'reconnect']);
    Route::post('settings/wa/test', [WaSettingsController::class, 'test']);
});
