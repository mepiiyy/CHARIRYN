<?php

declare(strict_types=1);

/**
 * Apa ini : Halaman akun pada modul Portal pelanggan.
 * Gunanya : Menampilkan atau memproses pengaturan akun.
 * Alur    : Muat bootstrap -> pastikan sesi pelanggan -> batasi data dengan ID pelanggan dari sesi -> tampilkan halaman atau proses form.
 * Terhubung ke: includes/init.php, services/PortalService.php / PengajuanService.php / ProfilService.php, helpers/*, tabel pelanggan, users, piutang, pembayaran, pengajuan.
 */

/**
 * Halaman: Kelola Akun (portal pelanggan)
 * Fungsi: Satu tempat untuk informasi akun, pengajuan perubahan profil,
 * status permintaan, dan penggantian kata sandi.
 */

// Muat konfigurasi, sesi, helper, service, dan penanganan kesalahan bersama.

require_once __DIR__ . '/../includes/init.php';
// Pastikan pengguna adalah pelanggan sebelum membaca data portal.
requirePelanggan();

// Buka koneksi database yang dipakai service pada halaman ini.

$pdo = koneksiDatabase();
$idPelanggan = idPelangganUser();

$profil = PortalService::profil($pdo, $idPelanggan);
if ($profil === null) {
    hancurkanSesi();
    setFlash('error', 'Data pelanggan tidak ditemukan. Silakan hubungi manajer.');
    redirect(halamanLoginPeran('pelanggan'));
}

$kesalahan = [];
$nama = (string) $profil['nama_pelanggan'];
$alamat = (string) ($profil['alamat'] ?? '');
$noHp = (string) ($profil['no_hp'] ?? '');
$email = (string) ($profil['email'] ?? '');
$alasan = '';

// Tangani data form di server sebelum halaman dirender.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = bersihkan($_POST['nama_pelanggan'] ?? '');
    $alamat = bersihkan($_POST['alamat'] ?? '');
    $noHp = bersihkan($_POST['no_hp'] ?? '');
    $email = bersihkan($_POST['email'] ?? '');
    $alasan = bersihkan($_POST['alasan'] ?? '');

    if (!cekTokenCsrf($_POST['token_csrf'] ?? null)) {
        $kesalahan[] = 'Sesi keamanan tidak valid. Silakan muat ulang halaman.';
    }

    validasiWajib($nama, 'Nama pelanggan', $kesalahan);
    validasiEmail($email, 'Email', $kesalahan);

    if ($alasan === '') {
        $kesalahan[] = 'Alasan perubahan wajib diisi.';
    }

    if ($kesalahan === []) {
        $hasil = ProfilService::buat($pdo, $idPelanggan, (int) userSaatIni()['id_user'], [
            'nama_pelanggan' => $nama,
            'alamat' => $alamat,
            'no_hp' => $noHp,
            'email' => $email,
        ], $alasan);

        if ($hasil['ok']) {
            setFlash('success', $hasil['pesan']);
            redirect(BASE_URL . 'portal/akun.php');
        }

        $kesalahan[] = $hasil['pesan'];
    }
}

$judul_halaman = 'Kelola Akun';
$sub_judul_halaman = 'Profil, perubahan data, dan keamanan akun';
$halaman_aktif = 'portal-akun';

// Mulai kerangka HTML bersama: head, navigasi, dan judul halaman.

require_once BASE_PATH . '/includes/kepala.php';

$akun = userSaatIni();
$daftar = ProfilService::daftarMilikPelanggan($pdo, $idPelanggan);
?>

