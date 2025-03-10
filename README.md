# SENGKUYUNG FROM MINI LARAVEL STARTER

Sengkuyung aplikasi berbasis web  dan api servis untuk mobile sengkuyung app by BAPENDA

## Fitur

- Fitur 1
- Fitur 2
- Fitur 3

## Prerequisites

Sebelum menjalankan proyek ini, pastikan Anda memiliki hal-hal berikut:

- PHP >= 8.3
- Composer
- Database (MySQL)

## Instalasi Cpanel dengan Terminal

Langkah-langkah untuk menginstal dan menjalankan proyek:

1. Buat SSH Key Baru
   - Masuk ke server Anda melalui SSH dan jalankan perintah berikut untuk membuat SSH key baru: 
    ```bash
    mkdir -p public_html/sengkuyung/.ssh
    ssh-keygen -t rsa -b 4096 -f public_html/sengkuyung/.ssh/id_rsa -N ""

2. Jalankan dan copy id_rsa.pub:
   ```bash
   cat public_html/sengkuyung/.ssh/id_rsa.pub


3. Buka GitHub Repository
   - Buka GitHub Repo → Settings → Deploy Keys
   - Klik "Add Key"
   - Paste isi id_rsa.pub (bukan id_rsa)
   - Centang Allow write access (jika ingin bisa push)
   - Klik "Add Key"

4. Set Permissions untuk SSH Key
   ```bash
   chmod 600 public_html/sengkuyung/.ssh/id_rsa
   chmod 644 public_html/sengkuyung/.ssh/id_rsa.pub


5. Buat atau edit file ~/.ssh/config untuk memastikan server menggunakan SSH key yang benar:
   ```bash
   nano ~/.ssh/config

6. Tambahkan baris berikut:
   ```bash
   Host github.com
   IdentityFile  public_html/sengkuyung/.ssh/id_rsa
   StrictHostKeyChecking no

7. Uji Koneksi 
   ```bash
   cd public_html/sengkuyung
   ssh -T git@github.com


8. Clone repositori ini:
   ```bash
   git clone git@github.com:prayogoedwin/sengkuyung.git

9. Pull:
   ```bash
   git pull origin master

10. Copy .env.example ke .env:
   ```bash
   cp .env.example .env

11. Update isi .env dengan:
   
   - DB_CONNECTION=mysql
   - DB_HOST=127.0.0.1
   - DB_PORT=3306
   - DB_DATABASE=db_sengkuyung
   - DB_USERNAME=root
   - DB_PASSWORD=

12. Jalankan composer install:
   ```bash
   composer install

13. Generate key:
   ```bash
   php artisan key:generate

14. Migrate
   ```bash
   php artisan migrate

15. Seed
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=UserSeeder
   php artisan db:seed --class=StatusSeeder
   php artisan db:seed --class=StatusVerifikasiSeeder
   php artisan db:seed --class=SengStatusFileSeeder

16. Arahkan path domain ke /public
17. Copy .env.example ke .env:
   ```bash
   cp .htaccess.example .htaccess

18. Optimize:
   ```bash
   php artisan optimize:clear

19. Buka domain!