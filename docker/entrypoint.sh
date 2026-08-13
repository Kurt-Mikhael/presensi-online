#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache public/build
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ ! -d vendor ]; then
    echo "[entrypoint] composer install..."
    composer install --no-interaction --prefer-dist --no-progress --no-security-blocking --optimize-autoloader
fi

if [ ! -f .env ]; then
    echo "[entrypoint] menyalin .env.example -> .env"
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
    echo "[entrypoint] generating APP_KEY..."
    php artisan key:generate --force >/dev/null
fi

echo "[entrypoint] menautkan storage publik..."
php artisan storage:link --force >/dev/null 2>&1 || true

echo "[entrypoint] menunggu database..."
until pg_isready -h db -U "${DB_USERNAME:-presensi}" -q; do
    sleep 1
done
sleep 2

echo "[entrypoint] menjalankan migrasi..."
php artisan migrate --force

echo "[entrypoint] membersihkan cache..."
php artisan optimize:clear 2>/dev/null || true

echo "[entrypoint] memulai php-fpm"
exec php-fpm
