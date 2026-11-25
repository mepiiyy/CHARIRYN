<?php

declare(strict_types=1);

/**
 * Apa ini : Potongan kerangka halaman bersama.
 * Gunanya : Menghindari pengulangan layout, navigasi, pesan, dan bootstrap.
 * Alur    : Halaman memuat bootstrap dan potongan layout -> browser menerima kerangka serta aset bersama.
 * Terhubung ke: halaman staf, helpers/*, assets/css/*, assets/js/*.
 */

/**
 * Header halaman: judul halaman di kiri
 */
?>
<header class="kepala">
    <div class="kepala-judul">
        <h1><?= e($judul_halaman) ?></h1>
    </div>
</header>
