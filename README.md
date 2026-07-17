# Website Informasi dan Registrasi Event PD Gunadarma

Aplikasi Laravel untuk publikasi event PD Gunadarma, registrasi peserta, pembayaran Midtrans, tiket QR, serta pengelolaan data melalui panel Filament.

## Teknologi

- PHP 8.4 atau lebih baru
- Laravel 13
- Filament 5
- MySQL 8 untuk deployment (SQLite tetap dapat digunakan untuk development)
- Vite 8, Tailwind CSS 4, Node.js, dan npm
- Midtrans Snap
- Laravel Mail dan database queue
- `chillerlan/php-qrcode` untuk tiket QR

## Persyaratan

- PHP 8.4+ dengan ekstensi `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, dan `zip`
- Composer 2
- Node.js 20+ dan npm
- MySQL 8+

## Instalasi Lokal

1. Ekstrak project dan buka terminal pada folder yang memuat file `artisan`.
2. Instal dependency:

```bash
composer install
npm install
```

3. Buat environment lokal:

```bash
cp .env.example .env
php artisan key:generate
```

Pada PowerShell gunakan `Copy-Item .env.example .env`.

4. Buat database MySQL dan isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` di `.env`.
5. Jalankan migration dan seeder:

```bash
php artisan migrate --seed
php artisan storage:link
```

6. Bangun asset dan jalankan aplikasi:

```bash
npm run build
php artisan serve
```

Untuk pengembangan frontend, gunakan `npm run dev` pada terminal terpisah.

### Alternatif SQLite

Untuk development sederhana, ubah `.env` menjadi:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/absolut/ke/project/database/database.sqlite
```

Buat file kosong `database/database.sqlite`, lalu jalankan migration. File database lokal tidak boleh dimasukkan ke Git atau dibagikan.

## Akun Admin Awal

Seeder tidak memiliki password admin tetap. Isi variabel berikut hanya pada environment pribadi sebelum menjalankan `php artisan migrate --seed`:

```env
ADMIN_NAME="Administrator PDUG"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password-kuat-minimal-12-karakter
```

Seeder menggunakan `updateOrCreate`, sehingga tidak membuat akun admin ganda. Hapus `ADMIN_PASSWORD` dari environment setelah bootstrap bila kebijakan deployment mengharuskannya. Pengelolaan admin berikutnya dilakukan dari **Kelola Pengguna**.

## Konfigurasi Email

Selama local development gunakan `MAIL_MAILER=log`; isi email dapat diperiksa pada `storage/logs/laravel.log`. Untuk production, isi konfigurasi SMTP melalui `.env` atau Railway Variables:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.provider.example
MAIL_PORT=587
MAIL_USERNAME=username-provider
MAIL_PASSWORD=password-provider
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domain.com
MAIL_FROM_NAME="PD Gunadarma Event"
```

### SMTP Gmail

Gunakan **Gmail App Password** (bukan password akun Gmail), aktifkan verifikasi dua langkah, lalu gunakan:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alamatgmail@gmail.com
MAIL_PASSWORD=app_password_16_karakter
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=alamatgmail@gmail.com
MAIL_FROM_NAME="PD Gunadarma Event"
```

Jangan menyimpan App Password atau kredensial provider pada repository. Konfigurasi ini dipakai untuk reset password dan email tiket.

Jika `QUEUE_CONNECTION=database`, jalankan worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

## Konfigurasi Midtrans

Gunakan kredensial Sandbox selama development:

```env
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true
```

Status pembayaran tidak diambil dari input frontend. Aplikasi memvalidasi signature, order ID, dan nominal dari Midtrans. Setelah domain publik tersedia, atur **Payment Notification URL** pada dashboard Midtrans menjadi:

```text
https://domain-aplikasi/payments/midtrans/notification
```

Untuk localhost, gunakan tunnel HTTPS seperti ngrok dan arahkan notification URL ke route yang sama. Jangan menyimpan token ngrok di project.

## Storage

File publik menggunakan `storage/app/public`. Jalankan:

```bash
php artisan storage:link
```

Folder `storage` dan `bootstrap/cache` harus dapat ditulis oleh proses PHP. Jangan mengekspor upload pengguna, log, session, cache, atau symbolic link lokal.

## Panel Admin

Panel tersedia di `/admin` dan hanya dapat diakses akun dengan role `admin`.

- Dashboard: `/admin`
- Event: `/admin/events`
- Transaksi: `/admin/registrations`
- Pengguna: `/admin/users`
- Laporan: `/admin/laporan`
- Pengaturan website: `/admin/pengaturan-website`

## Deployment Railway

Project menyertakan `railway.json` dan script pada folder `railway/`. Railway menggunakan Railpack untuk mendeteksi Laravel, menjalankan `npm run build`, lalu menjalankan migration serta cache pada tahap pre-deploy.

1. Push source code ke repository GitHub tanpa `.env`, `vendor`, `node_modules`, database lokal, log, atau upload pengguna.
2. Buat project Railway dari repository tersebut.
3. Tambahkan service MySQL dan hubungkan `DB_URL` ke URL koneksi service MySQL, atau isi variabel `DB_*` secara terpisah.
4. Atur minimal:

```env
APP_NAME="PD Gunadarma Event"
APP_ENV=production
APP_KEY=<hasil-php-artisan-key-generate-show>
APP_DEBUG=false
APP_URL=https://domain-railway
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

