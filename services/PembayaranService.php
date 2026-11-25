<?php

declare(strict_types=1);

/**
 * Apa ini : Service Pembayaran yang berisi aturan bisnis dan query.
 * Gunanya : Mencatat pembayaran dan menyelaraskan ringkasan piutang.
 * Alur    : Halaman memanggil metode -> service menjalankan query PDO -> hasil dikembalikan ke halaman.
 * Terhubung ke: halaman modul, config/database.php, helpers/*, tabel pembayaran, piutang, pelanggan
 */

/**
 * Service: Pembayaran
 * Fungsi: CRUD pembayaran dengan transaksi database dan rekalkulasi piutang
 */

class PembayaranService
{
    /**
     * Mengambil daftar data dengan filter dan paginasi.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param string $cari kata kunci pencarian, kosong berarti tanpa filter.
     * @param string $metode metode pembayaran untuk filter atau pencatatan.
     * @param ?string $dari batas tanggal awal filter, bila diisi.
     * @param ?string $sampai batas tanggal akhir filter, bila diisi.
     * @param int $halaman nomor halaman yang diminta.
     * @param int $perHalaman jumlah data pada setiap halaman.
     * @param string $status status yang dipakai sebagai filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function ambilSemua(PDO $pdo, string $cari = '', string $metode = '', ?string $dari = null, ?string $sampai = null, int $halaman = 1, int $perHalaman = 10, string $status = ''): array
    {
        // Siapkan filter opsional; SQL hanya menambahkan syarat yang memang dipilih pengguna.
        $kondisi = [];
        $parameter = [];

        if ($cari !== '') {
            $kondisi[] = "(p.no_transaksi LIKE :cari1 ESCAPE '\\\\' OR pl.nama_pelanggan LIKE :cari2 ESCAPE '\\\\')";
            $parameter[':cari1'] = $parameter[':cari2'] = '%' . escapeLike($cari) . '%';
        }

        if ($metode !== '') {
            $kondisi[] = 'b.metode_pembayaran = :metode';
            $parameter[':metode'] = $metode;
        }

        if ($status !== '') {
            $kondisi[] = 'b.status = :status';
            $parameter[':status'] = $status;
        }

        if ($dari !== null && $dari !== '') {
            $kondisi[] = 'b.tanggal_pembayaran >= :dari';
            $parameter[':dari'] = $dari;
        }

        if ($sampai !== null && $sampai !== '') {
            $kondisi[] = 'b.tanggal_pembayaran <= :sampai';
            $parameter[':sampai'] = $sampai;
        }

        $where = $kondisi !== [] ? 'WHERE ' . implode(' AND ', $kondisi) : '';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM pembayaran AS b
             JOIN piutang AS p ON p.id_piutang = b.id_piutang
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             ' . $where
        );
        $stmt->execute($parameter);
        $total = (int) $stmt->fetchColumn();

        $halaman = max(1, min($halaman, max(1, (int) ceil($total / $perHalaman))));
        $offset = ($halaman - 1) * $perHalaman;
        $sql = 'SELECT
                    b.id_pembayaran, b.tanggal_pembayaran, b.jumlah_bayar,
                    b.metode_pembayaran, b.keterangan, b.created_at,
                    b.status, b.alasan_pembatalan, b.dibatalkan_at,
                    p.id_piutang, p.no_transaksi, p.sisa_piutang,
                    pl.id_pelanggan, pl.kode_pelanggan, pl.nama_pelanggan
                FROM pembayaran AS b
                JOIN piutang AS p ON p.id_piutang = b.id_piutang
                JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
                ' . $where . '
                ORDER BY b.tanggal_pembayaran DESC, b.id_pembayaran DESC
                LIMIT :batas OFFSET :offset';

        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

        $stmt = $pdo->prepare($sql);
        foreach ($parameter as $kunci => $nilai) {
            $stmt->bindValue($kunci, $nilai);
        }
        $stmt->bindValue(':batas', $perHalaman, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Mengambil satu baris berdasarkan ID atau mengembalikan null.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $id ID baris yang menjadi sasaran.
     * @return ?array hasil sesuai tugas fungsi.
     */
    public static function ambil(PDO $pdo, int $id): ?array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT b.*, p.id_piutang, p.no_transaksi, p.total_piutang, p.jumlah_dibayar,
                    p.sisa_piutang, p.jatuh_tempo, p.status,
                    pl.id_pelanggan, pl.kode_pelanggan, pl.nama_pelanggan
             FROM pembayaran AS b
             JOIN piutang AS p ON p.id_piutang = b.id_piutang
             JOIN pelanggan AS pl ON pl.id_pelanggan = p.id_pelanggan
             WHERE b.id_pembayaran = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $hasil = $stmt->fetch();

