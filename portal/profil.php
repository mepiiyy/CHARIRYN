<?php

declare(strict_types=1);

/**
 * Halaman: Ubah Profil (lama)
 * Fungsi: Dialihkan ke halaman Kelola Akun agar tautan lama tetap berjalan.
 */

require_once __DIR__ . '/../includes/init.php';

redirect(BASE_URL . 'portal/akun.php');