5. Isi SMTP dan Midtrans melalui Variables Railway.
6. Generate domain pada menu Networking dan perbarui `APP_URL` serta notification URL Midtrans.
7. Pada deployment pertama, jalankan `php artisan db:seed --force` melalui Railway shell bila ingin membuat data awal dan akun dari variabel `ADMIN_*`.
8. Untuk queue, buat service kedua dari repository yang sama dan gunakan start command:

```bash
chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh
```

Railway mendeteksi web Laravel melalui PHP-FPM dan Caddy, sehingga project tidak mengunci start command web tertentu. Health check menggunakan `/up`.

Catatan: filesystem Railway bersifat sementara. Untuk upload yang harus persisten, gunakan Railway Volume atau object storage dan sesuaikan `FILESYSTEM_DISK`.

### Variabel environment Railway

Semua variabel tersedia sebagai placeholder di `.env.example`. Minimal periksa dan isi kategori berikut melalui Railway Variables:

- **Aplikasi & log:** `APP_NAME`, `APP_ENV=production`, `APP_KEY`, `APP_DEBUG=false`, `APP_URL`, `LOG_CHANNEL=stderr`, `LOG_LEVEL`.
- **Database:** `DB_CONNECTION=mysql` dan salah satu dari `DB_URL` atau seluruh `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **Session, cache, queue, storage:** `SESSION_DRIVER`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK`.
- **Mail:** seluruh `MAIL_*` termasuk alamat pengirim yang valid.
- **Pembayaran:** `MIDTRANS_MERCHANT_ID`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, serta `MIDTRANS_IS_PRODUCTION=true` setelah kredensial production dipakai.
- **Admin awal (opsional):** `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` hanya saat menjalankan seeder pertama kali.
- **Frontend:** `VITE_APP_NAME` bila branding frontend perlu diubah.

Setelah mengubah Variables, deploy ulang atau jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Validasi Project

```bash
composer validate --strict --no-check-publish
php artisan optimize:clear
php artisan route:list
php artisan migrate:status
php artisan test
npm run build
```

## Troubleshooting

- **Halaman tanpa CSS:** jalankan `npm install && npm run build`.
- **`No application encryption key`:** jalankan `php artisan key:generate`.
- **Database gagal terhubung:** periksa `DB_URL` atau seluruh variabel `DB_*`, lalu jalankan `php artisan config:clear`.
- **Email tidak terkirim:** periksa SMTP, jalankan queue worker, kemudian lihat failed jobs dengan `php artisan queue:failed`.
- **Webhook Midtrans tidak masuk:** pastikan notification URL memakai HTTPS publik dan route `/payments/midtrans/notification` dapat diakses dari internet.
- **Gambar upload tidak tampil:** jalankan `php artisan storage:link` dan periksa `APP_URL`.
- **Permission error:** berikan izin tulis kepada proses PHP untuk `storage` dan `bootstrap/cache`.

## Keamanan Ekspor

Jangan pernah mengunggah `.env`, password database, Gmail App Password, Server Key Midtrans, token ngrok, database pengguna, log, atau private key. Dependency dipulihkan melalui `composer install` dan `npm install`; folder `vendor` dan `node_modules` tidak perlu dibagikan.
