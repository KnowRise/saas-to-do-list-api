# --- Tahap 1: Builder ---
# Di tahap ini kita menyiapkan semua dependensi dan membangun aset.
# Kita beri nama tahap ini "builder"
FROM serversideup/php:8.3-fpm-nginx AS builder

# Ganti ke user root untuk bisa install software
USER root

# Install Node.js dan Git (Git mungkin dibutuhkan oleh Composer)
RUN apt-get update && apt-get install -y nodejs npm git

# Ganti ke user non-root untuk keamanan
USER www-data

# Pindah ke direktori kerja
WORKDIR /var/www/html

# --- Optimasi Cache untuk Composer ---
# Salin hanya file dependensi dulu. Jika file ini tidak berubah,
# Docker akan menggunakan cache dan melewati `composer install`.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader

# --- Optimasi Cache untuk NPM ---
# Sama seperti composer, salin hanya file dependensi Node.js
COPY --chown=www-data:www-data package.json package-lock.json ./
RUN npm install

# Sekarang, salin sisa file aplikasi
COPY --chown=www-data:www-data . .

# Jalankan perintah build dan optimasi Laravel
# Kita butuh `key:generate` jika .env belum ada kuncinya.
# Perintah optimasi ini sangat penting untuk performa di produksi.
RUN php artisan key:generate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && npm run build

# --- Tahap 2: Production ---
# Di tahap ini kita membangun image final yang bersih dan ringan.
FROM serversideup/php:8.3-fpm-nginx

# Ganti ke user non-root
USER www-data

# Pindah ke direktori kerja
WORKDIR /var/www/html

# Salin file yang sudah di-build dari tahap "builder"
# Kita hanya menyalin file yang benar-benar dibutuhkan untuk production.
# Tidak ada lagi node_modules, source code JS/CSS mentah, dll.
COPY --from=builder /var/www/html .

# Expose port 8000 (port default yang digunakan oleh base image ini)
EXPOSE 8000
