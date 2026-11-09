#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh — run this after every git pull to deploy the latest code
# Usage: sudo bash deploy.sh
# ─────────────────────────────────────────────────────────────────────────────
set -e

APP_DIR="/var/www/multi-vendor"
cd "$APP_DIR"

echo "==> [1/8] Pulling latest code..."
git pull origin main

echo "==> [2/8] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "==> [3/8] Building frontend assets..."
npm ci
npm run build

echo "==> [4/8] Running central migrations..."
php artisan migrate --force

echo "==> [5/8] Running tenant migrations..."
php artisan tenants:migrate --force

echo "==> [6/8] Seeding permissions..."
php artisan db:seed --class=AdminPermissionSeeder --force --no-interaction
php artisan tenants:seed --class=TenantPermissionSeeder --force --no-interaction

echo "==> [7/8] Caching config / routes / views..."
php artisan optimize:clear
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache
# php artisan event:cache

echo "==> [8/8] Fixing permissions & restarting services..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

php artisan queue:restart
# systemctl restart laravel-queue python-modules php8.3-fpm

supervisorctl reread
supervisorctl update
supervisorctl restart all

echo ""
echo "✓  Deploy complete."
