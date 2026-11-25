<?php

declare(strict_types=1);

/**
 * Apa ini : Format dan konversi tanggal.
 * Gunanya : Mengubah tanggal dari/ke format yang mudah dibaca pengguna Indonesia.
 * Alur    : includes/init.php memuat fungsi -> halaman atau service memanggilnya sesuai kebutuhan.
 * Terhubung ke: halaman form, tabel, laporan.
 */

/**
 * File: Tanggal
 * Fungsi: Pemformatan tanggal Indonesia
 * Memformat tanggal dalam bentuk yang mudah dibaca.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param ?string $tanggal tanggal yang akan diperiksa atau diformat.
 * @param bool $denganNamaBulan nilai yang diperlukan fungsi.
 * @return string hasil sesuai tugas fungsi.
 */

function formatTanggalIndonesia(?string $tanggal, bool $denganNamaBulan = false): string
{
    if ($tanggal === null || $tanggal === '') {
        return '-';
    }

    $waktu = strtotime($tanggal);
    if ($waktu === false) {
        return '-';
    }

    if (!$denganNamaBulan) {
        return date('d/m/Y', $waktu);
    }

    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return date('d', $waktu) . ' ' . $bulan[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
}

/**
 * Memformat tanggal beserta waktu.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param ?string $waktu nilai yang diperlukan fungsi.
 * @return string hasil sesuai tugas fungsi.
 */
function formatTanggalWaktuIndonesia(?string $waktu): string
{
    if ($waktu === null || $waktu === '') {
        return '-';
    }

    $t = strtotime($waktu);
    return $t === false ? '-' : date('d/m/Y H:i', $t);
}

/**
 * Konvensi: seluruh aplikasi memakai format hari/bulan/tahun (dd/mm/yyyy)
 * di layar dan input. Fungsi ini menerima input pengguna (dd/mm/yyyy,
 * dd-mm-yyyy, atau Y-m-d) dan mengembalikan Y-m-d, atau null bila invalid.
 * Mengubah tanggal Indonesia menjadi format database.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param mixed $nilai nilai masukan yang akan diperiksa atau diformat.
 * @return ?string hasil sesuai tugas fungsi.
 */
function parseTanggalID(mixed $nilai): ?string
{
    $nilai = trim((string) $nilai);
    if ($nilai === '') {
        return null;
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nilai, $m) === 1) {
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $nilai : null;
    }

    if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $nilai, $m) === 1) {
        if (!checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }

    return null;
}

/**
 * Nilai untuk atribut value input tanggal: Y-m-d -> dd/mm/yyyy.
 * Mentah yang tak terparse ditampilkan apa adanya (agar ketikan
 * sebagian saat filter realtime tidak hilang).
 * Menyiapkan tanggal untuk ditampilkan.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param mixed $nilai nilai masukan yang akan diperiksa atau diformat.
 * @return string hasil sesuai tugas fungsi.
 */
function tampilTanggalID(mixed $nilai): string
{
    $mentah = trim((string) $nilai);
    $parsed = parseTanggalID($mentah);
    if ($parsed === null) {
        return $mentah;
    }

    return date('d/m/Y', (int) strtotime($parsed));
}

/**
 * Selisih hari antara tanggal tertentu dan hari ini.
 * Positif = sudah lewat, nol = hari ini, negatif = masih depan.
 * Menghitung jarak hari dari tanggal sekarang.
 * Dipanggil oleh: halaman, service, atau include yang membutuhkan helper ini.
 * Data terkait: data yang dipakai fungsi ini.
 * @param string $tanggal tanggal yang akan diperiksa atau diformat.
 * @return int hasil sesuai tugas fungsi.
 */
function selisihHariDariHariIni(string $tanggal): int
{
    $t = strtotime($tanggal);
    $hariIni = strtotime(date('Y-m-d'));

    return $t === false ? 0 : (int) (($hariIni - $t) / 86400);
}