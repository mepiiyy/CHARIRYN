<?php

declare(strict_types=1);

/**
 * Apa ini : Halaman profil pada modul Portal pelanggan.
 * Gunanya : Menampilkan profil atau mengajukan perubahan profil.
 * Alur    : Muat bootstrap -> pastikan sesi pelanggan -> batasi data dengan ID pelanggan dari sesi -> tampilkan halaman atau proses form.
 * Terhubung ke: includes/init.php, services/PortalService.php / PengajuanService.php / ProfilService.php, helpers/*, tabel pelanggan, users, piutang, pembayaran, pengajuan.
 */

/**
 * Halaman: Ubah Profil (lama)
 * Fungsi: Dialihkan ke halaman Kelola Akun agar tautan lama tetap berjalan.
 */

// Muat konfigurasi, sesi, helper, service, dan penanganan kesalahan bersama.

require_once __DIR__ . '/../includes/init.php';

redirect(BASE_URL . 'portal/akun.php');
