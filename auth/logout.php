<?php

declare(strict_types=1);

/**
 * Apa ini : Proses keluar pengguna.
 * Gunanya : Mewajibkan POST dan token CSRF, menghapus sesi, lalu kembali ke halaman login.
 * Alur    : Pengguna mengirim form -> server memeriksa input -> service memverifikasi -> sesi atau redirect diperbarui.
 * Terhubung ke: helpers/validasi.php, helpers/autentikasi.php, config/sesi.php.
 */

/**
 * Halaman: Keluar (Logout)
 * Fungsi: Menghancurkan sesi dengan aman (hanya lewat POST + CSRF) lalu
 * mengarahkan ke halaman masuk sesuai peran pengguna.
 */

// Muat konfigurasi, sesi, helper, service, dan penanganan kesalahan bersama.

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cekTokenCsrf($_POST['token_csrf'] ?? null)) {
    redirect(halamanLoginPeran());
}

// Peran diambil sebelum sesi dihapus agar tujuan pengalihan tetap tepat.
$peran = roleUser();

hancurkanSesi();

redirect(halamanLoginPeran($peran));
