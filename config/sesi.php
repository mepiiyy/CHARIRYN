<?php

declare(strict_types=1);

/**
 * Apa ini : Pengaturan sesi pengguna.
 * Gunanya : Memulai sesi dengan cookie yang membatasi akses JavaScript dan pengiriman lintas situs.
 * Alur    : Aplikasi membaca nilai ini saat bootstrap, sebelum halaman menjalankan aturan bisnis.
 * Terhubung ke: includes/init.php, helpers/autentikasi.php, tabel users.
 */

/**
 * File: Konfigurasi Sesi
 * Fungsi: Inisialisasi sesi dengan pengaturan keamanan
 */

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('SIPIUTANG_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
