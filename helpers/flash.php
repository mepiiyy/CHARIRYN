<?php

declare(strict_types=1);

/**
 * Apa ini : Pesan sementara satu kali.
 * Gunanya : Menyimpan pesan di sesi agar halaman tujuan dapat menampilkannya setelah redirect.
 * Alur    : includes/init.php memuat fungsi -> halaman atau service memanggilnya sesuai kebutuhan.
 * Terhubung ke: halaman form, includes/pesan.php.
 */

/**
 * File: Flash Message
 * Fungsi: Pesan sekali pakai (success, error, warning, info)
 * Menyimpan pesan sementara di sesi untuk halaman berikutnya.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param string $jenis nilai yang diperlukan fungsi.
 * @param string $pesan nilai yang diperlukan fungsi.
 * @return void hasil sesuai tugas fungsi.
 */

function setFlash(string $jenis, string $pesan): void
{
    $_SESSION['flash'][] = ['jenis' => $jenis, 'pesan' => $pesan];
}

/**
 * Mengambil pesan sementara sekali lalu menghapusnya dari sesi.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @return array hasil sesuai tugas fungsi.
 */
function ambilFlash(): array
{
    $pesan = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $pesan;
}