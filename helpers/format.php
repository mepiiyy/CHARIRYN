<?php

declare(strict_types=1);

/**
 * Apa ini : Format angka dan keluaran aman.
 * Gunanya : Menyediakan format Rupiah/angka serta escape HTML untuk nilai dinamis.
 * Alur    : includes/init.php memuat fungsi -> halaman atau service memanggilnya sesuai kebutuhan.
 * Terhubung ke: semua halaman dan komponen tampilan.
 */

/**
 * File: Format
 * Fungsi: Pemformatan Rupiah, angka, dan escaping keluaran
 * Meng-escape nilai agar aman ditampilkan sebagai teks HTML.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param ?string $nilai nilai masukan yang akan diperiksa atau diformat.
 * @return string hasil sesuai tugas fungsi.
 */

function e(?string $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

/**
 * Memformat angka sebagai nominal Rupiah.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param mixed $nilai nilai masukan yang akan diperiksa atau diformat.
 * @return string hasil sesuai tugas fungsi.
 */
function formatRupiah($nilai): string
{
    $nilai = round((float) $nilai, 2);
    $desimal = abs($nilai - round($nilai)) < 0.005 ? 0 : 2;

    return 'Rp ' . number_format($nilai, $desimal, ',', '.');
}

/**
 * Memformat angka menggunakan pemisah Indonesia.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param mixed $nilai nilai masukan yang akan diperiksa atau diformat.
 * @return string hasil sesuai tugas fungsi.
 */
function formatAngka($nilai): string
{
    return number_format((float) $nilai, 0, ',', '.');
}