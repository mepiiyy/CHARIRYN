
# Chariryn

**Sistem Informasi Pengelolaan Data Piutang Pelanggan**

Chariryn merupakan aplikasi berbasis web untuk mengelola data pelanggan dan piutang. Aplikasi ini menyediakan akses bagi pemilik, manajer, dan pelanggan dengan fungsi yang disesuaikan berdasarkan masing-masing hak akses.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP 8.1 |
| Database | MySQL / MariaDB (InnoDB) |
| Frontend | HTML, CSS, JavaScript |

## Menjalankan Aplikasi

**Persyaratan:** PHP 8.1 atau lebih baru dan MySQL/MariaDB.

1. Jalankan layanan MySQL atau MariaDB. Konfigurasi database lokal menggunakan host `127.0.0.1` dan port `3307`.
2. Buat database dan masukkan data awal dengan menjalankan perintah berikut dari direktori proyek.

```bash
mysql -h 127.0.0.1 -P 3307 -u root < database/schema.sql
mysql -h 127.0.0.1 -P 3307 -u root < database/seed.sql
mysql -h 127.0.0.1 -P 3307 -u root < database/trigger-ringkasan.sql
```

Tambahkan opsi `-p` setelah `-u root` apabila MySQL menggunakan password.

3. Jalankan server PHP.

```bash
php -S 127.0.0.1:8000 -t .
```

4. Buka `http://127.0.0.1:8000` melalui browser.

## Konfigurasi Database

Koneksi database diatur melalui environment variables PHP. Aplikasi tidak membaca file `.env` secara otomatis.

| Variabel | Default |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3307` |
| `DB_NAMA` | `piutang_pelanggan` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | Kosong |

## Akun Demo

Akun berikut tersedia setelah menjalankan `database/seed.sql`.

| Peran | Username | Password |
|---|---|---|
| Pemilik | `pemilik` | `Admin123!` |
| Manajer | `manajer` | `Manajer123!` |
| Pelanggan | `budi` | `Pelanggan123!` |

Akun pelanggan menggunakan nama Budi Santoso.

## Struktur Direktori

```text
Chariryn/
├── auth/               # Autentikasi pengguna
├── akun/               # Pengaturan akun staf
├── akun-pelanggan/     # Pengaturan akun pelanggan
├── dashboard/          # Dashboard
├── portal/             # Portal pelanggan
├── pelanggan/          # Data pelanggan
├── piutang/            # Data piutang
├── pembayaran/         # Data pembayaran
├── pengajuan/          # Pengajuan pembayaran
├── laporan/            # Laporan
├── jatuh-tempo/        # Informasi jatuh tempo
├── cari-pantau/        # Pencarian dan pemantauan
├── services/           # Layanan aplikasi
├── helpers/            # Fungsi pendukung
├── config/             # Konfigurasi
├── includes/           # Komponen bersama
├── database/           # SQL dan skrip database
├── assets/             # CSS, JavaScript, dan gambar
└── storage/bukti/      # Bukti pembayaran
```

## Pengujian

Pengujian sistem menggunakan metode Black Box Testing. Pengujian dilakukan dengan memberikan input pada fungsi aplikasi dan memeriksa output yang dihasilkan berdasarkan hasil yang diharapkan.

