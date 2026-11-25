<?php

declare(strict_types=1);

/**
 * Apa ini : Potongan kerangka halaman bersama.
 * Gunanya : Menghindari pengulangan layout, navigasi, pesan, dan bootstrap.
 * Alur    : Halaman memuat bootstrap dan potongan layout -> browser menerima kerangka serta aset bersama.
 * Terhubung ke: halaman staf, helpers/*, assets/css/*, assets/js/*.
 */

/**
 * Navigasi antar jenis laporan (DFD 6.1 pemilihan jenis laporan).
 * Variabel $laporan_aktif diisi halaman pemanggil: piutang|pembayaran|jatuh-tempo|pelanggan.
 */
$laporan_aktif = $laporan_aktif ?? '';
$jenis_laporan = [
    'piutang' => ['label' => 'Piutang', 'url' => 'laporan/piutang.php'],
    'pembayaran' => ['label' => 'Pembayaran', 'url' => 'laporan/pembayaran.php'],
    'jatuh-tempo' => ['label' => 'Jatuh Tempo', 'url' => 'laporan/jatuh-tempo.php'],
    'pelanggan' => ['label' => 'Pelanggan', 'url' => 'laporan/pelanggan.php'],
];
?>
<nav class="aksi-halaman" aria-label="Jenis laporan">
    <?php foreach ($jenis_laporan as $kunci => $laporan): ?>
        <a class="btn <?= $laporan_aktif === $kunci ? 'btn-primer' : 'btn-sekunder' ?> btn-kecil"
           href="<?= BASE_URL . $laporan['url'] ?>"
           <?= $laporan_aktif === $kunci ? 'aria-current="page"' : '' ?>><?= e($laporan['label']) ?></a>
    <?php endforeach; ?>
    <span class="spasi-bebas"></span>
    <button type="button" class="btn btn-primer tombol-cetak">Cetak Laporan</button>
</nav>
