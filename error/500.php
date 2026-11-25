<?php

declare(strict_types=1);

/**
 * Apa ini : Halaman kesalahan server.
 * Gunanya : Menampilkan pesan aman saat terjadi kesalahan tanpa memuat bootstrap lagi.
 * Alur    : Tetapkan kode HTTP -> tampilkan pesan aman -> arahkan pengguna kembali bila memungkinkan.
 * Terhubung ke: handler kesalahan di includes/init.php, helpers/kesalahan.php.
 */

/**
 * Halaman: Kesalahan Server (500)
 * Halaman ini TIDAK memuat init.php agar aman dari perulangan.
 */

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi Kesalahan</title>
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/css/base.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/css/components.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/css/responsive.css">
</head>
<body>
    <main class="keadaan-kosong" style="max-width:520px;margin:10vh auto;border:none;">
        <h1 style="font-size:64px;letter-spacing:-0.03em;color:var(--warna-teks-muted);">500</h1>
        <h2>Terjadi Kesalahan pada Sistem</h2>
        <p>Terjadi kesalahan. Coba lagi.</p>
        <a class="btn btn-primer" href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>dashboard/index.php">Kembali ke Dashboard</a>
    </main>
</body>
</html>