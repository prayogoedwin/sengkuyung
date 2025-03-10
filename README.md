# MINI LARAVEL STARTER

App ini adalah laravel starter dengan fitur paling minimal, ditujukan untuk kebutuhan pribadi yang butuh cepat dari install laravel (11), templating blade, hingga crud user dasar

## Fitur

- Fitur 1
- Fitur 2
- Fitur 3

## Prerequisites

Sebelum menjalankan proyek ini, pastikan Anda memiliki hal-hal berikut:

- PHP >= 8.3
- Composer
- Database (MySQL)

## Instalasi

Langkah-langkah untuk menginstal dan menjalankan proyek:

1. Clone repositori ini:
   ```bash
   git clone https://github.com/prayogoedwin/mini-laravel-starter.git

2. Copy .env.example ke .env:
   ```bash
   cp .env.example .env

3. Update isi .env dengan:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_sengkuyung
   DB_USERNAME=root
   DB_PASSWORD=

4. Jalankan composer install:
   ```bash
   composer install

5. Generate key laravel:
   ```bash
   php artisan key:generate

6. Migrate
   ```bash
   php artisan migrate