        return $hasil === false ? null : $hasil;
    }

    /**
     * Riwayat pembayaran sebuah piutang (termasuk yang dibatalkan,
     * supaya jejaknya tetap terlihat).
     * Mengambil pembayaran untuk satu piutang.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $idPiutang ID piutang yang menjadi sasaran atau filter.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function ambilPerPiutang(PDO $pdo, int $idPiutang): array
    {
        // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
        $stmt = $pdo->prepare(
            'SELECT id_pembayaran, tanggal_pembayaran, jumlah_bayar, metode_pembayaran, keterangan,
                    status, alasan_pembatalan
             FROM pembayaran
             WHERE id_piutang = :id
             ORDER BY tanggal_pembayaran ASC, id_pembayaran ASC'
        );
        $stmt->execute([':id' => $idPiutang]);

        return $stmt->fetchAll();
    }

    /**
     * Alur penambahan pembayaran (transaksi database):
     * kunci piutang -> validasi -> simpan -> rekalkulasi -> commit.
     * Menyimpan pembayaran dan memperbarui ringkasan piutang.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param array $data nilai form yang telah diterima dari halaman pemanggil.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function buatPembayaran(PDO $pdo, array $data): array
    {
        $mentahId = $data['id_piutang'] ?? null;
        $idPiutang = (is_scalar($mentahId) && is_numeric((string) $mentahId)) ? (int) $mentahId : 0;
        if ($idPiutang <= 0) {
            return ['ok' => false, 'pesan' => 'Piutang tidak ditemukan.'];
        }
        $mentahJumlah = $data['jumlah_bayar'] ?? null;
        if (!is_scalar($mentahJumlah) || trim((string) $mentahJumlah) === '' || !is_numeric((string) $mentahJumlah)) {
            return ['ok' => false, 'pesan' => 'Jumlah pembayaran harus lebih dari 0.'];
        }
        try {
            // Kelompokkan beberapa perubahan agar semuanya berhasil bersama atau dibatalkan bersama.
            $pdo->beginTransaction();
            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.
            $stmt = $pdo->prepare('SELECT id_piutang, sisa_piutang FROM piutang WHERE id_piutang = :id FOR UPDATE');
            $stmt->execute([':id' => $idPiutang]);
            $piutang = $stmt->fetch();
            if ($piutang === false) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Piutang tidak ditemukan.'];
            }
            $jumlah = round((float) $mentahJumlah, 2);
            if ($jumlah <= 0) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Jumlah pembayaran harus lebih dari 0.'];
            }
            $sisa = round((float) $piutang['sisa_piutang'], 2);
            if ($jumlah - $sisa > 0.005) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Nominal pembayaran melebihi sisa piutang.'];
            }

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare(
                'INSERT INTO pembayaran (id_piutang, tanggal_pembayaran, jumlah_bayar, metode_pembayaran, keterangan)
                 VALUES (:id_piutang, :tanggal, :jumlah, :metode, :keterangan)'
            );
            $stmt->execute([
                ':id_piutang' => $idPiutang,
                ':tanggal' => $data['tanggal_pembayaran'],
                ':jumlah' => $jumlah,
                ':metode' => $data['metode_pembayaran'],
                ':keterangan' => $data['keterangan'] ?? null,
            ]);

            perbaruiRingkasanPiutang($pdo, $idPiutang);

            $pdo->commit();

            return ['ok' => true, 'pesan' => 'Pembayaran berhasil disimpan.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            catatKesalahan($e);

            return ['ok' => false, 'pesan' => 'Terjadi kesalahan. Coba lagi.'];
        }
    }

    /**
     * Alur pengubahan pembayaran: keluarkan nilai lama, validasi, simpan, rekalkulasi, commit.
     * Mengubah pembayaran lalu menghitung ulang piutang.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $id ID baris yang menjadi sasaran.
     * @param array $data nilai form yang telah diterima dari halaman pemanggil.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function ubahPembayaran(PDO $pdo, int $id, array $data): array
    {
        $mentahJumlah = $data['jumlah_bayar'] ?? null;
        if (!is_scalar($mentahJumlah) || trim((string) $mentahJumlah) === '' || !is_numeric((string) $mentahJumlah)) {
            return ['ok' => false, 'pesan' => 'Jumlah pembayaran harus lebih dari 0.'];
        }
        try {
            // Kelompokkan beberapa perubahan agar semuanya berhasil bersama atau dibatalkan bersama.
            $pdo->beginTransaction();

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare('SELECT id_piutang, jumlah_bayar, status FROM pembayaran WHERE id_pembayaran = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $lama = $stmt->fetch();

            if ($lama === false) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Pembayaran tidak ditemukan.'];
            }

            if ((string) $lama['status'] !== 'berlaku') {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Pembayaran yang sudah dibatalkan tidak dapat diubah. Catat pembayaran baru bila diperlukan.'];
            }

            $idPiutang = (int) $lama['id_piutang'];

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare('SELECT sisa_piutang FROM piutang WHERE id_piutang = :id FOR UPDATE');
            $stmt->execute([':id' => $idPiutang]);
            $piutang = $stmt->fetch();

            if ($piutang === false) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Piutang tidak ditemukan.'];
            }

            $jumlah = round((float) $mentahJumlah, 2);

            if ($jumlah <= 0) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Jumlah pembayaran harus lebih dari 0.'];
            }

            $sisaEfektif = round((float) $piutang['sisa_piutang'] + (float) $lama['jumlah_bayar'], 2);

            if ($jumlah - $sisaEfektif > 0.005) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Nominal pembayaran melebihi sisa piutang.'];
            }

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare(
                'UPDATE pembayaran
                 SET tanggal_pembayaran = :tanggal, jumlah_bayar = :jumlah,
                     metode_pembayaran = :metode, keterangan = :keterangan
                 WHERE id_pembayaran = :id'
            );
            $stmt->execute([
                ':tanggal' => $data['tanggal_pembayaran'],
                ':jumlah' => $jumlah,
                ':metode' => $data['metode_pembayaran'],
                ':keterangan' => $data['keterangan'] ?? null,
                ':id' => $id,
            ]);

            perbaruiRingkasanPiutang($pdo, $idPiutang);

            $pdo->commit();

            return ['ok' => true, 'pesan' => 'Pembayaran berhasil diperbarui.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            catatKesalahan($e);

            return ['ok' => false, 'pesan' => 'Terjadi kesalahan. Coba lagi.'];
        }
    }

    /**
     * Pembatalan pembayaran resmi (DFD 4.2).
     * Catatan pembayaran TIDAK dihapus: pembayaran ditandai "dibatalkan"
     * beserta alasan, sehingga riwayat tetap terlihat tetapi tidak lagi
     * mengurangi saldo piutang (DFD 4.3).
     * Membatalkan pembayaran dan menyelaraskan saldo.
     * Dipanggil oleh: halaman pada modul pembayaran.
     * Data terkait: pembayaran, piutang, pelanggan.
     * @param PDO $pdo koneksi PDO aktif.
     * @param int $id ID baris yang menjadi sasaran.
     * @param string $alasan alasan perubahan atau pembatalan.
     * @return array hasil sesuai tugas fungsi.
     */
    public static function batalkanPembayaran(PDO $pdo, int $id, string $alasan): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'pesan' => 'Alasan pembatalan wajib diisi.'];
        }

        try {
            // Kelompokkan beberapa perubahan agar semuanya berhasil bersama atau dibatalkan bersama.
            $pdo->beginTransaction();

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare('SELECT id_piutang, status FROM pembayaran WHERE id_pembayaran = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $baris = $stmt->fetch();

            if ($baris === false) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Pembayaran tidak ditemukan.'];
            }

            if ((string) $baris['status'] !== 'berlaku') {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Pembayaran ini sudah dibatalkan.'];
            }

            $idPiutang = (int) $baris['id_piutang'];

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare('SELECT id_piutang FROM piutang WHERE id_piutang = :id FOR UPDATE');
            $stmt->execute([':id' => $idPiutang]);
            if ($stmt->fetch() === false) {
                $pdo->rollBack();
                return ['ok' => false, 'pesan' => 'Piutang tidak ditemukan.'];
            }

            // Prepared statement memisahkan nilai masukan dari SQL agar input tidak menjadi perintah SQL.

            $stmt = $pdo->prepare(
                "UPDATE pembayaran
                 SET status = 'dibatalkan', alasan_pembatalan = :alasan, dibatalkan_at = NOW()
                 WHERE id_pembayaran = :id"
            );
            $stmt->execute([':alasan' => $alasan, ':id' => $id]);

            perbaruiRingkasanPiutang($pdo, $idPiutang);

            $pdo->commit();

            return ['ok' => true, 'pesan' => 'Pembayaran dibatalkan. Sisa piutang telah dihitung ulang.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            catatKesalahan($e);

            return ['ok' => false, 'pesan' => 'Terjadi kesalahan. Coba lagi.'];
        }
    }
}