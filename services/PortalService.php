<?php

declare(strict_types=1);

/**
 * Apa ini : Service Portal yang berisi aturan bisnis dan query.
 * Gunanya : Membaca data portal hanya untuk pelanggan yang sedang login.
 * Alur    : Halaman memanggil metode -> service menjalankan query PDO -> hasil dikembalikan ke halaman.
 * Terhubung ke: halaman modul, config/database.php, helpers/*, tabel pelanggan, piutang, pembayaran
 */

/**
 * Service: Portal Pelanggan (DFD 8.0)
 * Fungsi: Membaca data piutang milik satu pelanggan yang terautentikasi.
 * Semua query wajib memakai id_pelanggan dari sesi server.
 */

class PortalService
{
    /**
     * Mengambil profil pelanggan berdasarkan ID sesi.
     * Dipanggil oleh: halaman pada modul portal.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return ?array hasil sesuai tugas fungsi.
     */
    public static function profil(PDO $pdo, int $idPelanggan): ?array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare('SELECT * FROM pelanggan WHERE id_pelanggan = :id LIMIT 1');
        $stmt->execute([':id' => $idPelanggan]);
        $hasil = $stmt->fetch();

        return $hasil === false ? null : $hasil;
    }

    /**
     * Menghitung ringkasan pelanggan/piutang terkait.
     * Dipanggil oleh: halaman pada modul portal.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function ringkasan(PDO $pdo, int $idPelanggan): array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS jumlah_piutang,
                COALESCE(SUM(total_piutang), 0) AS total_piutang,
                COALESCE(SUM(jumlah_dibayar), 0) AS total_dibayar,
                COALESCE(SUM(sisa_piutang), 0) AS total_sisa,
                COALESCE(SUM(CASE WHEN sisa_piutang > 0.005 THEN 1 ELSE 0 END), 0) AS belum_lunas,
                COALESCE(SUM(CASE WHEN status = \'Lunas\' THEN 1 ELSE 0 END), 0) AS lunas,
                COALESCE(SUM(CASE WHEN sisa_piutang > 0.005 AND jatuh_tempo < CURDATE() THEN 1 ELSE 0 END), 0) AS lewat_tempo,
                COALESCE(SUM(CASE WHEN sisa_piutang > 0.005 AND jatuh_tempo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS segera_tempo
             FROM piutang
             WHERE id_pelanggan = :id'
        );
        $stmt->execute([':id' => $idPelanggan]);

        return $stmt->fetch();
    }

    /**
     * Mengambil piutang milik pelanggan yang sedang login.
     * Dipanggil oleh: halaman pada modul portal.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function daftarPiutang(PDO $pdo, int $idPelanggan): array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            "SELECT id_piutang, no_transaksi, tanggal_transaksi, total_piutang,
                    jumlah_dibayar, sisa_piutang, jatuh_tempo, status, keterangan,
                    CASE
                        WHEN sisa_piutang <= 0.005 THEN 'lunas'
                        WHEN jatuh_tempo < CURDATE() THEN 'lewat'
                        WHEN jatuh_tempo = CURDATE() THEN 'hari-ini'
                        ELSE 'mendatang'
                    END AS kategori_tempo
             FROM piutang
             WHERE id_pelanggan = :id
             ORDER BY (sisa_piutang > 0.005) DESC, jatuh_tempo ASC, id_piutang DESC"
        );
        $stmt->execute([':id' => $idPelanggan]);

        return $stmt->fetchAll();
    }

    /**
     * Memastikan piutang benar-benar dimiliki pelanggan tersebut.
     * Dipanggil oleh: halaman pada modul portal.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPiutang ID piutang yang menjadi sasaran atau filter.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return ?array hasil sesuai tugas fungsi.
     */
    public static function piutangMilik(PDO $pdo, int $idPiutang, int $idPelanggan): ?array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT p.*, pl.nama_pelanggan, pl.kode_pelanggan, pl.alamat, pl.no_hp, pl.email
             FROM piutang AS p
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             WHERE p.id_piutang = :id_piutang AND p.id_pelanggan = :id_pelanggan
             LIMIT 1'
        );
        $stmt->execute([':id_piutang' => $idPiutang, ':id_pelanggan' => $idPelanggan]);
        $hasil = $stmt->fetch();

        return $hasil === false ? null : $hasil;
    }

    /**
     * Mengambil riwayat pembayaran pelanggan.
     * Dipanggil oleh: halaman pada modul portal.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function riwayatPembayaran(PDO $pdo, int $idPelanggan): array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT b.id_pembayaran, b.tanggal_pembayaran, b.jumlah_bayar, b.metode_pembayaran,
                    b.keterangan, b.status, b.alasan_pembatalan, p.id_piutang, p.no_transaksi
             FROM pembayaran AS b
             JOIN piutang AS p ON p.id_piutang = b.id_piutang
             WHERE p.id_pelanggan = :id
             ORDER BY b.tanggal_pembayaran DESC, b.id_pembayaran DESC'
        );
        $stmt->execute([':id' => $idPelanggan]);

        return $stmt->fetchAll();
    }
}
