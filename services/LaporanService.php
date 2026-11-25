<?php

declare(strict_types=1);

/**
 * Apa ini : Service Laporan yang berisi aturan bisnis dan query.
 * Gunanya : Mengambil data gabungan untuk halaman laporan.
 * Alur    : Halaman memanggil metode -> service menjalankan query PDO -> hasil dikembalikan ke halaman.
 * Terhubung ke: halaman modul, config/database.php, helpers/*, tabel pelanggan, piutang, pembayaran
 */

/**
 * Service: Laporan
 * Fungsi: Agregasi data untuk laporan (pelanggan, piutang, jatuh tempo, pembayaran)
 */

class LaporanService
{
    /**
     * Mengambil data pelanggan untuk laporan.
     * Dipanggil oleh: halaman pada modul laporan.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param string $cari kata kunci pencarian, kosong berarti tanpa filter.
     * @param string $status status yang dipakai sebagai filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function laporanPelanggan(PDO $pdo, string $cari = '', string $status = ''): array
    {
        // Siapkan filter opsional; SQL hanya menambahkan syarat yang memang dipilih pengguna.
        $kondisi = [];
        $parameter = [];

        if ($cari !== '') {
            $kondisi[] = "(p.kode_pelanggan LIKE :cari1 ESCAPE '\\\\' OR p.nama_pelanggan LIKE :cari2 ESCAPE '\\\\' OR p.no_hp LIKE :cari3 ESCAPE '\\\\')";
            $parameter[':cari1'] = $parameter[':cari2'] = $parameter[':cari3'] = '%' . escapeLike($cari) . '%';
        }

        if ($status !== '') {
            $kondisi[] = 'p.status = :status';
            $parameter[':status'] = $status;
        }

        $where = $kondisi !== [] ? 'WHERE ' . implode(' AND ', $kondisi) : '';

        $sql = 'SELECT
                    p.kode_pelanggan, p.nama_pelanggan, p.no_hp, p.email, p.status,
                    (SELECT COUNT(*) FROM piutang AS x WHERE x.id_pelanggan = p.id_pelanggan) AS jumlah_piutang,
                    (SELECT COALESCE(SUM(x.total_piutang), 0) FROM piutang AS x WHERE x.id_pelanggan = p.id_pelanggan) AS total_piutang,
                    (SELECT COALESCE(SUM(x.jumlah_dibayar), 0) FROM piutang AS x WHERE x.id_pelanggan = p.id_pelanggan) AS total_dibayar,
                    (SELECT COALESCE(SUM(x.sisa_piutang), 0) FROM piutang AS x WHERE x.id_pelanggan = p.id_pelanggan) AS total_sisa
                FROM pelanggan AS p
                ' . $where . '
                ORDER BY p.nama_pelanggan ASC';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parameter);
        $data = $stmt->fetchAll();

        $total = [
            'jumlah_pelanggan' => count($data),
            'total_piutang' => array_sum(array_column($data, 'total_piutang')),
            'total_dibayar' => array_sum(array_column($data, 'total_dibayar')),
            'total_sisa' => array_sum(array_column($data, 'total_sisa')),
        ];

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Mengambil data piutang dengan filter laporan.
     * Dipanggil oleh: halaman pada modul laporan.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param string $cari kata kunci pencarian, kosong berarti tanpa filter.
     * @param ?string $dari batas tanggal awal filter, bila diisi.
     * @param ?string $sampai batas tanggal akhir filter, bila diisi.
     * @param string $status status yang dipakai sebagai filter.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function laporanPiutang(PDO $pdo, string $cari = '', ?string $dari = null, ?string $sampai = null, string $status = '', int $idPelanggan = 0): array
    {
        // Siapkan filter opsional; SQL hanya menambahkan syarat yang memang dipilih pengguna.
        $kondisi = [];
        $parameter = [];

        if ($cari !== '') {
            $kondisi[] = "(p.no_transaksi LIKE :cari1 ESCAPE '\\\\' OR pl.nama_pelanggan LIKE :cari2 ESCAPE '\\\\')";
            $parameter[':cari1'] = $parameter[':cari2'] = '%' . escapeLike($cari) . '%';
        }

        if ($dari !== null && $dari !== '') {
            $kondisi[] = 'p.tanggal_transaksi >= :dari';
            $parameter[':dari'] = $dari;
        }

        if ($sampai !== null && $sampai !== '') {
            $kondisi[] = 'p.tanggal_transaksi <= :sampai';
            $parameter[':sampai'] = $sampai;
        }

        if ($status !== '') {
            $kondisi[] = 'p.status = :status';
            $parameter[':status'] = $status;
        }

        if ($idPelanggan > 0) {
            $kondisi[] = 'p.id_pelanggan = :id_pelanggan';
            $parameter[':id_pelanggan'] = $idPelanggan;
        }

        $where = $kondisi !== [] ? 'WHERE ' . implode(' AND ', $kondisi) : '';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT
                p.id_piutang, p.no_transaksi, p.tanggal_transaksi, p.total_piutang,
                p.jumlah_dibayar, p.sisa_piutang, p.jatuh_tempo, p.status, p.keterangan,
                pl.kode_pelanggan, pl.nama_pelanggan
             FROM piutang AS p
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where . '
             ORDER BY p.tanggal_transaksi DESC, p.id_piutang DESC'
        );
        $stmt->execute($parameter);
        $data = $stmt->fetchAll();

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS jumlah,
                COALESCE(SUM(p.total_piutang), 0) AS total_piutang,
                COALESCE(SUM(p.jumlah_dibayar), 0) AS total_dibayar,
                COALESCE(SUM(p.sisa_piutang), 0) AS total_sisa
             FROM piutang AS p
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where
        );
        $stmt->execute($parameter);
        $total = $stmt->fetch();

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Mengambil piutang untuk laporan jatuh tempo.
     * Dipanggil oleh: halaman pada modul laporan.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param string $cari kata kunci pencarian, kosong berarti tanpa filter.
     * @param ?string $dari batas tanggal awal filter, bila diisi.
     * @param ?string $sampai batas tanggal akhir filter, bila diisi.
     * @param string $status status yang dipakai sebagai filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function laporanJatuhTempo(PDO $pdo, string $cari = '', ?string $dari = null, ?string $sampai = null, string $status = ''): array
    {
        // Siapkan filter opsional; SQL hanya menambahkan syarat yang memang dipilih pengguna.
        $kondisi = [];
        $parameter = [];

        if ($cari !== '') {
            $kondisi[] = "(p.no_transaksi LIKE :cari1 ESCAPE '\\\\' OR pl.nama_pelanggan LIKE :cari2 ESCAPE '\\\\')";
            $parameter[':cari1'] = $parameter[':cari2'] = '%' . escapeLike($cari) . '%';
        }

        if ($dari !== null && $dari !== '') {
            $kondisi[] = 'p.jatuh_tempo >= :dari';
            $parameter[':dari'] = $dari;
        }

        if ($sampai !== null && $sampai !== '') {
            $kondisi[] = 'p.jatuh_tempo <= :sampai';
            $parameter[':sampai'] = $sampai;
        }

        if ($status !== '') {
            $kondisi[] = 'p.status = :status';
            $parameter[':status'] = $status;
        }

        $where = $kondisi !== [] ? 'WHERE ' . implode(' AND ', $kondisi) : '';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT
                p.id_piutang, p.no_transaksi, p.tanggal_transaksi, p.total_piutang,
                p.jumlah_dibayar, p.sisa_piutang, p.jatuh_tempo, p.status,
                pl.kode_pelanggan, pl.nama_pelanggan
             FROM piutang AS p
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where . '
             ORDER BY p.jatuh_tempo ASC, p.id_piutang ASC'
        );
        $stmt->execute($parameter);
        $data = $stmt->fetchAll();

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS jumlah,
                COALESCE(SUM(p.total_piutang), 0) AS total_piutang,
                COALESCE(SUM(p.jumlah_dibayar), 0) AS total_dibayar,
                COALESCE(SUM(p.sisa_piutang), 0) AS total_sisa
             FROM piutang AS p
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where
        );
        $stmt->execute($parameter);
        $total = $stmt->fetch();

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Mengambil pembayaran dengan filter laporan.
     * Dipanggil oleh: halaman pada modul laporan.
     * Data terkait: pelanggan, piutang, pembayaran.
     * @param PDO $pdo koneksi PDO aktif.
     * @param string $cari kata kunci pencarian, kosong berarti tanpa filter.
     * @param ?string $dari batas tanggal awal filter, bila diisi.
     * @param ?string $sampai batas tanggal akhir filter, bila diisi.
     * @param string $metode metode pembayaran untuk filter atau pencatatan.
     * @param int $idPelanggan ID pelanggan yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function laporanPembayaran(PDO $pdo, string $cari = '', ?string $dari = null, ?string $sampai = null, string $metode = '', int $idPelanggan = 0): array
    {
        // Siapkan filter opsional; SQL hanya menambahkan syarat yang memang dipilih pengguna.
        $kondisi = [];
        $parameter = [];

        if ($cari !== '') {
            $kondisi[] = "(p.no_transaksi LIKE :cari1 ESCAPE '\\\\' OR pl.nama_pelanggan LIKE :cari2 ESCAPE '\\\\')";
            $parameter[':cari1'] = $parameter[':cari2'] = '%' . escapeLike($cari) . '%';
        }

        if ($dari !== null && $dari !== '') {
            $kondisi[] = 'b.tanggal_pembayaran >= :dari';
            $parameter[':dari'] = $dari;
        }

        if ($sampai !== null && $sampai !== '') {
            $kondisi[] = 'b.tanggal_pembayaran <= :sampai';
            $parameter[':sampai'] = $sampai;
        }

        if ($metode !== '') {
            $kondisi[] = 'b.metode_pembayaran = :metode';
            $parameter[':metode'] = $metode;
        }

        if ($idPelanggan > 0) {
            $kondisi[] = 'p.id_pelanggan = :id_pelanggan';
            $parameter[':id_pelanggan'] = $idPelanggan;
        }

        $where = $kondisi !== [] ? 'WHERE ' . implode(' AND ', $kondisi) : '';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT
                b.id_pembayaran, b.tanggal_pembayaran, b.jumlah_bayar, b.metode_pembayaran, b.keterangan,
                b.status, b.alasan_pembatalan,
                p.no_transaksi, p.id_piutang, p.jatuh_tempo, p.sisa_piutang,
                pl.kode_pelanggan, pl.nama_pelanggan
             FROM pembayaran AS b
             JOIN piutang AS p ON p.id_piutang = b.id_piutang
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where . '
             ORDER BY b.tanggal_pembayaran DESC, b.id_pembayaran DESC'
        );
        $stmt->execute($parameter);
        $data = $stmt->fetchAll();

        // Total pembayaran sah: pembayaran yang dibatalkan tidak dihitung.
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS jumlah,
                COALESCE(SUM(CASE WHEN b.status = \'berlaku\' THEN b.jumlah_bayar ELSE 0 END), 0) AS total_bayar
             FROM pembayaran AS b
             JOIN piutang AS p ON p.id_piutang = b.id_piutang
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where
        );
        $stmt->execute($parameter);
        $total = $stmt->fetch();

        return ['data' => $data, 'total' => $total];
    }
}