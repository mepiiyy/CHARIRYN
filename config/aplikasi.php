<?php

declare(strict_types=1);

/**
 * File: Konfigurasi Aplikasi
 * Fungsi: Konstanta aplikasi, base URL, dan zona waktu
 */

define('APP_NAME', 'Sistem Informasi Pengelolaan Data Piutang Pelanggan');
define('APP_SINGKAT', 'SIPiutang');
define('BASE_PATH', dirname(__DIR__));

// Penyimpanan berkas bukti pembayaran. Folder ini tidak dapat diakses
// lewat HTTP (storage/.htaccess); berkas hanya dibuka lewat endpoint
// yang memeriksa hak akses.
define('STORAGE_PATH', BASE_PATH . '/storage');
define('STORAGE_BUKTI_PATH', STORAGE_PATH . '/bukti');

date_default_timezone_set('Asia/Jakarta');

// Deteksi otomatis base URL, portabel untuk Linux dan Windows/XAMPP.
// Windows memakai backslash dan huruf drive yang bisa berbeda besar-kecil,
// jadi perbandingan path dilakukan case-insensitive.
$nama_folder = basename(BASE_PATH);

if (PHP_SAPI === 'cli' || empty($_SERVER['DOCUMENT_ROOT'])) {
    define('BASE_URL', 'http://localhost/' . $nama_folder . '/');
} else {
    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dokumen = rtrim(str_replace('\\', '/', (string) $_SERVER['DOCUMENT_ROOT']), '/');
    $jalur = rtrim(str_replace('\\', '/', BASE_PATH), '/');
    $relatif = null;

    if ($dokumen !== '' && strncasecmp($jalur, $dokumen, strlen($dokumen)) === 0
        && ($jalur === $dokumen || ($jalur[strlen($dokumen)] ?? '') === '/')) {
        $relatif = substr($jalur, strlen($dokumen));
    }

    // Fallback: proyek diakses lewat symlink/junction, alias folder, atau vhost,
    // sehingga path asli tidak berada di bawah DOCUMENT_ROOT. Hitung selisih
    // antara SCRIPT_NAME (URL) dan SCRIPT_FILENAME (path asli).
    if ($relatif === null) {
        $skrip = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $asli = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
        $berkas = str_replace('\\', '/', realpath($asli) !== false ? realpath($asli) : $asli);
        $sisa = '';
        if ($berkas !== '' && strncasecmp($berkas, $jalur . '/', strlen($jalur) + 1) === 0) {
            $sisa = substr($berkas, strlen($jalur));
        }
        if ($sisa !== '' && strlen($skrip) > strlen($sisa)
            && strcasecmp(substr($skrip, -strlen($sisa)), $sisa) === 0) {
            $relatif = substr($skrip, 0, strlen($skrip) - strlen($sisa));
        } else {
            $penanda = '/' . $nama_folder;
            $pos = stripos($skrip, $penanda . '/');
            $relatif = $pos !== false ? substr($skrip, 0, $pos + strlen($penanda)) : $penanda;
        }
    }

    define('BASE_URL', $skema . '://' . $_SERVER['HTTP_HOST'] . rtrim($relatif, '/') . '/');
}
