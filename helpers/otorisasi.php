<?php

declare(strict_types=1);

/**
 * Apa ini : Pemeriksaan akses berdasarkan peran.
 * Gunanya : Menghentikan pengguna yang tidak berhak sebelum halaman menjalankan aksi.
 * Alur    : includes/init.php memuat fungsi -> halaman atau service memanggilnya sesuai kebutuhan.
 * Terhubung ke: semua halaman terlindungi, helpers/autentikasi.php, error/403.php.
 */

/**
 * File: Otorisasi
 * Fungsi: Pemeriksaan peran (role) - selalu dilakukan di sisi server.
 *
 * Pemilik  : laporan piutang.
 * Manajer  : operasional (pelanggan, piutang, pembayaran, pemantauan, akun pelanggan).
 * Pelanggan: portal piutang pribadi.
 * Mengalihkan pengguna ke halaman 403.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @return void hasil sesuai tugas fungsi.
 */

function tolakAkses(): void
{
    redirect(BASE_URL . 'error/403.php');
}

/**
 * Membatasi aksi operasional kepada peran yang berwenang.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @return void hasil sesuai tugas fungsi.
 */
function requireManajer(): void
{
    requireLogin();

    if (roleUser() !== 'manajer') {
        tolakAkses();
    }
}

/**
 * Laporan piutang (DFD 6.0).
 * Pelanggan tidak punya akses; DFD Level 1 & 2 menetapkan Pemilik dan
 * Manajer sebagai aktor proses 6.0.
 * Memeriksa peran yang boleh membuka laporan.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @return void hasil sesuai tugas fungsi.
 */
function requireLaporan(): void
{
    requireLogin();

    if (!in_array(roleUser(), ['pemilik', 'manajer'], true)) {
        tolakAkses();
    }
}

/**
 * Membatasi halaman untuk akun pelanggan.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @return void hasil sesuai tugas fungsi.
 */
function requirePelanggan(): void
{
    requireLogin();

    if (roleUser() !== 'pelanggan' || idPelangganUser() <= 0) {
        tolakAkses();
    }
}
