<?php

declare(strict_types=1);

/**
 * Apa ini : Halaman reset pada modul Akun pelanggan.
 * Gunanya : Mengatur ulang kata sandi akun pelanggan.
 * Alur    : Muat bootstrap -> periksa sesi dan hak akses -> baca atau validasi permintaan -> panggil service -> tampilkan hasil atau redirect.
 * Terhubung ke: includes/init.php, services/PenggunaService.php, helpers/*, tabel users, pelanggan.
 */

/**
 * Halaman: Reset Kata Sandi Akun Pelanggan (DFD 7.3)
 * Fungsi: Menetapkan kata sandi baru untuk akun pelanggan.
 */

// Muat konfigurasi, sesi, helper, service, dan penanganan kesalahan bersama.

require_once __DIR__ . '/../includes/init.php';
// Periksa peran di server; menyembunyikan menu saja tidak cukup untuk melindungi halaman.
requireManajer();

// Baca ID dari URL melalui helper yang menolak nilai bukan angka.

$id = ambilIdDariGet();
if ($id === null) {
    redirect(BASE_URL . 'error/404.php');
}

// Buka koneksi database yang dipakai service pada halaman ini.

$pdo = koneksiDatabase();
$akun = PenggunaService::ambilAkunPelangganById($pdo, $id);

if ($akun === null) {
    redirect(BASE_URL . 'error/404.php');
}

$judul_halaman = 'Reset Kata Sandi';
$sub_judul_halaman = (string) $akun['username'];
$halaman_aktif = 'akun-pelanggan';

// Mulai kerangka HTML bersama: head, navigasi, dan judul halaman.

require_once BASE_PATH . '/includes/kepala.php';

$kesalahan = [];
$password = '';
$konfirmasi = '';

// Tangani data form di server sebelum halaman dirender.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_password'] ?? '');

    if (!cekTokenCsrf($_POST['token_csrf'] ?? null)) {
        $kesalahan[] = 'Sesi keamanan tidak valid. Silakan muat ulang halaman.';
    }

    if (strlen($password) < 8) {
        $kesalahan[] = 'Kata sandi minimal 8 karakter.';
    }

    if ($password !== $konfirmasi) {
        $kesalahan[] = 'Konfirmasi kata sandi tidak sama.';
    }

    if ($kesalahan === []) {
        $hasil = PenggunaService::resetKataSandi($pdo, $id, $password);
        if ($hasil['ok']) {
            setFlash('success', $hasil['pesan']);
            redirect(BASE_URL . 'akun-pelanggan/index.php');
        }
        $kesalahan[] = $hasil['pesan'];
    }
}
?>

<div class="kotak-form">
    <div class="aksi-halaman">
        <a class="btn btn-netral btn-kecil" href="<?= BASE_URL ?>akun-pelanggan/index.php">Kembali</a>
    </div>
    <h2>Reset Kata Sandi</h2>
    <p class="teks-kecil mb-lg">
        Akun <strong><?= e($akun['username']) ?></strong>
        <?php if ($akun['nama_pelanggan'] !== null): ?> untuk pelanggan <strong><?= e($akun['nama_pelanggan']) ?></strong><?php endif; ?>.
        Kata sandi lama tidak dapat dilihat, hanya diganti.
    </p>

    <?php if ($kesalahan !== []): ?>
        <ul class="pesan-kesalahan" role="alert">
            <?php foreach ($kesalahan as $pesan): ?>
                <li><?= e($pesan) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>akun-pelanggan/reset.php?id=<?= (int) $id ?>" data-anti-ganda>
        <?= fieldCsrf() ?>
        <div class="form-grid">
            <div class="kolom">
                <label for="password">Kata Sandi Baru *</label>
                <input class="input" type="password" id="password" name="password" autocomplete="new-password" required>
                <span class="bantuan">Minimal 8 karakter.</span>
            </div>
            <div class="kolom">
                <label for="konfirmasi_password">Konfirmasi Kata Sandi Baru *</label>
                <input class="input" type="password" id="konfirmasi_password" name="konfirmasi_password" autocomplete="new-password" required>
            </div>
        </div>
        <div class="form-aksi">
            <button type="submit" class="btn btn-primer">Reset Kata Sandi</button>
            <a class="btn btn-netral" href="<?= BASE_URL ?>akun-pelanggan/index.php">Batal</a>
        </div>
    </form>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
