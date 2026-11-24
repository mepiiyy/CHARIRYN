<?php

declare(strict_types=1);

/**
 * File: Konfigurasi Database
 * Fungsi: Koneksi PDO tunggal ke MariaDB/MySQL
 *
 * Nilai diambil dari environment variable (Vercel/Railway/dll.) jika tersedia,
 * dengan fallback ke nilai default lokal. Port tidak di-hardcode: XAMPP Windows
 * memakai 3306, XAMPP Linux memakai 3307, jadi port 3306/3307 dicoba otomatis.
 */

define('DB_HOST',     getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_PORT',     getenv('DB_PORT')     ?: '');
define('DB_NAMA',     getenv('DB_NAMA')     ?: 'chariryn');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

function koneksiDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $daftarPort = trim(DB_PORT) !== '' ? [trim(DB_PORT)] : ['3306', '3307'];
    $galat = null;

    foreach ($daftarPort as $port) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAMA . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if (!defined('DB_PORT_AKTIF')) {
                define('DB_PORT_AKTIF', $port);
            }

            return $pdo;
        } catch (PDOException $e) {
            $galat = $e;
        }
    }

    if (function_exists('catatKesalahan')) {
        catatKesalahan(new RuntimeException(
            'Koneksi database ' . DB_NAMA . '@' . DB_HOST . ' gagal pada port '
            . implode('/', $daftarPort) . ': ' . ($galat ? $galat->getMessage() : 'tanpa pesan')
        ));
    }
    http_response_code(500);
    require BASE_PATH . '/error/500.php';
    exit;
}