<section class="kartu mb-lg" aria-label="Informasi akun dan profil">
    <div class="kartu-judul">
        <div>
            <h3>Akun &amp; Profil Aktif</h3>
            <p class="teks-kecil">Perubahan data berlaku setelah disetujui manajer.</p>
        </div>
        <a class="btn btn-primer btn-kecil" href="<?= BASE_URL ?>akun/sandi.php">Ganti Kata Sandi</a>
    </div>
    <div class="form-grid" style="margin:0;">
        <div><span class="teks-kecil">Nama Akun</span><p><?= e((string) $akun['nama']) ?></p></div>
        <div><span class="teks-kecil">Username</span><p><?= e((string) $akun['username']) ?></p></div>
        <div><span class="teks-kecil">Kode Pelanggan</span><p><?= e((string) $profil['kode_pelanggan']) ?></p></div>
        <div><span class="teks-kecil">Nama Pelanggan</span><p><?= e((string) $profil['nama_pelanggan']) ?></p></div>
        <div><span class="teks-kecil">No. HP</span><p><?= e((string) ($profil['no_hp'] ?? '-')) ?></p></div>
        <div><span class="teks-kecil">Email</span><p><?= e((string) ($profil['email'] ?? '-')) ?></p></div>
        <div style="grid-column:1 / -1;"><span class="teks-kecil">Alamat</span><p><?= e((string) ($profil['alamat'] ?? '-')) ?></p></div>
    </div>
</section>

<div class="kotak-form mb-lg">
    <h2>Ajukan Perubahan Profil</h2>

    <?php if ($kesalahan !== []): ?>
        <ul class="pesan-kesalahan" role="alert">
            <?php foreach ($kesalahan as $pesan): ?>
                <li><?= e($pesan) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>portal/akun.php" data-anti-ganda>
        <?= fieldCsrf() ?>
        <div class="form-grid">
            <div class="kolom" style="grid-column:1 / -1;">
                <label for="nama_pelanggan">Nama Pelanggan *</label>
                <input class="input" type="text" id="nama_pelanggan" name="nama_pelanggan" value="<?= e($nama) ?>" required>
            </div>
            <div class="kolom">
                <label for="no_hp">Nomor HP</label>
                <input class="input" type="text" id="no_hp" name="no_hp" value="<?= e($noHp) ?>">
            </div>
            <div class="kolom">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>">
            </div>
            <div class="kolom" style="grid-column:1 / -1;">
                <label for="alamat">Alamat</label>
                <textarea class="textarea" id="alamat" name="alamat"><?= e($alamat) ?></textarea>
            </div>
            <div class="kolom" style="grid-column:1 / -1;">
                <label for="alasan">Alasan Perubahan *</label>
                <textarea class="textarea" id="alasan" name="alasan" placeholder="Jelaskan alasan perubahan data (misal: pindah alamat)" required><?= e($alasan) ?></textarea>
                <span class="bantuan">Wajib diisi</span>
            </div>
        </div>
        <div class="form-aksi">
            <button type="submit" class="btn btn-primer">Kirim Permintaan</button>
            <a class="btn btn-netral" href="<?= BASE_URL ?>portal/index.php">Batal</a>
        </div>
    </form>
</div>

<section class="kartu-panel mb-lg" aria-label="Status permintaan perubahan profil">
    <div class="kartu-judul">
        <h3>Status Permintaan Saya</h3>
    </div>
    <div class="table-wrapper">
        <table class="tabel">
            <thead>
                <tr>
                    <th class="col-tanggal">Diajukan</th>
                    <th class="col-keterangan">Alasan</th>
                    <th class="col-status">Status</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($daftar === []): ?>
                    <tr><td colspan="4" class="tabel-baris-kosong">Belum ada permintaan perubahan profil.</td></tr>
                <?php else: ?>
                    <?php foreach ($daftar as $baris): ?>
                        <tr>
                            <td><?= e(formatTanggalWaktuIndonesia((string) $baris['created_at'])) ?></td>
                            <td><?= e((string) $baris['alasan']) ?></td>
                            <td><?= badgeStatusVerifikasi((string) $baris['status']) ?></td>
                            <td>
                                <?php if ((string) $baris['status'] === 'ditolak'): ?>
                                    <span class="teks-bahaya"><?= e((string) ($baris['alasan_penolakan'] ?? '-')) ?></span>
                                <?php elseif ((string) $baris['status'] === 'disetujui'): ?>
                                    <span class="teks-kecil">Disetujui pada <?= e(formatTanggalWaktuIndonesia((string) ($baris['tanggal_verifikasi'] ?? ''))) ?></span>
                                <?php else: ?>
                                    <span class="teks-kecil">Menunggu peninjauan manajer</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
