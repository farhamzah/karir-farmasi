# Runbook lokal

Runtime task berada di `E:\Aplikasi\farmasi-ubp-workspace\.runtime\karir` dan tidak masuk repository atau review bundle.

- PHP: `.runtime\karir\php84\php.exe`
- Composer: jalankan `composer.phar` menggunakan PHP tersebut dan `COMPOSER_HOME` lokal.
- MySQL: Community Server ZIP/no-install, bind `127.0.0.1:33079`, tanpa Windows service.
- Database development: `safa_karir_dev`.
- Database testing: `safa_karir_test`.
- Database production: `safa_karir_prod`.
- Credential hanya berada pada file runtime yang tidak dilacak Git.

Jangan memakai PHP/MySQL XAMPP, Docker/WSL, database Core, atau schema di luar allowlist. Jangan menjalankan `migrate:fresh`, drop, atau truncate pada database selain `safa_karir_test`.

Untuk development, pastikan MySQL khusus karier aktif lalu jalankan:

```powershell
& ..\..\.runtime\karir\php84\php.exe artisan migrate
npm.cmd run build
& ..\..\.runtime\karir\php84\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Gunakan `.env.example` sebagai daftar variabel. Jangan menyalin nilai credential runtime ke dokumentasi atau Git.
