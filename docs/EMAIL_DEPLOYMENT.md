# Email Deployment

## Development / Codex

Development memakai `MAIL_MAILER=log`. Aplikasi tidak mengirim email ke alamat sungguhan; seluruh pesan dicatat pada `storage/logs/laravel.log`.

Untuk mencoba reset password:

1. Buka `/forgot-password` dan kirim email akun yang terdaftar.
2. Popup generik akan tampil. Pesan ini sengaja tidak mengungkap apakah alamat email terdaftar.
3. Cari `Subject: Reset Password Akun PD Gunadarma` dalam `storage/logs/laravel.log`.
4. Salin URL `reset-password` dari log, buka pada browser, ubah password, lalu uji login dengan password baru.

Link tersebut dibentuk Laravel dari `APP_URL`. Pada local nilainya adalah `http://127.0.0.1:8000`.

## Railway / production

Tambahkan Railway Variables berikut. Gunakan nilai dari provider email, jangan simpan kredensial di repository:

```env
APP_URL=https://domain-produksi-anda
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=host-provider
MAIL_PORT=587
MAIL_USERNAME=username-provider
MAIL_PASSWORD=password-provider
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domain.com
MAIL_FROM_NAME="PD Gunadarma Event"
```

`MAIL_SCHEME` dapat dibiarkan `null` untuk SMTP standar. Bila provider meminta koneksi implicit TLS, gunakan `smtps`. Setelah variabel diubah, jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

Pastikan `APP_URL` memakai domain HTTPS produksi agar link reset tidak menunjuk localhost.

## Checklist produksi

- Reset password masuk Inbox atau Spam.
- Link reset memakai HTTPS dan domain produksi.
- Token reset masih valid dan password dapat diperbarui.
- Nama serta alamat pengirim benar.
- Tidak ada kredensial pada repository atau log aplikasi.
- `MAIL_MAILER` bukan `log`.

Saat `APP_ENV=production`, aplikasi mencatat warning jika mailer masih `log`, `APP_URL` masih local, atau alamat pengirim masih memakai `example.com`. Warning ini hanya masuk log dan tidak memaparkan rahasia ke browser.

## Struktur notifikasi

Template Markdown bertema `pdug` di `resources/views/vendor/mail` dipakai bersama oleh seluruh email. Reset password tetap sinkron agar tidak memerlukan worker. Struktur notifikasi untuk registrasi dan pembayaran tersedia, tetapi belum dihubungkan ke alur otomatis. Email tiket yang sudah ada hanya dikirim setelah Midtrans menandai pembayaran sebagai berhasil/settlement. Untuk memakai queue di production nanti, implementasikan `ShouldQueue` pada notifikasi transaksi dan jalankan worker queue Railway.
