<?php

declare(strict_types=1);

/**
 * File: Konfigurasi Database
 * Fungsi: Koneksi PDO tunggal ke MariaDB/MySQL
 *
 * Nilai diambil dari environment variable (Vercel/Railway/dll.) jika tersedia,
 * dengan fallback ke nilai default lokal (XAMPP port 3307).
 */

define('DB_HOST',     getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_PORT',     getenv('DB_PORT')     ?: '3307');
define('DB_NAMA',     getenv('DB_NAMA')     ?: 'piutang_pelanggan');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

function koneksiDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAMA . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        if (function_exists('catatKesalahan')) {
            catatKesalahan($e);
        }
        http_response_code(500);
        require BASE_PATH . '/error/500.php';
        exit;
    }

    return $pdo;
}
