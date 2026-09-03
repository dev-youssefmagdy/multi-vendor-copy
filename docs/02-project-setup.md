# 02 — Project Setup

## Ownership & Permissions

```bash
sudo chown -R www-data:www-data /var/www/multi-vendor
sudo chmod -R 755 /var/www/multi-vendor
sudo chmod -R 775 /var/www/multi-vendor/storage /var/www/multi-vendor/bootstrap/cache

# Add your user to www-data so you can edit files without sudo
sudo usermod -aG www-data $USER
newgrp www-data
```

## Install PHP Dependencies

```bash
cd /var/www/multi-vendor
composer install --no-dev --optimize-autoloader
```

## Install Node Dependencies & Build Assets

```bash
npm install
npm run build
```

> The `public/build/` folder will be created with all compiled Tailwind CSS and JS bundles.

## Create Storage Symlink

```bash
php artisan storage:link
```

> This creates `public/storage` → `storage/app/public`.
> If it says "already exists", it is fine.
