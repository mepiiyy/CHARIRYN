<?php

declare(strict_types=1);

/**
 * Apa ini : Pencatatan kesalahan server.
 * Gunanya : Menyimpan detail teknis ke log tanpa membocorkannya ke layar pengguna.
 * Alur    : includes/init.php memuat fungsi -> halaman atau service memanggilnya sesuai kebutuhan.
 * Terhubung ke: includes/init.php, error/500.php, logs/error.log.
 */

/**
 * File: Penanganan Kesalahan
 * Fungsi: Mencatat kesalahan ke file log tanpa membocorkan detail ke pengguna
 * Mencatat rincian kesalahan ke log privat server.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param Throwable $kesalahan daftar pesan kesalahan yang dapat ditambah oleh fungsi.
 * @return void hasil sesuai tugas fungsi.
 */

function catatKesalahan(Throwable $kesalahan): void
{
    $baris = '[' . date('Y-m-d H:i:s') . '] '
        . $kesalahan->getMessage()
        . ' di ' . $kesalahan->getFile()
        . ':' . $kesalahan->getLine()
        . PHP_EOL;

    @file_put_contents(BASE_PATH . '/logs/error.log', $baris, FILE_APPEND | LOCK_EX);
}