# Survey General API

Backend PHP native untuk Survey PemKot Jogja.

## Prasyarat

- PHP 8.2+
- Composer
- MySQL/MariaDB
- API seperti project `identity-dummy`

## Setup

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType Directory -Force -Path public\uploads\survey-thumbnails, public\uploads\profiles, storage\logs
@'
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">
  <rect width="640" height="360" fill="#eff4ff"/>
  <text x="320" y="180" text-anchor="middle" fill="#570000" font-family="Arial" font-size="28">Survey Pemkot Jogja</text>
</svg>
'@ | Set-Content public\uploads\survey-thumbnails\default.svg
```

Generate secret/random_bytes:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## Database

Untuk root/user MySQL tanpa password:

```powershell
cmd /c "mysql -u root < database\schema.sql"
php database\seed.php
```

Catatan: `php database\seed.php` membutuhkan minimal satu user di tabel `users`.
Jika database benar-benar kosong, register user dari frontend dulu, lalu jalankan seed lagi.

Jika MySQL memakai password:

```powershell
cmd /c "mysql -u root -p < database\schema.sql"
php database\seed.php
```

Reset data survey dummy tanpa menghapus user:

```powershell
php database\seed.php --reset-surveys
```

## OTP dan Email Lokal

Default development memakai log:

```env
MAIL_TRANSPORT=log
```

Kode email dan OTP lokal tersimpan di:

```text
storage/logs/mail.log
storage/logs/sms.log
```
